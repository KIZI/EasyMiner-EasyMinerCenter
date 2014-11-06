<?php
namespace App\Model\Mining\LM;


use App\Model\Data\Entities\DbConnection;
use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Model\Mining\IMiningDriver;
use Kdyby\Curl\Request as CurlRequest;
use Kdyby\Curl\Response as CurlResponse;
use Tracy\Debugger;

class LMDriver implements IMiningDriver{
  /** @var  Task $task */
  private $task;
  /** @var  Miner $miner */
  private $miner;
  /** @var  array $minerConfig */
  private $minerConfig = null;
  /** @var  MinersFacade $minersFacade */
  private $minersFacade;
  /** @var array $params - parametry výchozí konfigurace */
  private $params;


  /**
   * Funkce pro definování úlohy na základě dat z EasyMineru
   * @param string $taskConfigJson
   */
  public function startMining($taskConfigJson) {
    // TODO: Implement startMining() method.
  }

  /**
   * Funkce pro zastavení dolování
   * @return bool
   */
  public function stopMining() {
    try{
      $this->cancelRemoteMinerTask($this->getRemoteMinerTaskName());
      return true;
    }catch (\Exception $e){}
    return false;
  }

  /**
   * Funkce vracející info o aktuálním stavu dané úlohy
   * @return string
   */
  public function taskState() {
    // TODO: Implement taskState() method.
  }

  /**
   * Funkce pro načtení výsledků z DM nástroje a jejich uložení do DB
   */
  public function importResults() {
    // TODO: Implement importResults() method.
  }

  /**
   * Funkce volaná po smazání konkrétního mineru
   * @return mixed
   */
  public function deletedMiner() {
    // TODO: Implement deletedMiner() method.
  }

  /**
   * Funkce pro kontrolu konfigurace daného mineru (včetně konfigurace atributů...)
   * @throws \Exception
   */
  public function checkMinerState(){
    $minerConfig=$this->miner->getConfig();
    $lmId=$minerConfig['lm_miner'];
    // TODO: Implement checkMinerState() method.
  }

  /**
   * Funkce vracející ID aktuálního vzdáleného mineru (lispmineru)
   * @return null|string
   */
  private function getRemoteMinerId(){
    $minerConfig=$this->getMinerConfig();
    if (isset($minerConfig['lm_miner_id'])){
      return $minerConfig['lm_miner_id'];
    }else{
      return null;
    }
  }

  /**
   * Funkce nastavující ID aktuálně otevřeného mineru
   * @param string|null $lmMinerId
   */
  private function setRemoteMinerId($lmMinerId){
    $minerConfig=$this->getMinerConfig();
    $minerConfig['lm_miner_id']=$lmMinerId;
    $this->setMinerConfig($minerConfig);
  }

  /**
   * Funkce vracející adresu LM connectu
   * @return string
   */
  private function getRemoteMinerUrl(){
    //FIXME
  }

  /**
   * Funkce vracející typ požadovaného LM pooleru
   * @return string
   */
  private function getRemoteMinerPooler(){
    //FIXME
    return 'task';
  }

  /**
   * Funkce vracející jméno úlohy na LM connectu
   * @return string
   */
  private function getRemoteMinerTaskName(){
    //FIXME
  }

  /**
   * @return string
   */
  private function getAttributesTableName(){
    //FIXME
  }

