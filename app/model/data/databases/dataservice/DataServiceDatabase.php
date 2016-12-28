<?php

namespace EasyMinerCenter\Model\Data\Databases\DataService;

use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\Data\Databases\IDatabase;
use EasyMinerCenter\Model\Data\Entities\DbField;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbValue;
use EasyMinerCenter\Model\Data\Entities\DbValuesRows;
use EasyMinerCenter\Model\Data\Exceptions\DatabaseCommunicationException;
use EasyMinerCenter\Model\Data\Exceptions\DatabaseException;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class DataServiceDatabase - třída zajišťující přístup k databázím dostupným prostřednictvím služby EasyMiner-Data
 *
 * @package EasyMinerCenter\Model\Data\Databases
 * @author Stanislav Vojíř
 */
abstract class DataServiceDatabase implements IDatabase {
  /** @var  string $apiKey */
  private $apiKey;
  /** @var  DbConnection $dbConnection */
  private $dbConnection;

  const UPLOAD_SLOW_DOWN_INTERVAL=500000;//interval pro zpomalení uploadu (v mi)
  const UPLOAD_CHUNK_SIZE=500000;
  const UPLOAD_CODE_SLOW_DOWN=429;
  const UPLOAD_CODE_CONTINUE=202;
  const UPLOAD_CODE_OK=200;

