<?php

namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\InvalidStateException;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\TasksFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\GuhaPmmlSerializer;
use Nette\Application\BadRequestException;

/**
 * Class TasksPresenter - presenter pro práci s jednotlivými úlohami
 * @package EasyMinerCenter\RestModule\Presenters
 */
class TasksPresenter extends BaseResourcePresenter {
  /** @var  TasksFacade $tasksFacade */
  private $tasksFacade;
  /** @var  MinersFacade $minersFacade */
  private $minersFacade;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;


  /**
   * Akce vracející PMML data konkrétní úlohy
   * @param $id
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Tasks"},
   *   path="/tasks/{id}/pmml",
   *   summary="Get PMML export of the task",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Task ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Task PMML",
   *     @SWG\Schema(type="xml")
   *   ),
   *   @SWG\Response(response=404, description="Requested task was not found."),
   *   @SWG\Response(response=500, description="Task has not been solved.")
   * )
   */
  public function actionReadPmml($id){
    $task=$this->findTaskWithCheckAccess($id);

    if ($task->state!=Task::STATE_SOLVED){
      throw new InvalidStateException("Task has not been solved!");
    }

    $pmml=$this->prepareTaskPmml($task);
    $this->sendXmlResponse($pmml);
  }

  /**
   * @param $task
   * @return \SimpleXMLElement
   */
  private function prepareTaskPmml(Task $task){
    //TODO refaktorovat - zároveň je totožná konstrukce použita v TaskPresenteru v modulu EasyMiner
    /** @var Metasource $metasource */
    $metasource=$task->miner->metasource;
    $this->databasesFacade->openDatabase($metasource->getDbConnection());
    $pmmlSerializer=new GuhaPmmlSerializer($task,null,$this->databasesFacade);
    $pmmlSerializer->appendTaskSettings();
    $pmmlSerializer->appendDataDictionary();
    $pmmlSerializer->appendTransformationDictionary();
    $pmmlSerializer->appendRules();
    return $pmmlSerializer->getPmml();
  }


  /**
   * Funkce pro nalezení úlohy dle zadaného ID a kontrolu oprávnění aktuálního uživatele pracovat s daným pravidlem
   *
   * @param int $taskId
   * @return Task
   * @throws \Nette\Application\BadRequestException
   */
  private function findTaskWithCheckAccess($taskId){
    try{
      /** @var Task $task */
      $task=$this->tasksFacade->findTask($taskId);
    }catch (EntityNotFoundException $e){
      $this->error('Requested task was not found.');
      return null;
    }
    $this->minersFacade->checkMinerAccess($task->miner,$this->getCurrentUser());
    return $task;
  }


  #region injections
  /**
   * @param TasksFacade $tasksFacade
   */
  public function injectTasksFacade(TasksFacade $tasksFacade) {
    $this->tasksFacade=$tasksFacade;
  }
  /**
   * @param MinersFacade $minersFacade
   */
  public function injectMinersFacade(MinersFacade $minersFacade) {
    $this->minersFacade=$minersFacade;
  }
  /**
   * @param DatabasesFacade $databasesFacade
   */
  public function injectDatabasesFacade(DatabasesFacade $databasesFacade) {
    $this->databasesFacade=$databasesFacade;
  }
  #endregion injections
}
