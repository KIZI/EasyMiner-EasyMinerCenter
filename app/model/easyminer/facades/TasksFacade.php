<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;


use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\TaskState;
use EasyMinerCenter\Model\EasyMiner\Repositories\TasksRepository;
use Nette\Utils\FileSystem;

class TasksFacade {
  /** @var  TasksRepository $tasksRepository */
  private $tasksRepository;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;

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
   * @param string $taskUuid
   * @return Task
   * @throws \Exception
   */
  public function findTaskByUuid($miner,$taskUuid){
    return $this->tasksRepository->findBy(array('miner_id'=>(($miner instanceof Miner)?$miner->minerId:$miner),'task_uuid'=>$taskUuid));
  }

  /**
   * @param Task $task
   * @return mixed
   */
  public function saveTask(Task &$task){
    return $this->tasksRepository->persist($task);
  }

  /**
   * @param Task $task
   * @param TaskState $taskState
   */
  public function updateTaskState(Task &$task,TaskState $taskState){
    /** @var Task $task - aktualizujeme data o konkrétní úloze*/
    $task=$this->findTask($task->taskId);
    //zpracování info o stavu úlohy
    if (!empty($taskState->rulesCount) && $taskState->rulesCount>$task->rulesCount){
      $task->rulesCount=$taskState->rulesCount;
    }

    //stav řešení úlohy
    if (($task->state!=Task::STATE_SOLVED)&&($task->state!=$taskState->state)){
      $task->state=$taskState->state;
    }

    //URL s výsledky
    $task->resultsUrl=$taskState->resultsUrl;

    //postupný import výsledků
    if ($task->importState!=Task::IMPORT_STATE_DONE){
      if ($taskState->importState!=null && $taskState->importState!=$task->importState) {
        $task->importState=$taskState->importState;
      }

      #region vyřešení pole importData
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
      #endregion vyřešení pole importData

    }elseif($taskState->importState==Task::IMPORT_STATE_DONE && $task->importState!=Task::IMPORT_STATE_DONE){
      $task->importState=Task::IMPORT_STATE_DONE;
      $task->setImportData([]);
    }


    if ($task->isModified()){
      $this->saveTask($task);
    }
  }

  /**
   * Funkce pro kontrolu, jestli je zvolená úloha obsažená v Rule Clipboard
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
   * Funkce pro uložení úlohy s daným uuid (než se odešle mineru...)
   * @param Miner $miner
   * @param string $taskUuid
   * @return \EasyMinerCenter\Model\EasyMiner\Entities\Task
   */
  public function prepareTaskWithUuid(Miner $miner,$taskUuid){
    try{
      $task=$this->findTaskByUuid($miner,$taskUuid);
      return $task;
    }catch (\Exception $e){/*úloha pravděpodobně neexistuje...*/}
    $task=new Task();
    $task->taskUuid=$taskUuid;
    $task->miner=$miner;
    $task->type=$miner->type;
    $task->state=Task::STATE_NEW;
    $this->saveTask($task);

    return $task;
  }

  /**
   * Funkce pro připravení úlohy na základě jednoduchého pole s konfigurací (například přes API)
   *
*@param Miner $miner
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
    $taskUuid=uniqid();
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
      'taskId'=>$taskUuid
    ];

    //configure task object
    $task->miner=$miner;
    $task->type=$miner->type;
    $task->name=$settingsArr['name'];
    $task->taskUuid=$taskUuid;
    $task->state=Task::STATE_NEW;
    $task->setTaskSettings($taskSettings);
    return $task;
  }

  /**
   * Funkce pro připravení struktury nastavení měr zajímavosti v nastavení úlohy dle pole s jednoduchou konfigurací
   * @param array $IMsSettingsArr
   * @return array
   */
  private function prepareSimpleTaskIMsArr($IMsSettingsArr) {
    $result=[];
    if (empty($IMsSettingsArr)){return $result;}
    foreach($IMsSettingsArr as $IMSettings){
      if (!in_array($IMSettings['name'],['FUI','AAD','LIFT','SUPP','AUTO_FUI_SUPP'])){ //TODO kontrola podporovaných kombinací měr zajímavosti
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
   * Funkce pro připravení struktury nastavení měr zajímavosti v nastavení úlohy dle pole s jednoduchou konfigurací
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
   * Funkce pro připravení struktury cedentu v nastavení úlohy dle pole s jednoduchou konfigurací
   * @param array $attributesSettingsArr
   * @return array
   */
  private function prepareSimpleTaskAttributesArr($attributesSettingsArr) {
    $result=[];
    if (empty($attributesSettingsArr)){return $result;}
    //připravíme strukturu pro jednotlivé atributy
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