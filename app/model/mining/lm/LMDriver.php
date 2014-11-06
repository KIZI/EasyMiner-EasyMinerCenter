<?php
namespace App\Model\Mining\LM;


use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Entities\Task;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Model\Mining\IMiningDriver;

class LMDriver implements IMiningDriver{
  /** @var  Task $task */
  private $task;
  /** @var  Miner $miner */
  private $miner;
  /** @var  array $minerConfig */
  private $minerConfig = null;
  /** @var  MinersFacade $minersFacade */
  private $minersFacade;


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
    // TODO: Implement stopMining() method.
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
   * Funkce pro kontrolu konfigurace daného mineru (včetně konfigurace atributů...)
   * @param Miner|Task $miner
   * @throws \Exception
   */
  public function checkMinerState($miner){
    if ($miner instanceof Task){
      $this->task=$miner;
      $this->miner=$miner->miner;
    }elseif($miner instanceof Miner){
      $this->miner=$miner;
    }else{
      $this->miner=$this->minersFacade->findMiner($miner);
    }

    $minerConfig=$miner->getConfig();
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

  /**
   * @param Task $task
   * @param MinersFacade $minersFacade
   */
  public function __construct(Task $task = null, MinersFacade $minersFacade) {
    $this->minersFacade=$minersFacade;
    $this->setTask($task);
  }

  /**
   * Funkce pro nastavení aktivní úlohy
   * @param Task $task
   * @return mixed
   */
  public function setTask(Task $task) {
    $this->task=$task;
  }


}