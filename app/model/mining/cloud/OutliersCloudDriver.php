<?php

namespace EasyMinerCenter\Model\Mining\Cloud;

use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTask;
use EasyMinerCenter\Model\EasyMiner\Entities\OutliersTaskState;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\Mining\Entities\Outlier;
use EasyMinerCenter\Model\Mining\Exceptions\MinerCommunicationException;
use EasyMinerCenter\Model\Mining\Exceptions\OutliersTaskInvalidArgumentException;
use EasyMinerCenter\Model\Mining\IOutliersMiningDriver;
use Nette\Utils\Json;

/**
 * Class OutliersCloudDriver - driver pro práci s outliery pomocí dolovací služby easyminer-miner
 * @package EasyMinerCenter\Model\Mining\Cloud
 * @author Stanislav Vojíř
 */
class OutliersCloudDriver extends AbstractCloudDriver implements IOutliersMiningDriver{
  /** @var  OutliersTask $outliersTask */
  private $outliersTask;

  #region konstanty pro dolování (před vyhodnocením pokusu o dolování jako chyby)
  const MAX_MINING_REQUESTS=5;
  const REQUEST_DELAY=1;// delay between requests (in seconds)
  #endregion

  /**
   * @param OutliersTask $outliersTask
   * @param MinersFacade $minersFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array $params = array() - parametry výchozí konfigurace
   */
  public function __construct(OutliersTask $outliersTask=null, MinersFacade $minersFacade, MetaAttributesFacade $metaAttributesFacade, User $user, XmlSerializersFactory $xmlSerializersFactory, $params = array()){
    parent::__construct($minersFacade,$metaAttributesFacade,$user,$xmlSerializersFactory,$params);
    $this->outliersTask=$outliersTask;
  }

  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @return OutliersTaskState
   * @throws \Exception
   */
  public function startMining(){
    //import úlohy a spuštění dolování...
    $numRequests=1;
    sendStartRequest:
    try{
      #region pracovní zjednodušený request
      $response=self::curlRequestResponse($this->getRemoteMinerUrl().'/outlier-detection', Json::encode([
        'datasetId'=>$this->outliersTask->miner->metasource->ppDatasetId,
        'minSupport'=>$this->outliersTask->minSupport
      ]),'POST',[
        'Content-Type'=>'application/json; charset=utf-8'
      ],$this->getApiKey(),$responseCode);
      $taskState=$this->parseResponse($response,$responseCode);
      #endregion
    }catch (\Exception $e){
      if ((++$numRequests < self::MAX_MINING_REQUESTS)){sleep(self::REQUEST_DELAY); goto sendStartRequest;}
    }
    if (!empty($taskState)){
      return $taskState;
    }else{
      throw new \Exception('Task import failed!');
    }
  }

  /**
   * Funkce vracející info o aktuálním stavu dané úlohy
   * @return OutliersTaskState
   */
  public function checkOutliersTaskState(){
    if ($this->outliersTask->state==OutliersTask::STATE_IN_PROGRESS){
      if($this->outliersTask->resultsUrl!=''){
        $numRequests=1;
        sendStartRequest:
        try{
          #region zjištění stavu úlohy
          $url=$this->getRemoteMinerUrl().'/'.$this->outliersTask->resultsUrl.'?apiKey='.$this->getApiKey();
          $response=self::curlRequestResponse($url,'','GET',[],$this->getApiKey(),$responseCode);
          $taskState=$this->parseResponse($response,$responseCode);
          if ($taskState!==null){
            return $taskState;
          }
          #endregion
        }catch (\Exception $e){
          if ((++$numRequests < self::MAX_MINING_REQUESTS)){sleep(self::REQUEST_DELAY); goto sendStartRequest;}
        }
      }else{
        $taskState=$this->outliersTask->getTaskState();
        $taskState->state=OutliersTask::STATE_FAILED;
        return $taskState;
      }
    }
    return $this->outliersTask->getTaskState();
  }

  /**
   * Function for parsing of task mining start/check state request
   * @param string $response
   * @param int $responseCode
   * @return OutliersTaskState|null
   */
  private function parseResponse($response, $responseCode){
    if ($responseCode==202 || $responseCode==204){
      //task accepted
      $responseData=Json::decode($response,Json::FORCE_ARRAY);
      if (!empty($responseData['statusLocation']) && !empty($responseData['taskId'])){
        //došlo ke spuštění úlohy...
        $taskState=$this->outliersTask->getTaskState();
        $taskState->state=OutliersTask::STATE_IN_PROGRESS;
        $taskState->resultsUrl='outlier-detection/'.$responseData['taskId'];
        return $taskState;
      }
    }elseif($responseCode==201){
      $responseData=Json::decode($response,Json::FORCE_ARRAY);
      if (!empty($responseData['id'])){
        $taskState=$this->outliersTask->getTaskState();
        $taskState->resultsUrl=null;
        $taskState->minerOutliersTaskId=$responseData['id'];
        $taskState->state=OutliersTask::STATE_SOLVED;
        return $taskState;
      }
    }elseif($responseCode>=400){
      return new OutliersTaskState($this->outliersTask,OutliersTask::STATE_FAILED);
    }
    return null;
  }


  /**
   * Funkce pro nastavení aktivní úlohy
   * @param OutliersTask $outliersTask
   */
  public function setOutliersTask(OutliersTask $outliersTask){
    $this->outliersTask=$outliersTask;
  }

  /**
   * Funkce pro odstranění aktivní úlohy
   * @return bool
   * @throws OutliersTaskInvalidArgumentException
   * @throws MinerCommunicationException
   */
  public function deleteOutliersTask(){
    //kontrola, jestli není úloha aktuálně v procesu řešení
    if ($this->outliersTask->state==OutliersTask::STATE_IN_PROGRESS){
      $outliersTaskState=$this->checkOutliersTaskState();
      if ($outliersTaskState->state==OutliersTask::STATE_IN_PROGRESS){
        throw new OutliersTaskInvalidArgumentException('Outliers task is in progress - it is not possible to remove it.');
      }
    }
    $minerOutliersTask=$this->outliersTask->getMinerOutliersTask();
    try{
      self::curlRequestResponse($this->getRequestUrl('/result/'.$minerOutliersTask->dataset.'/'.$minerOutliersTask->id),'','DELETE',[],$this->getApiKey(),$responseCode);
      if ($responseCode=='200' || $responseCode=='404'){
        return true;
      }
    }catch(MinerCommunicationException $e){
      throw $e;
    }
    return false;
  }

  /**
   * Funkce volaná před smazáním konkrétního mineru
   * @return mixed
   */
  public function deleteMiner(){
    return true;
  }

  /**
   * Funkce vracející výsledky úlohy dolování outlierů
   * @param int $limit
   * @param int $offset
   * @return Outlier[]
   */
  public function getOutliersTaskResults($limit, $offset=0){
    // TODO: Implement getOutliersTaskResults() method.
  }

  /**
   * Funkce vracející URL pro odeslání požadavku na datovou službu
   *
   * @param string $relativeUrl
   * @return string
   */
  private function getRequestUrl($relativeUrl){
    return $this->getRemoteMinerUrl().'/outlier-detection'.$relativeUrl;
  }

}