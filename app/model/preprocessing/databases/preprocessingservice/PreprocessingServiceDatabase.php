<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Exceptions\DatasetNotFoundException;
use EasyMinerCenter\Model\Preprocessing\Entities\PpAttribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpDataset;
use EasyMinerCenter\Model\Preprocessing\Entities\PpTask;
use EasyMinerCenter\Model\Preprocessing\Exceptions\PreprocessingCommunicationException;
use EasyMinerCenter\Model\Preprocessing\Exceptions\PreprocessingException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

/**
 * Class PreprocessingServiceDatabase - třída zajišťující přístup k databázím dostupným prostřednictvím služby EasyMiner-Preprocessing
 *
 * @package EasyMinerCenter\Model\Data\Databases\DataService
 * @author Stanislav Vojíř
 */
class PreprocessingServiceDatabase implements IPreprocessing {
  /** @var  string $apiKey */
  private $apiKey;
  /** @var  PpConnection $ppConnection */
  private $ppConnection;

  /**
   * Funkce vracející seznam datových zdrojů v DB
   *
   * @return PpDataset[]
   */
  public function getPpDatasets() {
    $responseData=$this->curlRequestResponse($this->getRequestUrl('/dataset'),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
    $responseData=Json::decode($responseData, Json::FORCE_ARRAY);

    $result=[];
    if (!empty($responseData) && $responseCode==200){
      foreach($responseData as $item){
        if (!$item['active']){continue;}//jde o pracovní příznak preprocessing služby, že daný dataset ještě není připraven
        $result[]=new PpDataset($item['id'],$item['name'],$item['dataSource'],$item['type'],$item['size']);
      }
    }
    return $result;
  }

  /**
   * Funkce vracející informace o konkrétním datovém zdroji
   *
   * @param int|string $ppDatasetId
   * @return PpDataset
   * @throws PreprocessingException
   */
  public function getPpDataset($ppDatasetId) {
    try{
      $responseData=$this->curlRequestResponse($this->getRequestUrl('/dataset/'.$ppDatasetId), null, 'GET', ['Accept'=>'application/json; charset=utf8'], $responseCode);
      $responseData=Json::decode($responseData, Json::FORCE_ARRAY);

      if(!empty($responseData) && ($responseCode==200)) {
        return new PpDataset($responseData['id'], $responseData['name'], $responseData['dataSource'], $responseData['type'], $responseData['size']);
      }else{
        throw new PreprocessingCommunicationException('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new DatasetNotFoundException($e);
    }
  }

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param PpDataset $ppDataset
   * @return PpAttribute[]
   * @throws PreprocessingException
   */
  public function getPpAttributes(PpDataset $ppDataset) {
    try{
      $responseData=$this->curlRequestResponse($this->getRequestUrl('/dataset/'.$ppDataset->id.'/attribute'),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
      if ($responseCode==200){
        $responseData=Json::decode($responseData, Json::FORCE_ARRAY);
        $result=[];
        if (!empty($responseData)){
          foreach($responseData as $responseField){
            $result[]=new PpAttribute($responseField['id'], $responseField['dataset'], $responseField['field'], $responseField['name'], $responseField['type'], $responseField['uniqueValuesSize']);
          }
        }
        return $result;
      }else{
        throw new PreprocessingCommunicationException('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new PreprocessingException();
    }
  }





  /**
   * Konstruktor zajišťující připojení k preprocessing DB/službě
   *
   * @param PpConnection $ppConnection
   * @param string $apiKey
   */
  public function __construct(PpConnection $ppConnection, $apiKey) {
    $this->ppConnection=$ppConnection;
    $this->ppConnection->dbServer=rtrim($this->ppConnection->dbServer,'/');//nechceme lomítko na konci
    $this->apiKey=$apiKey;
  }

  #region funkce pro práci s RESTFUL API
  /**
   * Funkce vracející URL pro odeslání požadavku na datovou službu
   *
   * @param string $relativeUrl
   * @return string
   */
  private function getRequestUrl($relativeUrl){
    $url=$this->ppConnection->dbApi;
    if (Strings::endsWith($url,'/')){
      $url=rtrim($url,'/');
    }
    return $url.$relativeUrl;
  }

  /**
   * Funkce pro práci s RESTFUL API
   *
   * @param string $url
   * @param string $postData = ''
   * @param string|null $method = 'GET'
   * @param array $headersArr =[]
   * @param int|null &$responseCode - proměnná pro vrácení stavového kódu odpovědi
   * @return string - response data
   * @throws PreprocessingCommunicationException
   */
  private function curlRequestResponse($url, $postData='', $method='GET', $headersArr=[], &$responseCode=null){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch,CURLOPT_MAXREDIRS,0);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
    if (empty($headersArr['Content-Type']) & !empty($postData)){
      $headersArr['Content-Type']='application/xml; charset=utf-8';
    }
    if (!empty($this->apiKey)){
      $headersArr['Authorization']='ApiKey '.$this->apiKey;
    }
    if ($postData!=''){
      curl_setopt($ch,CURLOPT_POST,true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, ($method?$method:'POST'));
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
      $headersArr['Content-length']=strlen($postData);
    }else{
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, ($method?$method:'GET'));
    }

    $httpHeadersArr=[];
    if (!empty($headersArr)){
      foreach($headersArr as $header=>$value){
        $httpHeadersArr[]=$header.': '.$value;
      }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeadersArr);

    $responseData = curl_exec($ch);
    $responseCode=curl_getinfo($ch,CURLINFO_HTTP_CODE);

    if(curl_errno($ch)){
      $exception=curl_error($ch);
      $exceptionNumber=curl_errno($ch);
      curl_close($ch);
      throw new PreprocessingCommunicationException($exception,$exceptionNumber);
    }
    curl_close($ch);
    return $responseData;
  }
  #endregion funkce pro práci s RESTFUL API

  /**
   * Funkce pro inicializaci preprocessind datasetu
   *
   * @param PpDataset|null $ppDataset = null
   * @param PpTask|null $ppTask = null
   * @return PpDataset|PpTask - při dokončení vytvoření úlohy vrací PpDataset, jinak PpTask
   * @throws PreprocessingCommunicationException
   */
  public function createPpDataset(PpDataset $ppDataset=null, PpTask $ppTask=null) {
    if ($ppTask){
      //jde o již běžící úlohu
      $response=$this->curlRequestResponse(
        $ppTask->getNextLocation(),
        null,
        'GET',
        ['Accept'=>'application/json; charset=utf8'],
        $responseCode
      );
    }elseif($ppDataset){
      //jde o novou inicializaci datasetu
      $response=$this->curlRequestResponse(
        $this->getRequestUrl('/dataset'),
        http_build_query(['dataSource'=>$ppDataset->dataSource,'name'=>$ppDataset->name]),
        'POST',
        ['Accept'=>'application/json; charset=utf8', 'Content-Type'=>'application/x-www-form-urlencoded'],
        $responseCode
      );
    }else{
      throw new \BadFunctionCallException('createPpDataset - it is necessary to set up a ppDataset or a ppTask');
    }



    try{
      $response=Json::decode($response,Json::FORCE_ARRAY);
    }catch (JsonException $e){
      throw new PreprocessingCommunicationException('Response encoding failed.',$e);
    }
    switch ($responseCode){
      case 200:
        //úloha byla úspěšně dokončena
        return new PpDataset($response['id'],$response['name'],$response['dataSource'],$response['type'],$response['size']);
      case 201:
        //jde o pokračující úlohu - vracíme PpTask s informacemi získanými z preprocessing služby
        return new PpTask($response);
      case 202:
        //jde o vytvoření nové dlouhotrvající úlohy
        return new PpTask($response);
      case 400:
      case 500:
      case 404:
      default:
        throw new PreprocessingCommunicationException(@$response['name'].': '.@$response['message'],$responseCode);
    }
  }

  /**
   * Funkce pro odstranění preprocessing datasetu
   *
   * @param PpDataset $ppDataset
   * @throws DatasetNotFoundException
   * @throws PreprocessingCommunicationException
   */
  public function deletePpDataset(PpDataset $ppDataset) {
    $this->curlRequestResponse($this->getRequestUrl('/dataset/'.$ppDataset->id),null,'DELETE',['Accept'=>'application/json; charset=utf8'], $responseCode);
    if ($responseCode!=200){
      throw new DatasetNotFoundException();
    }
  }


  public static function getPpTypeName() {/*TODO: remove*/}
  public static function getPpType() {/*TODO: remove*/}

}