  /**
   * Funkce vracející seznam datových zdrojů v DB
   *
   * @return DbDatasource[]
   */
  public function getDbDatasources() {
    $responseData=$this->curlRequestResponse($this->getRequestUrl('/datasource'),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
    $responseData=Json::decode($responseData, Json::FORCE_ARRAY);

    $result=[];
    if (!empty($responseData) && $responseCode==200){
      foreach($responseData as $item){
        $result[]=new DbDatasource($item['id'],$item['name'],$item['type'],$item['size']);
      }
    }
    return $result;
  }

  /**
   * Funkce vracející informace o konkrétním datovém zdroji
   *
   * @param int $datasourceId
   * @return DbDatasource
   * @throws EntityNotFoundException
   */
  public function getDbDatasource($datasourceId) {
    try{
      $responseData=$this->curlRequestResponse($this->getRequestUrl('/datasource/'.$datasourceId),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
      $responseData=Json::decode($responseData, Json::FORCE_ARRAY);

      if (!empty($responseData) && ($responseCode==200)){
        return new DbDatasource($responseData['id'],$responseData['name'],$responseData['type'],$responseData['size']);
      }else{
        throw new \Exception('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new EntityNotFoundException('Requested DbDatasource was not found.');
    }
  }

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param DbDatasource $dbDatasource
   * @return DbField[]
   * @throws EntityNotFoundException
   * @throws \Exception
   */
  public function getDbFields(DbDatasource $dbDatasource) {
    $responseData=$this->curlRequestResponse($this->getRequestUrl('/datasource/'.$dbDatasource->id.'/field'),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
    if ($responseCode==200){
      $responseData=Json::decode($responseData, Json::FORCE_ARRAY);
      $result=[];
      if (!empty($responseData)){
        foreach($responseData as $responseField){
          $result[]=new DbField($responseField['id'], $responseField['dataSource'], $responseField['name'], $responseField['type'], $responseField['uniqueValuesSize']);
        }
      }
      return $result;
    }
    throw new EntityNotFoundException('Requested DbDatasource was not found.');
  }

  /**
   * Funkce pro přejmenování datového sloupce
   * @param DbField $dbField
   * @param string $newName='' (pokud není název vyplněn, je převzat název z DbField
   * @return bool
   */
  public function renameDbField(DbField $dbField, $newName=''){
    $newName=trim($newName);
    if (!$newName){
      $newName=$dbField->name;
    }
    $this->curlRequestResponse($this->getRequestUrl('/datasource/'.$dbField->dataSource.'/field/'.$dbField->id),$newName,'PUT',['Content-Type'=>'text/plain;charset=utf-8'], $responseCode);
    return ($responseCode==200);
  }


  /**
   * Funkce pro rozbalení komprimovaných dat
   * @param string $data
   * @param string $compression
   * @return string
   * @throws \Exception
   * @throws \Nette\Utils\JsonException
   */
  public function unzipData($data, $compression){
    #region zahájení uploadu
    $uploadStartConfig=[
      'mediaType'=>'csv',
      'maxLines'=>20,
      'compression'=>strtolower($compression)
    ];
    $uploadStartConfig=Json::encode($uploadStartConfig);
    $requestCount=0;
    do{
      $uploadId=$this->curlRequestResponse($this->getRequestUrl('/upload/preview/start'),$uploadStartConfig,'POST',['Content-Type'=>'application/json;charset=utf-8'], $responseCode);
      if ($requestCount>0){
        usleep(300);
      }
      $requestCount++;
    }while($responseCode!=200 & $requestCount<5);
    if ($responseCode!=200){
      throw new \Exception('Preview upload failed.');
    }
    #endregion zahájení uploadu
    $response=$this->curlRequestResponse($this->getRequestUrl('/upload/preview/').$uploadId,$data,'POST',['Content-Type'=>'text/plain'],$responseCode);

    do{
      switch($responseCode){
        case 200:
          return $response;
        case 202:
          usleep(300);
          $response=$this->curlRequestResponse($this->getRequestUrl('/upload/preview/').$uploadId,'','POST',['Content-Type'=>'text/plain'],$responseCode);
          $repeat=true;
          break;
        default:
          throw new \Exception('Preview upload failed:'.$response,$responseCode);
      }
    }while($repeat);

    return null;
  }

  /**
   * Funkce vracející hodnoty zvoleného datového sloupce (DbField)
   *
   * @param DbField $dbField
   * @param int $offset
   * @param int $limit
   * @return DbValue[]
   * @throws DatabaseException
   */
  public function getDbValues(DbField $dbField, $offset=0, $limit=1000){
    try{
      $responseData=$this->curlRequestResponse($this->getRequestUrl('/datasource/'.urlencode($dbField->dataSource).'/field/'.urlencode($dbField->id).'/values?offset='.intval($offset).'&limit='.intval($limit)),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
      if ($responseCode==200){
        $responseData=Json::decode($responseData, Json::FORCE_ARRAY);
        $result=[];
        if (!empty($responseData)){
          foreach($responseData as $responseItem){
            $result[]=new DbValue($responseItem['id'],$responseItem['value'],$responseItem['frequency']);
          }
        }
        return $result;
      }else{
        throw new DatabaseCommunicationException('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new DatabaseException();
    }
  }

  /**
   * Funkce vracející jednotlivé řádky z databáze
   *
   * @param DbDatasource $dbDatasource
   * @param int $offset =0
   * @param int $limit =1000
   * @param DbField[]|null $dbFields =null
   * @return DbValuesRows
   * @throws DatabaseException
   */
  public function getDbValuesRows(DbDatasource $dbDatasource, $offset=0, $limit=1000, $dbFields=null){
    try{
      $responseData=$this->curlRequestResponse($this->getRequestUrl('/datasource/'.urlencode($dbDatasource->id).'/instances?offset='.intval($offset).'&limit='.intval($limit)),null,'GET',['Accept'=>'application/json; charset=utf8'], $responseCode);
      if ($responseCode==200){
        $responseData=Json::decode($responseData, Json::FORCE_ARRAY);
        $fields=[];
        if (!empty($responseData['fields'])){
          foreach($responseData['fields'] as $fieldData){
            $fields[]=new DbField($fieldData['id'],$fieldData['dataSource'],$fieldData['name'],$fieldData['type'],$fieldData['uniqueValuesSize']);
          }
        }
        return new DbValuesRows($fields, !empty($responseData['instances'])?$responseData['instances']:[]);
      }else{
        throw new DatabaseCommunicationException('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new DatabaseException();
    }
  }

  /**
   * Funkce pro import existujícího CSV souboru do databáze
   *
   * @param string $filename
   * @param string $name
   * @param string $encoding
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string $nullValue
   * @param string[] $dataTypes
   * @return DbDatasource
   * @throws DatabaseException
   */
  public function importCsvFile($filename, $name, $encoding='utf-8', $delimiter=',', $enclosure='"', $escapeCharacter='\\', $nullValue='', $dataTypes){
    $file=@fopen($filename, 'r');
    if (!$file){
      throw new DatabaseException('File is not readable: '.$filename);
    }

    $uploadId=$this->startCsvUpload($name, $encoding, $delimiter, $enclosure, $escapeCharacter, $nullValue, $dataTypes);
    $filePart=fread($file, self::UPLOAD_CHUNK_SIZE);
    $callsCount=10000;
    while($callsCount>0){
      $callsCount--;
      try{
        $response=$this->curlRequestResponse($this->getRequestUrl('/upload/'.$uploadId),$filePart,'POST',['Content-Type'=>'text/plain'], $responseCode);
        switch($responseCode){
          case self::UPLOAD_CODE_CONTINUE:
            if($filePart==''){
              usleep(self::UPLOAD_SLOW_DOWN_INTERVAL);
            }elseif(!feof($file)){
              $filePart=fread($file, self::UPLOAD_CHUNK_SIZE);
            }else{
              $filePart='';
            }
            break;
          case self::UPLOAD_CODE_SLOW_DOWN:
            usleep(self::UPLOAD_SLOW_DOWN_INTERVAL);
            break;
          case self::UPLOAD_CODE_OK:
            fclose($file);
            $responseData=Json::decode($response,Json::FORCE_ARRAY);
            return new DbDatasource($responseData['id'],$responseData['name'],$responseData['type'],$responseData['size']);
        }
      }catch(\Exception $e){
        throw new DatabaseException('CSV upload failed: '.$e->getMessage(),(!empty($responseCode)?$responseCode:0),$e);
      }
    }
    throw new DatabaseException('CSV upload failed :-(');
  }

  /**
   * Funkce pro zahájení uploadu
   * @param string $name
   * @param string $encoding
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string $nullValue
   * @param string[] $dataTypes
   * @return string
   * @throws DatabaseException
   */
  private function startCsvUpload($name, $encoding='utf-8', $delimiter=',', $enclosure='"', $escapeCharacter='\\', $nullValue='', $dataTypes){
    $uploadParams=[
      'name'=>$name,
      'mediaType'=>'csv',
      'dbType'=>static::getDbType(),
      'separator'=>$delimiter,
      'encoding'=>$encoding,
      'quotesChar'=>$enclosure,
      'escapeChar'=>$escapeCharacter,
      'locale'=>'en',//TODO tohle by mělo být předáno nějakým parametrem...
      'nullValues'=>[$nullValue],
      'dataTypes'=>$dataTypes
    ];

    try{
      $uploadId=$this->curlRequestResponse($this->getRequestUrl('/upload/start'),Json::encode($uploadParams),'POST',['Content-Type'=>'application/json;charset=utf-8'], $responseCode);
      if ($responseCode==self::UPLOAD_CODE_OK){
        return $uploadId;
      }else{
        throw new DatabaseCommunicationException('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new DatabaseException();
    }
  }

  /**
   * Funkce pro odstranění datového zdroje
   *
   * @param DbDatasource $dbDatasource
   * @throws DatabaseException
   * @throws DatabaseCommunicationException
   */
  public function deleteDbDatasource(DbDatasource $dbDatasource){
    $this->curlRequestResponse($this->getRequestUrl('/datasource/'.$dbDatasource->id),null,'DELETE',['Accept'=>'application/json; charset=utf8'], $responseCode);
    if ($responseCode!=200){
      throw new DatabaseException();
    }
  }

  /**
   * Konstruktor zajišťující připojení k databázi
   *
   * @param DbConnection $dbConnection
   * @param string $apiKey
   */
  public function __construct(DbConnection $dbConnection, $apiKey) {
    $this->dbConnection=$dbConnection;
    $this->dbConnection->dbServer=rtrim($this->dbConnection->dbServer,'/');//nechceme lomítko na konci
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
    $url=$this->dbConnection->dbApi;
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
   * @param array $headersArr=[]
   * @param int|null &$responseCode - proměnná pro vrácení stavového kódu odpovědi
   * @return string - response data
   * @throws \Exception - curl error
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
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, ($method?$method:"POST"));
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
      $headersArr['Content-length']=strlen($postData);
    }else{
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, ($method?$method:"GET"));
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
      curl_close($ch);
      throw new \Exception($exception);
    }
    curl_close($ch);
    return $responseData;
  }
  #endregion funkce pro práci s RESTFUL API

}