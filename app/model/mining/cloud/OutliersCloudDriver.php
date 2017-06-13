<?php

namespace EasyMinerCenter\Model\Mining\Cloud;

use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
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
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class OutliersCloudDriver extends AbstractCloudDriver implements IOutliersMiningDriver{
  /** @var  OutliersTask $outliersTask */
  private $outliersTask;
  /** @var  Attribute[] $attributesArrByPpDatasetAttributeId */
  private $attributesArrByPpDatasetAttributeId;

  #region constants for mining delay/max requests
  const MAX_MINING_REQUESTS=5;
  const REQUEST_DELAY=1;// delay between requests (in seconds)
  #endregion constants for mining delay/max requests

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
   * Method for start of the current data mining task
   * @return OutliersTaskState
   * @throws \Exception
   */
  public function startMining(){
    //import task and run the mining
    $numRequests=1;
    sendStartRequest:
    try{
      #region send request
      $minerOutliersTask=$this->outliersTask->getMinerOutliersTask();
      $response=self::curlRequestResponse($this->getRemoteMinerUrl().'/outlier-detection', Json::encode([
        'datasetId'=>$minerOutliersTask->dataset,
        'minSupport'=>$this->outliersTask->minSupport
      ]),'POST',[
        'Content-Type'=>'application/json; charset=utf-8'
      ],$this->getApiKey(),$responseCode);
      $taskState=$this->parseResponse($response,$responseCode);
      #endregion send request
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
   * Method for checking the current task state
   * @return OutliersTaskState
   */
  public function checkOutliersTaskState(){
    if ($this->outliersTask->state==OutliersTask::STATE_IN_PROGRESS){
      if($this->outliersTask->resultsUrl!=''){
        $numRequests=1;
        sendStartRequest:
        try{
          #region check OutliersTask state
          $url=$this->getRemoteMinerUrl().'/'.$this->outliersTask->resultsUrl.'?apiKey='.$this->getApiKey();
          $response=self::curlRequestResponse($url,'','GET',[],$this->getApiKey(),$responseCode);
          $taskState=$this->parseResponse($response,$responseCode);
          if ($taskState!==null){
            return $taskState;
          }
          #endregion check OutliersTask state
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
        //OutliersTask started...
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
   * Method for setting the current (active) OutliersTask
   * @param OutliersTask $outliersTask
   */
  public function setOutliersTask(OutliersTask $outliersTask){
    $this->outliersTask=$outliersTask;
  }

  /**
   * Method for deleting the current OutliersTask
   * @return bool
   * @throws OutliersTaskInvalidArgumentException
   * @throws MinerCommunicationException
   */
  public function deleteOutliersTask(){
    //check, if the task is not currently running
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
   * Method for deleting the current miner
   * @return mixed
   */
  public function deleteMiner(){
    return true;
  }

  /**
   * Method returning results of the current task
   * @param int $limit
   * @param int $offset
   * @return Outlier[]
   * @throws MinerCommunicationException
   */
  public function getOutliersTaskResults($limit, $offset=0){
    try{
      $minerOutliersTask=$this->outliersTask->getMinerOutliersTask();
      $response=self::curlRequestResponse($this->getRequestUrl('/result/'.$minerOutliersTask->dataset.'/'.$minerOutliersTask->id.'/outliers?offset='.$offset.'&limit='.$limit),'','GET',[],$this->getApiKey(),$responseCode);
      if ($responseCode=='200'){
        //process the results
        return $this->parseOutliersTaskResultsResponse($response);
      }else{
        throw new MinerCommunicationException('Results not found.');
      }
    }catch(MinerCommunicationException $e){
      throw $e;
    }
  }

  /**
   * Private method for parsing of task results
   * @param string $response
   * @return Outlier[]
   */
  private function parseOutliersTaskResultsResponse($response){
    $responseData=Json::decode($response,Json::FORCE_ARRAY);
    $result=[];
    if (!empty($responseData)){
      foreach($responseData as $responseItem){
        $outlier=new Outlier();
        $outlier->score=$responseItem['score'];
        $outlier->id=$responseItem['instance']['id'];
        if (!empty($responseItem['instance']['values'])){
          foreach($responseItem['instance']['values'] as $valueItem){
            $outlier->attributeValues[$this->getAttributeName($valueItem['attribute'])]=$valueItem['value'];
          }
        }
        $result[]=$outlier;
      }
    }

    return $result;
  }

  /**
   * Private method returning the name of attribute with the given ppAttributeId
   * @param string $ppAttributeId
   * @return string
   */
  private function getAttributeName($ppAttributeId){
    if (empty($this->attributesArrByPpDatasetAttributeId)){
      $attributesArr=$this->outliersTask->miner->metasource->attributes;
      if (!empty($attributesArr)){
        foreach($attributesArr as $attribute){
          $this->attributesArrByPpDatasetAttributeId[$attribute->ppDatasetAttributeId]=$attribute;
        }
      }
    }
    return $this->attributesArrByPpDatasetAttributeId[$ppAttributeId]->name;
  }


  /**
   * Method returning URL for sending of a request
   * @param string $relativeUrl
   * @return string
   */
  private function getRequestUrl($relativeUrl){
    return $this->getRemoteMinerUrl().'/outlier-detection'.$relativeUrl;
  }

}