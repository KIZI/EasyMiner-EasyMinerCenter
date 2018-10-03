<?php
namespace EasyMinerCenter\RestModule\Presenters;
use Drahak\Restful\Application\BadRequestException;
use Drahak\Restful\NotImplementedException;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\Scoring\IScorerDriver;
use EasyMinerCenter\Model\Scoring\ScorerDriverFactory;

/**
 * Class EvaluationPresenter
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class EvaluationPresenter extends BaseResourcePresenter {
  use TasksFacadeTrait;

  /** @var  ScorerDriverFactory $scorerDriverFactory */
  private $scorerDriverFactory;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;

  /**
   * Action managing the run of a concrete validation method
   * @param string $id - type of validation
   */
  public function actionRead($id) {
    if ($id=='classification'){
      $this->forward('classification');
    }else{
      throw new NotImplementedException();
    }
  }

  /**
   * Action for evaluation of classification model
   * @SWG\Get(
   *   tags={"Evaluation"},
   *   path="/evaluation/classification",
   *   summary="Evaluate classification model",
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @SWG\Parameter(
   *     name="scorer",
   *     description="Scorer type",
   *     required=true,
   *     type="string",
   *     in="query",
   *     enum={"easyMinerScorer","modelTester"}
   *   ),
   *   @SWG\Parameter(
   *     name="task",
   *     description="Task ID",
   *     required=false,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="ruleSet",
   *     description="Rule set ID",
   *     required=false,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="datasource",
   *     description="Datasource ID (if not specified, task datasource will be used)",
   *     required=false,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Evaluation result",
   *     @SWG\Schema(
   *       ref="#/definitions/ScoringResultResponse"
   *     )
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid API key supplied",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   )
   * )
   *
   * @throws BadRequestException
   * @throws NotImplementedException
   */
  public function actionClassification() {
    $this->setXmlMapperElements('classification');
    $inputData=$this->getInput()->getData();
    /** @var IScorerDriver $scorerDriver */
    if (empty($inputData['scorer'])){
      $scorerDriver=$this->scorerDriverFactory->getDefaultScorerInstance();
    }else{
      $scorerDriver=$this->scorerDriverFactory->getScorerInstance($inputData['scorer']);
    }
    if (!empty($inputData['datasource'])){
      try{
        $datasource=$this->datasourcesFacade->findDatasource(@$inputData['datasource']);
        if (!$this->datasourcesFacade->checkDatasourceAccess($datasource,$this->getCurrentUser())){
          throw new \Exception();
        }
      }catch (\Exception $e){
        throw new BadRequestException("Requested data source was not found!");
      }
    }elseif(!empty($inputData['task'])){
      $task=$this->findTaskWithCheckAccess($inputData['task']);
      $datasource=$task->miner->datasource;
    }else{
      throw new BadRequestException("Data source was not specified!");
    }

    if (!empty($inputData['task'])){
      if (empty($task)){
        $task=$this->findTaskWithCheckAccess($inputData['task']);
      }
      $result=$scorerDriver->evaluateTask($task,$datasource)->getCorrectIncorrectDataArr();
      $result['task']=$task->getDataArr(false);
    }elseif(!empty($inputData['ruleSet'])){
      $ruleSet=$this->ruleSetsFacade->findRuleSet($inputData['ruleSet']);
      $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->getCurrentUser());
      $result=$scorerDriver->evaluateRuleSet($ruleSet,$datasource)->getDataArr();
      $result['ruleSet']=$ruleSet->getDataArr();
    }else{
      throw new BadRequestException("No task or rule set found!");
    }
    $this->resource=$result;
    $this->sendResource();
  }

  #region injections
  /**
   * @param ScorerDriverFactory $scorerDriverFactory
   */
  public function injectScorerDriverFactory(ScorerDriverFactory $scorerDriverFactory) {
    $this->scorerDriverFactory=$scorerDriverFactory;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade) {
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }
  #endregion injections
}

/**
 * @SWG\Definition(
 *   definition="ScoringResultResponse",
 *   title="ScoringResult",
 *   required={"rowCount","correct","incorrect","unclassified"},
 *   @SWG\Property(property="rowCount",type="integer",description="Count of rows"),
 *   @SWG\Property(property="correct",type="integer",description="True classifications"),
 *   @SWG\Property(property="incorrect",type="integer",description="False classifications"),
 *   @SWG\Property(property="unclassified",type="integer",description="False classifications"),
 *   @SWG\Property(
 *      property="task",
 *      description="Task details",
 *      ref="#/definitions/TaskSimpleResponse"
 *   ),
 *   @SWG\Property(
 *      property="ruleSet",
 *      description="Rule set details",
 *      ref="#/definitions/RuleSetResponse"
 *   ),
 * )
 */