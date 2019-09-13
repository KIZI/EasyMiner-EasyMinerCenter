<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\TasksFacade;
use EasyMinerCenter\Model\EasyMiner\Serializers\AssociationRulesXmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Serializers\PlainTextRuleSerializer;
use EasyMinerCenter\Model\EasyMiner\Transformators\XmlTransformator;
use EasyMinerCenter\Model\Scoring\IScorerDriver;
use EasyMinerCenter\Model\Scoring\ScorerDriverFactory;
use Nette\Application\BadRequestException;
use Nette\InvalidArgumentException;

/**
 * Class RuleSetsPresenter - presenter pro práci s rulesety
 * @package EasyMinerCenter\KnowledgeBaseModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class RuleSetsPresenter extends BasePresenter{
  use MinersFacadeTrait;
  use ResponsesTrait;
  use UsersTrait;

  /** @var TasksFacade $tasksFacade */
  private $tasksFacade;
  /** @var DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var  XmlTransformator $xmlTransformator */
  private $xmlTransformator;
  /** @var ScorerDriverFactory $scorerDriverFactory */
  private $scorerDriverFactory;


  /**
   * Action for serialization of Rules in the RuleSet in DRL form
   * @param int $id - id of the RuleSet
   * @throws \Exception
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function renderDRL($id){
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //generate DRL and send it as text response
    $associationRulesXmlSerializer=new AssociationRulesXmlSerializer($ruleSet->findRules());
    $xml=$associationRulesXmlSerializer->getXml();
    //FIXME implementovat $this->sendXmlResponse($xml);return;
    $this->sendTextResponse($this->xmlTransformator->transformToDrl($xml));
  }

  /**
   * Action for serialization of Rules in the RuleSet in plaintext form
   * @param int $id - id of the RuleSet
   * @param string|null $order
   * @throws \Exception
   * @throws \Nette\Application\BadRequestException
   */
  public function renderText($id, $order=null){
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $user=$this->getCurrentUser();
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$user);
    $rules=$this->ruleSetsFacade->findRulesByRuleSet($ruleSet,$order);
    //serialize result and send it
    $result=PlainTextRuleSerializer::serialize($rules,$user,$ruleSet);
    $this->sendTextResponse($result);
  }

  /**
   * Action for list of existing RuleSets
   */
  public function actionList(){
    $ruleSets=$this->ruleSetsFacade->findRuleSetsByUser($this->user->id);
    $result=[];
    if (empty($ruleSets)) {
      //if no RuleSet found, create a new one...
      $ruleSet=new RuleSet();
      $user=$this->usersFacade->findUser($this->user->id);
      $ruleSet->user=$user;
      $ruleSet->name=$user->name;
      $this->ruleSetsFacade->saveRuleSet($ruleSet);
      $result[$ruleSet->ruleSetId]=$ruleSet->getDataArr();
    }else{
      foreach ($ruleSets as $ruleSet){
        $result[$ruleSet->ruleSetId]=$ruleSet->getDataArr();
      }
    }
    $this->sendJsonResponse($result);
  }

  #region actions for manipulation with a RuleSet

  /**
   * Action for creation of a new RuleSet (with the given name)
   * @param string $name
   * @param string $description=""
   * @throws InvalidArgumentException
   */
  public function actionNew($name, $description=""){
    $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($name,$this->user->id);
    //create new RuleSet
    $ruleSet=new RuleSet();
    $ruleSet->name=$name;
    $ruleSet->description=$description;
    $ruleSet->user=$this->usersFacade->findUser($this->user->id);
    $this->ruleSetsFacade->saveRuleSet($ruleSet);
    //send the response
    $this->sendJsonResponse($ruleSet->getDataArr());
  }

  /**
   * Action for renaming of an existing RuleSet (description change is also supported)
   * @param int $id
   * @param string $name
   * @param string $description=""
   */
  public function actionRename($id,$name,$description=""){
    //find the RuleSet and check it
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($name,$this->user->id,$ruleSet);
    //change the name and description and save it
    $ruleSet->name=$name;
    $ruleSet->description=$description;
    $this->ruleSetsFacade->saveRuleSet($ruleSet);
    //send the response
    $this->sendJsonResponse($ruleSet->getDataArr());
  }

  /**
   * Action for deleting of a RuleSet
   * @param int $id
   */
  public function actionDelete($id){
    //find RuleSet and check it
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //delete the RuleSet and send confirmation response
    if ($this->ruleSetsFacade->deleteRuleSet($ruleSet)){
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  #endregion actions for manipulation with a RuleSet

  #region actions for manipulation of relations between Rules and RuleSets

  /**
   * Action returning one concrete RuleSet with basic list of Rules
   * @param int $id
   * @param int $offset
   * @param int $limit
   * @param string|null $order = null
   */
  public function actionGetRules($id,$offset=0,$limit=25,$order=null){
    //find RuleSet and check it
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //prepare the result
    $result=[
      'ruleset'=>$ruleSet->getDataArr(),
      'rules'=>[]
    ];
    if ($ruleSet->rulesCount>0 || true){
      $rules=$this->ruleSetsFacade->findRulesByRuleSet($ruleSet,$order,$offset,$limit);
      if (!empty($rules)){
        foreach($rules as $rule){
          $result['rules'][]=$rule->getBasicDataArr();
        }
      }
    }
    $this->sendJsonResponse($result);
  }

  /**
   * Action for adding of Rules to a RuleSet
   * @param int $id
   * @param string|int $rules - ID of Rules, separated with commas or semicolons
   * @param string $relation = 'positive'
   * @param string $result = "simple" (varianty "simple", "rules")
   */
  public function actionAddRules($id,$rules,$relation=RuleSetRuleRelation::RELATION_POSITIVE, $result="simple"){
    //find RuleSet and check it
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    /** @var int[] $ruleIdsArr */
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    if (!empty($ruleIdsArr)){
      foreach($ruleIdsArr as $ruleId){
        if (!$ruleId){continue;}
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          $this->ruleSetsFacade->addRuleToRuleSet($rule,$ruleSet,$relation);
        }catch (\Exception $e){continue;}
      }
    }
    if ($result=="rules"){
      $result=[
        'ruleset'=>$ruleSet->getDataArr(),
        'rules'=>[]
      ];
      $result['rules']=$this->prepareRulesResult($ruleIdsArr,$ruleSet);
      $this->sendJsonResponse($result);
    }else{
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  /**
   * Action for removing of Rules from a RuleSet
   * @param int $id
   * @param string|int $rules
   * @param string $result = "simple" (varianty "simple", "rules")
   */
  public function actionRemoveRules($id, $rules, $result="simple"){
    //find RuleSet and check it
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    /** @var int[] $ruleIdsArr */
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    if (!empty($ruleIdsArr)){
      foreach($ruleIdsArr as $ruleId){
        if (!$ruleId){continue;}
        try{
          //$rule=$this->rulesFacade->findRule($ruleId);
          $this->ruleSetsFacade->removeRuleFromRuleSet($ruleId,$ruleSet);
        }catch (\Exception $e){continue;}
      }
    }
    $this->ruleSetsFacade->updateRuleSetRulesCount($ruleSet);

    if ($result=="rules"){
      $result=[
        'ruleset'=>$ruleSet->getDataArr(),
        'rules'=>[]
      ];
      $result['rules']=$this->prepareRulesResult($ruleIdsArr, $ruleSet);
      $this->sendJsonResponse($result);
    }else{
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  /**
   * Action for adding the full list of task results (all rules) to rule clipboard
   * @param int $id
   * @param string $returnRules ='' - IDs of rules, separated with commas (or a single ID)
   * @param string $result
   * @param int $task
   * @throws \Exception
   */
  public function actionAddAllTaskRules($id=null,$relation=RuleSetRuleRelation::RELATION_POSITIVE, $task, $returnRules=null){
    $task=$this->tasksFacade->findTask($task);
    $this->checkMinerAccess($task->miner);

    //find RuleSet and check it
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);

    if (!empty($task->rules)){
      foreach ($task->rules as $rule){
        $this->ruleSetsFacade->addRuleToRuleSet($rule, $ruleSet, $relation);
      }
    }

    if (!empty($returnRules)){
      $ruleIdsArr=explode(',',str_replace(';',',',$returnRules));
      $result=[
        'ruleset'=>$ruleSet->getDataArr(),
        'rules'=>[]
      ];
      $result['rules']=$this->prepareRulesResult($ruleIdsArr,$ruleSet);

      $this->sendJsonResponse($result);
    }else{
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  /**
   * Action for removing of all Rules from the given RuleSet
   * @param int $id
   */
  public function actionRemoveAllRules($id){
    //find RuleSet and check it
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //remove the relations
    $this->ruleSetsFacade->removeAllRulesFromRuleSet($ruleSet);
    $this->sendJsonResponse(['state'=>'ok']);
  }

  /**
   * Method for preparation of an array with information about selected Rules for sending in a JSON response
   * @param int[] $ruleIdsArr
   * @param RuleSet|int $ruleSet
   * @return array
   */
  private function prepareRulesResult($ruleIdsArr, $ruleSet=null){
    $result=[];
    if (!empty($ruleIdsArr)) {
      foreach ($ruleIdsArr as $ruleId) {
        if (!$ruleId) {
          continue;
        }
        try {
          $rule = $this->rulesFacade->findRule($ruleId);
          $result[$rule->ruleId]=$rule->getBasicDataArr();
          if (!empty($ruleSet)){
            $relation=$rule->getRuleSetRelation($ruleSet);
            if (!empty($relation)){
              $result[$rule->ruleId]['ruleSetRelation']=$relation->relation;
            }else{
              $result[$rule->ruleId]['ruleSetRelation']='';
            }
          }
        }catch (\Exception $e){continue;}
      }
    }
    return $result;
  }

  #endregion actions for manipulation of relations between Rules and RuleSets

  /**
   * Action for selection of datasource for model tester
   * @param int $id - ruleset id
   * @param string $scorer = ''
   * @throws \Nette\Application\BadRequestException
   */
  public function renderScorerSelectDatasource($id, $scorer=''){
    try{
      $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    }catch (\Exception $e){
      throw new BadRequestException();
    }
    $currentUser=$this->getCurrentUser();
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$currentUser);

    $this->layout='iframe';
    $this->template->layout='iframe';
    $this->template->scorer=$scorer;
    $this->template->ruleSet=$ruleSet;
    $this->template->datasources=$this->datasourcesFacade->findDatasourcesByUser($currentUser, true);
  }

  /**
   * @param int $id
   * @param null $datasource =null
   * @param string $scorer = ''
   * @throws \Nette\Application\AbortException
   * @throws BadRequestException
   */
  public function renderScorer($id, $datasource=null, $scorer=''){
    #region find ruleset and datasource
    if (!($datasource)){
      $this->forward('scorerSelectDatasource',['id'=>$id,'scorer'=>$scorer]);
    }
    $currentUser=$this->getCurrentUser();
    try{
      $datasource=$this->datasourcesFacade->findDatasource($datasource);
    }catch (\Exception $e){
      $this->forward('scorerSelectDatasource',['id'=>$id,'scorer'=>$scorer]);
    }
    $this->datasourcesFacade->checkDatasourceAccess($datasource,$currentUser);
    try{
      $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    }catch (\Exception $e){
      throw new BadRequestException();
    }
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$currentUser);
    #endregion find ruleset and datasource
    //run scorer and show results
    /** @var IScorerDriver $scorerDriver */
    if (empty($inputData['scorer'])){
      $scorerDriver=$this->scorerDriverFactory->getDefaultScorerInstance();
    }else{
      $scorerDriver=$this->scorerDriverFactory->getScorerInstance($scorer);
    }

    $this->layout='iframe';
    $this->template->layout='iframe';
    $this->template->ruleSet=$ruleSet;
    $this->template->datasource=$datasource;
    $this->template->scoringResult=$scorerDriver->evaluateRuleSet($ruleSet,$datasource);
  }

  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade){
    $this->datasourcesFacade=$datasourcesFacade;
  }
  /**
   * @param TasksFacade $tasksFacade
   */
  public function injectTasksFacade(TasksFacade $tasksFacade){
    $this->tasksFacade=$tasksFacade;
  }
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param XmlTransformator $xmlTransformator
   */
  public function injectXmlTransformator(XmlTransformator $xmlTransformator){
    $this->xmlTransformator=$xmlTransformator;
    //nastaven basePath
    $this->xmlTransformator->setBasePath($this->template->basePath);
  }
  /**
   * @param ScorerDriverFactory $scorerDriverFactory
   */
  public function injectScorerDriverFactory(ScorerDriverFactory $scorerDriverFactory){
    $this->scorerDriverFactory=$scorerDriverFactory;
  }
  #endregion injections
}