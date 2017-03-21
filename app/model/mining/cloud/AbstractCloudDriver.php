<?php

namespace EasyMinerCenter\Model\Mining\Cloud;

use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\XmlSerializersFactory;
use EasyMinerCenter\Model\Mining\Exceptions\MinerCommunicationException;
use Nette\InvalidArgumentException;

/**
 * Class AbstractCloudDriver
 * @package EasyMinerCenter\Model\Mining\Cloud
 * @author Stanislav Vojíř
 */
class AbstractCloudDriver{
  /** @var  Miner $miner */
  protected $miner;
  /** @var  array $minerConfig */
  protected $minerConfig = null;
  /** @var  MinersFacade $minersFacade */
  protected $minersFacade;
  /** @var MetaAttributesFacade $metaAttributesFacade */
  protected $metaAttributesFacade;
  /** @var XmlSerializersFactory $xmlSerializersFactory */
  protected $xmlSerializersFactory;
  /** @var array $params - parametry výchozí konfigurace */
  protected $params;
  /** @var  User $user */
  protected $user;
  /** @var  string $apiKey - API KEY pro konktrétního uživatele */
  protected $apiKey;

  /**
   * AbstractCloudDriver constructor.
   * @param MinersFacade $minersFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param User $user
   * @param XmlSerializersFactory $xmlSerializersFactory
   * @param array $params
   */
  public function __construct(MinersFacade $minersFacade, MetaAttributesFacade $metaAttributesFacade, User $user, XmlSerializersFactory $xmlSerializersFactory, $params = array()) {
    $this->minersFacade=$minersFacade;
    $this->params=$params;
    $this->metaAttributesFacade=$metaAttributesFacade;
    $this->user=$user;
    $this->xmlSerializersFactory=$xmlSerializersFactory;
    $this->setApiKey($user->getEncodedApiKey());
  }

  /**
   * Funkce vracející adresu R connectu
   * @return string
   */
  protected function getRemoteMinerUrl(){
    $minerUrl=trim(@$this->params['minerUrl'],'/');
    return $this->getRemoteServerUrl().($minerUrl!=''?'/'.$minerUrl:'');
  }

  /**
   * Funkce vracející adresu serveru, kde běží R connect
   * @return string
   */
  protected function getRemoteServerUrl(){
    return rtrim(@$this->params['server']);
  }

  /**
   * Funkce vracející konfiguraci aktuálně otevřeného mineru
   * @return array
   */
  protected function getMinerConfig(){
    if (!$this->minerConfig){
      $this->minerConfig=$this->miner->getConfig();
    }
    return $this->minerConfig;
  }

  /**
   * Funkce nastavující konfiguraci aktuálně otevřeného mineru
   * @param array $minerConfig
   * @param bool $save = true
   */
  protected function setMinerConfig($minerConfig,$save=true){
    $this->miner->setConfig($minerConfig);
    $this->minerConfig=$minerConfig;
    if ($save){
      $this->minersFacade->saveMiner($this->miner);
    }
  }

  #region apiKey

  /**
   * Funkce nastavující API klíč
   * @param string $apiKey
   */
  public function setApiKey($apiKey){
    $this->apiKey=$apiKey;
  }

  /**
   * Funkce vracející nastavený API klíč
   * @return string
   * @throws InvalidArgumentException
   */
  public function getApiKey(){
    if (empty($this->apiKey)){
      throw new InvalidArgumentException("Missing API KEY!");
    }
    return $this->apiKey;
  }

  #endregion apiKey

  /**
   * @param string $url
   * @param string|null $postData = ''
   * @param string $method='GET'
   * @param array $headersArr=array()
   * @param string $apiKey = ''
   * @param int|null &$responseCode - proměnná pro vrácení stavového kódu odpovědi
   * @return string - response data
   * @throws MinerCommunicationException - curl error
   */
  protected function curlRequestResponse($url, $postData='', $method='GET', $headersArr=[], $apiKey='', &$responseCode=null){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch,CURLOPT_MAXREDIRS,0);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
    if (empty($headersArr['Content-Type']) & !empty($postData)){
      $headersArr['Content-Type']='application/xml; charset=utf-8';
    }
    if (!empty($apiKey)){
      $headersArr['Authorization']='ApiKey '.$apiKey;
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
      throw new MinerCommunicationException($exception,$responseCode);
    }
    curl_close($ch);
    return $responseData;
  }

  /**
   * Funkce pro kontrolu, jestli je dostupný dolovací server
   *
   * @param string $serverUrl
   * @throws \Exception
   * @return bool
   */
  public static function checkMinerServerState($serverUrl) {
    $response=self::curlRequestResponse($serverUrl);
    return !empty($response);
    //TODO implementace kontroly dostupnosti serveru
  }

}