  /**
   * Funkce vracející konfiguraci aktuálně otevřeného mineru
   * @return array
   */
  private function getMinerConfig(){
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
  private function setMinerConfig($minerConfig,$save=true){
    $this->miner->setConfig($minerConfig);
    $this->minerConfig=$minerConfig;
    if ($save){
      $this->minersFacade->saveMiner($this->miner);
    }
  }


  #region kód z KBI ==================================================================================================

  /**
   * @param DbConnection $dbConnection
   * @return string ID of registered miner.
   * @throws \Exception
   */
  private function registerRemoteMiner(DbConnection $dbConnection) {
    /** @var \SimpleXMLElement $requestXml */
    $requestXml = simplexml_load_string('<RegistrationRequest></RegistrationRequest>');

    /*if (isset($db_cfg['metabase'])) { //nastavení existující metabáze
      $metabaseXml = $requestXml->addChild('Metabase');
      $metabaseXml->addAttribute('type', 'Access');
      $metabaseXml->addChild('File', $db_cfg['metabase']);
    }*/

    if ($dbConnection->type!='mysql'){
      throw new \Exception('LMDriver supports only MySQL databases!');
    }

    $connectionXml = $requestXml->addChild('Connection');
    $connectionXml->addAttribute('type', $dbConnection->type);

    if ($dbConnection->dbPort!='') {
      $connectionXml->addChild('Server', $dbConnection->dbServer.':'.$dbConnection->dbPort);
    }else {
      $connectionXml->addChild('Server', $dbConnection->dbServer);
    }

    $connectionXml->addChild('Database', $dbConnection->dbName);

    if (!empty($dbConnection->dbUsername)) {
      $connectionXml->addChild('Username', $dbConnection->dbUsername);
    }
    if (!empty($dbConnection->dbUsername)) {
      $connectionXml->addChild('Username', $dbConnection->dbUsername);
    }

    if (!empty($dbConnection->dbPassword)) {
      $connectionXml->addChild('Password', $dbConnection->dbPassword);
    }

    $data = $requestXml->asXML();

    $url = $this->getRemoteMinerUrl().'/miners';

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response=$curlRequest->post($data);

    Debugger::fireLog($response);

    return $this->parseRegisterResponse($response);
  }

  /**
   * @param CurlResponse $response
   * @return string
   * @throws \Exception
   */
  private function parseRegisterResponse(CurlResponse $response) {
    $body = simplexml_load_string($response->getResponse());

    if ($response->getCode() != 200 || $body['status'] == 'failure') {
      throw new \Exception(isset($body->message) ? (string)$body->message : $response->getCode());
    } else if ($body['status'] == 'success') {
      return (string)$body['id'];
    }

    throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response)));
  }

  /**
   * Funkce pro smazání vzdáleného mineru
   * @return mixed
   */
  private function unregisterRemoteMiner() {
    $url = $this->getRemoteMinerUrl()."/miners/".$this->getRemoteMinerId();

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response=$curlRequest->delete();

    return $this->parseResponse($response, "Miner unregistered/removed.");
  }

  /**
   * Funkce pro import DataDictionary do LispMineru
   * @param string|\SimpleXMLElement $dataDictionary
   * @return string
   * @throws \Exception
   */
  private function importDataDictionary($dataDictionary) {
    if ($dataDictionary instanceof \SimpleXMLElement){
      $dataDictionary=$dataDictionary->asXML();
    }
    $remoteMinerId=$this->getRemoteMinerId();

    if(!$remoteMinerId){
      throw new \Exception('LISpMiner ID was not provided.');
    }

    $url = $this->getRemoteMinerUrl().'/miners/'.$remoteMinerId.'/DataDictionary';

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response = $curlRequest->put($dataDictionary);

    Debugger::fireLog($response, "Import executed");

    return $this->parseResponse($response);
  }

  /**
   * @param string $template
   * @return string
   * @throws \Exception
   */
  public function getDataDescription($template = '') {
    $remoteMinerId = $this->getRemoteMinerId();

    if (!$remoteMinerId) {
      throw new \Exception('LISpMiner ID was not provided.');
    }

    $url = $this->getRemoteMinerUrl().'/miners/'.$remoteMinerId.'/DataDictionary';

    $requestData = array(
      'matrix' => $this->getAttributesTableName(),
      'template' => $template
    );

    Debugger::fireLog(array($url, $requestData), "getting DataDictionary");

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response=$curlRequest->get($requestData);

    if ($response->isOk()) {
      return trim($response->getResponse());
    }

    return $this->parseResponse($response);
  }

  public function queryPost($query, $options) {//TODO query a options???
    $remoteMinerId = $this->getRemoteMinerId();

    if (!$remoteMinerId){
      throw new \Exception('LISpMiner ID was not provided.');
    }

    $url = $this->getRemoteMinerUrl();

    $data = array();

    if (isset($options['template'])) {
      $data['template'] = $options['template'];
      Debugger::fireLog("Using LM exporting template {$data['template']}");
    }

    if (isset($options['export'])) {
      $task = $options['export'];
      $url .= '/miners/'.$remoteMinerId.'/tasks/'.$task;

      Debugger::fireLog("Making just export of task '{$task}' (no generation).");
      Debugger::fireLog(array('URL' => $url, 'GET' => $data, 'POST' => $query));

      $curlRequest = $this->prepareNewCurlRequest($url);
      $response=$curlRequest->get($data);
    } else {
      $pooler = $this->getRemoteMinerPooler();

      if (isset($options['pooler'])) {
        $pooler = $options['pooler'];
        Debugger::log("Using '{$pooler}' as pooler");
      }

      switch ($pooler) {
        case 'grid':
          $url .= '/miners/'.$remoteMinerId.'/tasks/grid';
          break;
        case 'proc':
          $url .= '/miners/'.$remoteMinerId.'/tasks/proc';
          break;
        case 'task':
        default:
          $url .= '/miners/'.$remoteMinerId.'/tasks/task';
      }

      Debugger::log(array('URL' => $url, 'GET' => $data, 'POST' => $query));

      $curlRequest=$this->prepareNewCurlRequest($url);
      $curlRequest->getUrl()->appendQuery($options);//TODO kontrola připojení parametrů k URL...
      $response = $curlRequest->post($url, $query);
    }

    if ($response->isOk()) {
      return $response->getResponse();
    }

    return $this->parseResponse($response);
  }

  /**
   * Funkce pro zastavení úlohy na LM connectu
   * @param string $taskName
   * @return string
   * @throws \Exception
   */
  public function cancelRemoteMinerTask($taskName) {
    /** @var \SimpleXMLElement $requestXml */
    $requestXml = simplexml_load_string("<CancelationRequest></CancelationRequest>");
    $remoteMinerId = $this->getRemoteMinerId();

    if (!$remoteMinerId) {
      throw new \Exception('LISpMiner ID was not provided.');
    }

    $url = $this->getRemoteMinerUrl();

    switch ($this->getRemoteMinerPooler()) {
      case 'grid':
        $url .= '/miners/'.$remoteMinerId.'/tasks/grid/'.$taskName;
        break;
      case 'proc':
        $url .= '/miners/'.$remoteMinerId.'/tasks/proc/'.$taskName;
        break;
      case 'task':
      default:
        $url .= '/miners/'.$remoteMinerId.'/tasks/task/'.$taskName;
    }

    Debugger::fireLog(array($url, 'Canceling task'));

    $curlRequest=$this->prepareNewCurlRequest($url);
    $response=$curlRequest->put($requestXml->asXML());

    if ($response->isOk()) {
      return $response->getResponse();
    }

    return $this->parseResponse($response);
  }

  /**
   * Funkce pro otestování existence LM connect mineru
   * @return bool
   */
  private function test(){
    try {
      $remoteMinerId = $this->getRemoteMinerId();
      if (!$remoteMinerId) {
        throw new \Exception('LISpMiner ID was not provided.');
      }
      $url = $this->getRemoteMinerUrl().'/miners/'.$remoteMinerId;

      $curlRequest=$this->prepareNewCurlRequest($url);
      $response=$curlRequest->get();

      Debugger::fireLog($response, "Test executed");

      $this->parseResponse($response, '');

      return true;
    } catch (\Exception $ex) {
      return false;
    }
  }

  /**
   * Funkce parsující stav odpovědi od LM connectu
   * @param CurlResponse $response
   * @param string $message
   * @return string
   * @throws \Exception
   */
  private function parseResponse($response, $message = '') {
    $body = $response->getResponse();
    $body=simplexml_load_string($body);
    if (!$response->isOk() || $body['status'] == 'failure') {
      throw new \Exception(isset($body->message) ? (string)$body->message : $response->getCode());
    } else if ($body['status'] == 'success') {
      return isset($body->message) ? (string)$body->message : $message;
    }
    throw new \Exception(sprintf('Response not in expected format (%s)', htmlspecialchars($response->getResponse())));
  }

  /**
   * @param $url
   * @return CurlRequest
   */
  private function prepareNewCurlRequest($url){
    return new CurlRequest($url);//TODO credentials!!!
  }


  #endregion kód z KBI ===============================================================================================

  #region constructor
  /**
   * @param Task $task
   * @param MinersFacade $minersFacade
   * @param $params = array()
   */
  public function __construct(Task $task = null, MinersFacade $minersFacade, $params = array()) {
    $this->minersFacade=$minersFacade;
    $this->setTask($task);
    $this->params=$params;
  }

  /**
   * Funkce pro nastavení aktivní úlohy
   * @param Task $task
   * @return mixed
   */
  public function setTask(Task $task) {
    $this->task=$task;
  }

  #endregion constructor


}