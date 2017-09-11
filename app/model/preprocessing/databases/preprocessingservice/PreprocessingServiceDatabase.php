<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Entities\PpValue;
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
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Class PreprocessingServiceDatabase - driver for access to databases using EasyMiner-Preprocessing
 * @package EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
abstract class PreprocessingServiceDatabase implements IPreprocessing {
  /** @var  string $apiKey */
  private $apiKey;
  /** @var  PpConnection $ppConnection */
  private $ppConnection;

  /**
   * Method returning list of available datasets
   * @return PpDataset[]
   */
  public function getPpDatasets() {
    $responseData=$this->curlRequestResponse($this->getRequestUrl('/dataset'),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
    $responseData=Json::decode($responseData, Json::FORCE_ARRAY);

    $result=[];
    if (!empty($responseData) && $responseCode==200){
      foreach($responseData as $item){
        if (!$item['active']){continue;}//it is working mark of preprocessing service, that the given dataset is not prepared yet
        $result[]=new PpDataset($item['id'],$item['name'],$item['dataSource'],$item['type'],$item['size']);
      }
    }
    return $result;
  }

  /**
   * Method returning info about one selected dataset
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
   * Method returning list of attributes (data columns) in selected dataset
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
            $result[]=new PpAttribute($responseField['id'], $responseField['dataset'], $responseField['field'], $responseField['name'], (empty($responseField['type'])?PpAttribute::TYPE_NOMINAL:$responseField['type']), $responseField['uniqueValuesSize']);
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
   * Method returning details of one attribute
   * @param PpDataset $ppDataset
   * @param string $ppAttributeId
   * @return PpAttribute
   * @throws PreprocessingException
   */
  public function getPpAttribute(PpDataset $ppDataset, $ppAttributeId) {
    try{
      $responseData=$this->curlRequestResponse($this->getRequestUrl('/dataset/'.$ppDataset->id.'/attribute/'.$ppAttributeId),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
      if ($responseCode==200){
        $responseData=Json::decode($responseData, Json::FORCE_ARRAY);
        return new PpAttribute($responseData['id'], $responseData['dataset'], $responseData['field'], $responseData['name'], (empty($responseData['type'])?PpAttribute::TYPE_NOMINAL:$responseData['type']), $responseData['uniqueValuesSize']);
      }else{
        throw new PreprocessingCommunicationException('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new PreprocessingException();
    }
  }



  /**
   * PreprocessingServiceDatabase constructor, providing connection to remote database
   * @param PpConnection $ppConnection
   * @param string $apiKey
   */
  public function __construct(PpConnection $ppConnection, $apiKey) {
    $this->ppConnection=$ppConnection;
    $this->ppConnection->dbServer=rtrim($this->ppConnection->dbServer,'/');//we do not want the slash on end
    $this->apiKey=$apiKey;
  }

  #region methods for work with RESTFUL API
  /**
   * Method returning URL for sending a request to EasyMiner-Preprocessing service
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
   * @param string $url
   * @param string $postData = ''
   * @param string|null $method = 'GET'
   * @param array $headersArr =[]
   * @param int|null &$responseCode - variable returning the response code
   * @return string - response data
   * @throws PreprocessingCommunicationException
   */
  private function curlRequestResponse($url, $postData='', $method='GET', $headersArr=[], &$responseCode=null){
    if (Strings::startsWith($url,'/')){
      $url='http://localhost/'.$url;
    }
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
  #endregion methods for work with RESTFUL API

  /**
   * Method for creating (initializating) a dataset
   * @param PpDataset|null $ppDataset = null
   * @param PpTask|null $ppTask = null
   * @return PpDataset|PpTask - when the operation is finished, it returns PpDataset, others it returns PpTask
   * @throws PreprocessingCommunicationException
   */
  public function createPpDataset(PpDataset $ppDataset=null, PpTask $ppTask=null) {
    if ($ppTask){
      //the task is running
      $response=$this->curlRequestResponse(
        $ppTask->getNextLocation(),
        null,
        'GET',
        ['Accept'=>'application/json; charset=utf8'],
        $responseCode
      );
    }elseif($ppDataset){
      //it is initialization of a new dataset
      $response=$this->curlRequestResponse(
        $this->getRequestUrl('/dataset'),
        http_build_query(['dataSource'=>$ppDataset->dataSource,'name'=>$ppDataset->name]),
        'POST',
        ['Accept'=>'application/json; charset=utf8', 'Content-Type'=>'application/x-www-form-urlencoded'],
        $responseCode
      );
    }else{
      throw new \BadMethodCallException('createPpDataset - it is necessary to set up a ppDataset or a ppTask');
    }

    try{
      $response=Json::decode($response,Json::FORCE_ARRAY);
    }catch (JsonException $e){
      throw new PreprocessingCommunicationException('Response encoding failed.',$e);
    }
    switch ($responseCode){
      /** @noinspection PhpMissingBreakStatementInspection */
      case 200:
        if (!empty($response['id'])){
          //task finished successfully
          return new PpDataset($response['id'],$response['name'],$response['dataSource'],$response['type'],$response['size']);
        }
      case 201:
        //it is still running task - return PpTask with info gained from preprocessing service
        return new PpTask($response);
      case 202:
        //it is new long running task
        return new PpTask($response);
      case 400:
      case 500:
      case 404:
      default:
        throw new PreprocessingCommunicationException(@$response['name'].': '.@$response['message'],$responseCode);
    }
  }

  /**
   * Method for deleting a dataset
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

  /**
   * Method returning list of available preprocessing types
   * @return string[]
   */
  public static function getSupportedPreprocessingTypes() {
    return [
      Preprocessing::TYPE_EACHONE,
      Preprocessing::TYPE_INTERVAL_ENUMERATION,
      Preprocessing::TYPE_NOMINAL_ENUMERATION,
      Preprocessing::TYPE_EQUIDISTANT_INTERVALS,
      Preprocessing::TYPE_EQUIFREQUENT_INTERVALS,
      Preprocessing::TYPE_EQUISIZED_INTERVALS
    ];
  }

  /**
   * Method for initialization of preprocessing of an attribute
   * @param Attribute[] $attributes
   * @param PpTask $ppTask = null
   * @return PpAttribute[]|PpTask
   * @throws PreprocessingCommunicationException
   */
  public function createAttributes(array $attributes=null, PpTask $ppTask=null) {
    if ($ppTask){
      //it is running task
      $response=$this->curlRequestResponse(
        $ppTask->getNextLocation(),
        null,
        'GET',
        ['Accept'=>'application/json; charset=utf8'],
        $responseCode
      );
    }elseif(!empty($attributes)){
      //it is new initialization of dataset - for the first we have to prepare config PMML and get reference to metasource
      $metasource=$attributes[0]->metasource;
      $preprocessingPmml=PreprocessingPmmlSerializer::preparePreprocessingPmml($attributes);

      //send preprocessing request
      $response=$this->curlRequestResponse(
        $this->getRequestUrl('/dataset/'.$metasource->ppDatasetId.'/attribute'),
        $preprocessingPmml,
        'POST',
        ['Accept'=>'application/json; charset=utf8', 'Content-Type'=>'application/xml; charset=utf-8'],
        $responseCode
      );
    }else{
      throw new \BadMethodCallException('createAttributes - it is necessary to set up array of attributes or a ppTask');
    }

    try{
      $response=Json::decode($response,Json::FORCE_ARRAY);
    }catch (JsonException $e){
      throw new PreprocessingCommunicationException('Response encoding failed.',$e);
    }
    switch ($responseCode){
      /** @noinspection PhpMissingBreakStatementInspection */
      case 200:
        if (empty($response['statusLocation']) && empty($response['resultLocation'])){
          //task finished successfully
          /** @var PpAttribute[] $result */
          $result=[];
          if (!empty($response) && is_array($response)){
            foreach($response as $responseItem){
              $result[]=new PpAttribute($responseItem['id'],$responseItem['dataset'],$responseItem['field'],$responseItem['name'],empty($responseItem['type'])?PpAttribute::TYPE_NOMINAL:$responseItem['type'],$responseItem['uniqueValuesSize']);
            }
          }
          return $result;
        }
      case 201:
        //it is still running task - return PpTask with info gained from preprocessing service
        return new PpTask($response);
      case 202:
        //it is new long running task
        return new PpTask($response);
      case 400:
      case 500:
      case 404:
      default:
        Debugger::log($response,ILogger::EXCEPTION);
        throw new PreprocessingCommunicationException(@$response['name'].': '.@$response['message'],$responseCode);
    }
  }

  /**
   * Method returning values of one selected attribute
   * @param PpDataset $ppDataset
   * @param int $ppAttributeId
   * @param int $offset
   * @param int $limit
   * @return PpValue[]
   *
   * @throws PreprocessingException
   */
  public function getPpValues(PpDataset $ppDataset, $ppAttributeId, $offset=0, $limit=1000){
    try{
      $responseData=$this->curlRequestResponse($this->getRequestUrl('/dataset/'.urlencode($ppDataset->id).'/attribute/'.urlencode($ppAttributeId).'/values?offset='.intval($offset).'&limit='.intval($limit)),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
      if ($responseCode==200){
        $responseData=Json::decode($responseData, Json::FORCE_ARRAY);
        $result=[];
        if (!empty($responseData)){
          foreach($responseData as $responseItem){
            $result[]=new PpValue($responseItem['id'],$responseItem['value'],$responseItem['frequency']);
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

}