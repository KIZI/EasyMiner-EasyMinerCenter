<?php

namespace EasyMinerCenter\RestModule\Presenters;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use Nette\Application\BadRequestException;

/**
 * Class MinersPresenter - presenter pro práci s jednotlivými minery
 * @package EasyMinerCenter\RestModule\Presenters
 */
class MinersPresenter extends BaseResourcePresenter{
  /** @var  MinersFacade $minersFacade */
  private $minersFacade;

  /**
   * Funkce vracející detaily konkrétního mineru či seznam minerů
   * @param int|null $id = null
   * @throws BadRequestException
   * @SWG\Get(
   *   tags={"Miners"},
   *   path="/miners/{id}",
   *   summary="Get miner details",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Miner ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response="200",
   *     description="Miner details.",
   *     @SWG\Schema(ref="#/definitions/MinerResponse")
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionRead($id=null){
    $this->setXmlMapperElements('miner');
    if (empty($id)){$this->forward('list');return;}
    $miner=$this->findMinerWithCheckAccess($id);
    $this->resource=$miner->getDataArr();
    $this->sendResource();
  }

  /**
   * Funkce vracející seznam minerů aktuálně přihlášeného uživatele
   * @SWG\Get(
   *   tags={"Miners"},
   *   path="/miners",
   *   summary="Get list of miners for the current user",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Response(
   *     response="200",
   *     description="List of miners",
   *     @SWG\Schema(
   *       type="array",
   *       @SWG\Items(
   *         ref="#/definitions/MinerBasicResponse"
   *       )
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionList() {
    $this->setXmlMapperElements('miners','miner');
    $currentUser=$this->getCurrentUser();
    $miners=$this->minersFacade->findMinersByUser($currentUser);
    $result=[];
    if (!empty($miners)){
      foreach ($miners as $miner){
        $result[]=[
          'id'=>$miner->minerId,
          'name'=>$miner->name,
          'type'=>$miner->type
        ];
      }
    }
    $this->resource=$result;
    $this->sendResource();
  }

  /**
   * Akce vracející seznam úloh asociovaných s vybraným minerem
   * @param $id
   * @throws BadRequestException
   *
   * @SWG\Get(
   *   tags={"Miners"},
   *   path="/miners/{id}/tasks",
   *   summary="Get list of tasks in the selected miner",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json","application/xml"},
   *   @SWG\Parameter(
   *     name="id",
   *     description="Miner ID",
   *     required=true,
   *     type="integer",
   *     in="path"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="List of tasks",
   *     @SWG\Schema(
   *       @SWG\Property(property="miner",ref="#/definitions/MinerBasicResponse"),
   *       @SWG\Property(
   *         property="task",
   *         type="array",
   *         @SWG\Items(ref="#/definitions/TaskBasicResponse")
   *       )
   *     )
   *   ),
   *   @SWG\Response(response=404, description="Requested miner was not found.")
   * )
   */
  public function actionReadTasks($id) {
    $this->setXmlMapperElements('miner');
    if (empty($id)){$this->forward('list');return;}
    $miner=$this->findMinerWithCheckAccess($id);

    $result=[
      'miner'=>[
        'id'=>$miner->minerId,
        'name'=>$miner->name,
        'type'=>$miner->type
      ],
      'task'=>[]
    ];
    $tasks=$miner->tasks;
    if (!empty($tasks)){
      foreach ($tasks as $task){
        $result['task'][]=[
          'id'=>$task->taskId,
          'name'=>$task->name,
          'state'=>$task->state,
          'rulesCount'=>$task->rulesCount
        ];
      }
    }
    $this->resource=$result;
    $this->sendResource();
  }




  /**
   * @param int $minerId
   * @return null|Miner
   * @throws \Nette\Application\BadRequestException
   */
  private function findMinerWithCheckAccess($minerId){
    try{
      /** @var Miner $miner */
      $miner=$this->minersFacade->findMiner($minerId);
    }catch (EntityNotFoundException $e){
      $this->error('Requested miner was not found.');
      return null;
    }
    $this->minersFacade->checkMinerAccess($miner,$this->getCurrentUser());
    return $miner;
  }

  #region injections
  /**
   * @param MinersFacade $minersFacade
   */
  public function injectMinersFacade(MinersFacade $minersFacade){
    $this->minersFacade=$minersFacade;
  }
  #endregion injections
}

/**
 * @SWG\Definition(
 *   definition="MinerResponse",
 *   title="Miner",
 *   required={"id","name","type","datasourceId","metasourceId","ruleSetId"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the miner"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the miner"),
 *   @SWG\Property(property="type",type="string",description="Miner type",enum={"r","lm"}),
 *   @SWG\Property(property="datasourceId",type="integer",description="ID of used datasource"),
 *   @SWG\Property(property="metasourceId",type="integer",description="ID of used metasource"),
 *   @SWG\Property(property="ruleSetId",type="integer",description="ID of rule set associated with the miner"),
 *   @SWG\Property(property="created",type="dateTime",description="DateTime of miner creation"),
 *   @SWG\Property(property="lastOpened",type="dateTime",description="DateTime of miner last open action")
 * )
 * @SWG\Definition(
 *   definition="MinerBasicResponse",
 *   title="MinerBasicInfo",
 *   required={"id","name","type"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the miner"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the miner"),
 *   @SWG\Property(property="type",type="string",description="Miner type",enum={"r","lm"})
 * )
 * @SWG\Definition(
 *   definition="TaskBasicResponse",
 *   title="TaskBasicInfo",
 *   required={"id","name","state"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the task"),
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the task"),
 *   @SWG\Property(property="state",type="string",description="State of the task"),
 *   @SWG\Property(property="rulesCount",type="integer",description="Count of founded rules")
 * )
 */