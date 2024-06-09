<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\TaskState;
use EasyMinerCenter\Model\EasyMiner\Repositories\TasksRepository;

/**
 * Class TasksFacade
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class TasksFacade {
  /** @var  TasksRepository $tasksRepository */
  private $tasksRepository;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

  /**
   * TasksFacade constructor.
   * @param TasksRepository $tasksRepository
   * @param RulesFacade $rulesFacade
   */
  public function __construct(TasksRepository $tasksRepository, RulesFacade $rulesFacade){
    $this->tasksRepository=$tasksRepository;
    $this->rulesFacade=$rulesFacade;
  }

  /**
   * @param $id
   * @return Task
   * @throws \Exception
   */
  public function findTask($id){
    return $this->tasksRepository->find($id);
  }

  /**
   * @param Miner|int $miner
   * @param string|string[]|null $state=null
   * @return int
   */
  public function findTasksByMinerCount($miner,$state=null){
    $paramsArr=['miner_id'=>(($miner instanceof Miner)?$miner->minerId:$miner)];
    if(!empty($state)){
      if (is_array($state)){
        $paramsArr[]=['state IN (?)',$state];
      }else{
        $paramsArr['state']=$state;
      }
    }
    return $this->tasksRepository->findCountBy($paramsArr);
  }

  /**
   * @param Miner|int $miner
   * @param string|string[]|null $state=null
   * @param string|null $order=null
   * @param int|null $offset=null
   * @param int|null $limit=null
   * @return Task[]
   */
  public function findTasksByMiner($miner, $state=null, $order=null, $offset=null, $limit=null){
    $paramsArr=['miner_id'=>(($miner instanceof Miner)?$miner->minerId:$miner)];
    if (!empty($order)){
      $paramsArr['order']=$order;
    }
    if(!empty($state)){
      if (is_array($state)){
        $paramsArr[]=['state IN (?)',$state];
      }else{
        $paramsArr['state']=$state;
      }
    }
    return $this->tasksRepository->findAllBy($paramsArr,$offset,$limit);
  }

  /**
   * @param Task $task
   * @return mixed
   */
  public function saveTask(Task &$task){
    if (empty($task->created)){
      $task->created=new \DateTime();
    }
    return $this->tasksRepository->persist($task);
  }

  /**
   * Method for deleting of data mining Task including also connected Rules
   * @param Task $task
   */
  public function deleteTask(Task $task){
    //TODO implement deleteTask()

  }

  /**
   * @param Task $task
   * @param TaskState $taskState
   */
  public function updateTaskState(Task &$task,TaskState $taskState){
    /** @var Task $task*/
    $task=$this->findTask($task->taskId);
    //process the info about the TaskState
    if (!empty($taskState->rulesCount) && $taskState->rulesCount>$task->rulesCount){
      $task->rulesCount=$taskState->rulesCount;
    }

    //state of the solving of the Task
    if (($task->state!=Task::STATE_SOLVED)&&($task->state!=$taskState->state)){
      $task->state=$taskState->state;
    }

    //URL s results
    $task->resultsUrl=$taskState->resultsUrl;

    //gradual import of results
    if ($task->importState!=Task::IMPORT_STATE_DONE){
      if ($taskState->importState!=null && $taskState->importState!=$task->importState) {
        $task->importState=$taskState->importState;
      }

      #region resolving of the array importData
      $importData=array_merge($task->getImportData(),$taskState->importData);
      if (!empty($importData)){
        foreach($importData as $key=>$filename){
          if (!file_exists($filename)){
            unset($importData[$key]);
          }
        }
      }
      if (empty($importData)&&$task->isMiningFinished()){
        $task->importState=Task::IMPORT_STATE_DONE;
      }
      $task->setImportData($importData);
      #endregion resolving of the array importData

    }elseif($taskState->importState==Task::IMPORT_STATE_DONE && $task->importState!=Task::IMPORT_STATE_DONE){
      $task->importState=Task::IMPORT_STATE_DONE;
      $task->setImportData([]);
    }


    if ($task->isModified()){
      $this->saveTask($task);
    }
  }

  /**
   * Method for check, if the given Task is included in Rule Clipboard
   * @param Task $task
   */
  public function checkTaskInRuleClipoard(Task &$task){
    $rulesCount=$this->rulesFacade->getRulesCountByTask($task,true);
    if ($rulesCount!=$task->rulesInRuleClipboardCount){
      $task->rulesInRuleClipboardCount=$rulesCount;
      $this->saveTask($task);
    }
  }

  /**
   * Method for preparation of a Task before send of it to the Miner
   * @param Miner $miner
   * @param int|null $id = null
   * @return Task
   */
  public function prepareTask(Miner $miner, $id=null){
    try{
      $task=$this->findTask($id);
      return $task;
    }catch (\Exception $e){/*the Task probably does not exist*/}
    $task=new Task();
    $task->miner=$miner;
    $task->type=$miner->type;
    $task->state=Task::STATE_NEW;
    $this->saveTask($task);

    return $task;
  }

  /**
   * Method for preparation of a data mining Task based on a simple array with configuration (for example via the API)
   * @param Miner $miner
   * @param array $settingsArr
   * @param Task|null $updateTask=null
   * @return Task
   * @throws \InvalidArgumentException
   */
  public function prepareSimpleTask(Miner $miner, $settingsArr, Task $updateTask=null) {
    //prepare apropriate task...
    if ($updateTask instanceof Task){
      $task = $updateTask;
      if ($task->miner->minerId != $miner->minerId){
        throw new \InvalidArgumentException('Invalid combination of miner and task!');
      }
    }else{
      $task = new Task();
    }
    //prepare task settings from $settings
    if (isset($settingsArr['succedent'])&&!isset($settingsArr['consequent'])){
      $settingsArr['consequent']=$settingsArr['succedent'];
    }
    $taskSettings=[
      'limitHits'=>max(1, @$settingsArr['limitHits']),
      'rule0'=>[
        'id'=>0,
        'groupFields'=>true,
        'antecedent'=>[
          'type'=>'cedent',
          'connective'=>[
            'id'=> 2,
            'name'=>'AND',
            'type'=>'and'
          ],
          'level'=>1,
          'children'=>$this->prepareSimpleTaskAttributesArr(@$settingsArr['antecedent'])
        ],
        'IMs'=>$this->prepareSimpleTaskIMsArr(@$settingsArr['IMs']),
        'specialIMs'=>$this->prepareSimpleTaskSpecialIMsArr(@$settingsArr['specialIMs']),
        'succedent'=>[
          'type'=>'cedent',
          'connective'=>[
            'id'=> 2,
            'name'=>'AND',
            'type'=>'and'
          ],
          'level'=>1,
          'children'=>$this->prepareSimpleTaskAttributesArr($settingsArr['consequent'])
        ]
      ],
      'rules'=>1,
      'debug'=>false,
      'strict'=>false,
      'taskMode'=>'task',
      'taskName'=>$settingsArr['name'],
    ];

    //configure task object
    $task->miner=$miner;
    $task->type=$miner->type;
    $task->name=$settingsArr['name'];
    $task->state=Task::STATE_NEW;
    $task->setTaskSettings($taskSettings);
    return $task;
  }

  /**
   * Method for preparation of the structure of configuration interest measures in the Task configuration using an array with simple config
   * @param array $IMsSettingsArr
   * @return array
   */
  private function prepareSimpleTaskIMsArr($IMsSettingsArr) {
    $result=[];
    if (empty($IMsSettingsArr)){return $result;}
    foreach($IMsSettingsArr as $IMSettings){ //TODO KIZI/EasyMiner-EasyMinerCenter#104
      if (!in_array($IMSettings['name'],['CONF','AAD','LIFT','SUPP','AUTO_CONF_SUPP','RULE_LENGTH'])){ //TODO kontrola podporovaných kombinací měr zajímavosti
        throw new \InvalidArgumentException('Unsupported interest measure: '.$IMSettings['name']);
      }
      $result[]=[
        'name'=> $IMSettings['name'],
        'localizedName'=>$IMSettings['name'],//TODO opravit na lokalizovaný název
        'thresholdType'=>'% of all',
        'compareType'=>'Greater than or equal',
        'fields'=>[
          [
            'name'=>'threshold',
            'value'=>@$IMSettings['value']
          ]
        ],
        'threshold'=>@$IMSettings['value'],
        'alpha'=>0
      ];
    }
    return $result;
  }

  /**
   * Method for preparation of the structure of configuration special interest measures in the Task configuration using an array with simple config
   * @param array $specialIMsSettingsArr
   * @return array
   */
  private function prepareSimpleTaskSpecialIMsArr($specialIMsSettingsArr) {
    $result=[];
    if (empty($specialIMsSettingsArr)){return $result;}
    foreach($specialIMsSettingsArr as $IMSettings){
      if (!in_array($IMSettings['name'],['CBA'])){ //TODO kontrola podporovaných kombinací měr zajímavosti
        throw new \InvalidArgumentException('Unsupported interest measure: '.$IMSettings['name']);
      }
      $result[]=[
        'name'=> $IMSettings['name'],
        'localizedName'=>$IMSettings['name'],//TODO opravit na lokalizovaný název
        'thresholdType'=>'% of all',
        'compareType'=>'Greater than or equal',
        'fields'=>[
          [
            'name'=>'threshold',
            'value'=>@$IMSettings['value']
          ]
        ],
        'threshold'=>@$IMSettings['value'],
        'alpha'=>0
      ];
    }
    return $result;
  }

  /**
   * Method for preparation of the structure of a Cedent in the Task configuration using an array with simple config
   * @param array $attributesSettingsArr
   * @return array
   */
  private function prepareSimpleTaskAttributesArr($attributesSettingsArr) {
    $result=[];
    if (empty($attributesSettingsArr)){return $result;}
    //prepare the structure for individual attributes
    foreach($attributesSettingsArr as $attributeSetting){
      if (!empty($attributeSetting['fixedValue'])){
        //fixed value => ONE CATEGORY
        $result[]=[
          'name'=>@$attributeSetting['attribute'],
          'category'=>'One category',
          'ref'=>@$attributeSetting['attribute'],
          'fields'=>[
            [
              'name'=>'category',
              'value'=>$attributeSetting['fixedValue']
            ]
          ],
          'sign'=>'positive'
        ];
      }else{
        //* => SUBSET 1-1
        $result[]=[
          'name'=>@$attributeSetting['attribute'],
          'category'=>'Subset',
          'ref'=>@$attributeSetting['attribute'],
          'fields'=>[
            [
              'name'=>'minLength',
              'value'=>1
            ],
            [
              'name'=>'maxLength',
              'value'=>1
            ]
          ],
          'sign'=>'positive'
        ];
      }
    }
    return $result;
  }

}