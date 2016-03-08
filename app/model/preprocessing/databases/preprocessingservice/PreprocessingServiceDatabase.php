<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Entities\PpAttribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpDataset;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class PreprocessingServiceDatabase - třída zajišťující přístup k databázím dostupným prostřednictvím služby EasyMiner-Preprocessing
 *
 * @package EasyMinerCenter\Model\Data\Databases\DataService
 * @author Stanislav Vojíř
 */
abstract class PreprocessingServiceDatabase implements IPreprocessing {
  /** @var  string $apiKey */
  private $apiKey;
  /** @var  PpConnection $ppConnection */
  private $ppConnection;

  //TODO implement

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
   * @throws EntityNotFoundException
   */
  public function getPpDataset($ppDatasetId) {
    try{
      $responseData=$this->curlRequestResponse($this->getRequestUrl('/dataset/'.$ppDatasetId), null, 'GET', ['Accept'=>'application/json; charset=utf8'], $responseCode);
      $responseData=Json::decode($responseData, Json::FORCE_ARRAY);

      if(!empty($responseData) && ($responseCode==200)) {
        return new PpDataset($responseData['id'], $responseData['name'], $responseData['dataSource'], $responseData['type'], $responseData['size']);
      }else{
        throw new \Exception('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new EntityNotFoundException('Requested PpDataset was not found.');
    }
  }

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param PpDataset $ppDataset
   * @return PpAttribute[]
   * @throws EntityNotFoundException
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
        throw new \Exception('responseCode: '.$responseCode);
      }
    }catch (\Exception $e){
      throw new EntityNotFoundException('Requested DbDatasource was not found.');
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