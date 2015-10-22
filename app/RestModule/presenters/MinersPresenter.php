<?php

namespace EasyMinerCenter\RestModule\Presenters;
use Drahak\Restful\Validation\IValidator;
use EasyMinerCenter\Exceptions\EntityNotFoundException;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\Format;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MinersFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

/**
 * Class MinersPresenter - presenter pro práci s jednotlivými minery
 * @package EasyMinerCenter\RestModule\Presenters
 */
class MinersPresenter extends BaseResourcePresenter{
  use MinersFacadeTrait;

  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;

  /** @var null|Datasource $datasource */
  private $datasource=null;
  /** @var null|RuleSet $ruleSet */
  private $ruleSet=null;

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
   * Akce vracející seznam minerů aktuálně přihlášeného uživatele
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
   *   )
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

  #region actionCreate
  /**
   * Akce pro vytvoření nového uživatelského účtu na základě zaslaných hodnot
   * @SWG\Post(
   *   tags={"Miners"},
   *   path="/miners",
   *   summary="Create new miner",
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   produces={"application/json", "application/xml"},
   *   consumes={"application/json", "application/xml"},
   *   @SWG\Parameter(
   *     description="Miner",
   *     name="miner",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/MinerInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="Miner created successfully, returns details of Miner.",
   *     @SWG\Schema(ref="#/definitions/MinerResponse")
   *   ),
   *   @SWG\Response(
   *     response=422,
   *     description="Sent values are not acceptable!",
   *     @SWG\Schema(ref="#/definitions/InputErrorResponse")
   *   )
   * )
   */
  public function actionCreate(){
    //prepare Miner from input values
    /** @var Miner $miner */
    $miner=new Miner();
    /** @var array $data */
    $data=$this->input->getData();
    $miner->name=$data['name'];
    $miner->type=$data['type'];
    $miner->datasource=$this->datasource;
    $this->fixMetabaseMappings($this->datasource);
    if (!empty($this->ruleSet)){
      $miner->ruleSet=$this->ruleSet;
    }
    $miner->created=new \DateTime();
    if ($user=$this->getCurrentUser()){
      $miner->user=$user;
    }
    $this->minersFacade->saveMiner($miner);
    //send response
    $this->actionRead($miner->minerId);
  }

  /**
   * Funkce pro kontrolu vstupů pro vytvoření nového mineru
   */
  public function validateCreate() {
    $currentUser=$this->getCurrentUser();
    $this->input->field('name')->addRule(IValidator::REQUIRED,'Name is required!');
    $this->input->field('type')
      ->addRule(IValidator::REQUIRED,'Miner type is required!')
      ->addRule(IValidator::CALLBACK,'You have to select a configured miner type!',function($value){
        $minerTypesArr=$this->minersFacade->getAvailableMinerTypes();
        return isset($minerTypesArr[$value]);
      });
    $this->input->field('datasourceId')
      ->addRule(IValidator::REQUIRED,'Datasource ID is required!')
      ->addRule(IValidator::CALLBACK,'Requested datasource was not found, or is not accessible!', function($value)use($currentUser){
          try{
            $this->datasource=$this->datasourcesFacade->findDatasource($value);
            return $this->datasourcesFacade->checkDatasourceAccess($this->datasource,$currentUser);
          }catch (\Exception $e){
            return false;
          }
        });
    $this->input->field('ruleSetId')
      ->addRule(IValidator::CALLBACK,'Requested rule set was not found, or is not accessible!',function($value)use($currentUser){
        if (empty($value)){return true;}
        try{
          $this->ruleSet=$this->ruleSetsFacade->findRuleSet($value);
          $this->ruleSetsFacade->checkRuleSetAccess($this->ruleSet,$currentUser);
          return true;
        }catch (\Exception $e){
          return false;
        }
      });
  }
  #endregion actionCreate


  /**
   * Funkce pro zajištění automatického vytvoření formátů pro nahraná data
   * @param Datasource $datasource
   * TODO refaktorovat (zároveň je tento kód v DataPresenteru v EasyMiner modulu)
   */
  private function fixMetabaseMappings(Datasource $datasource) {
    $currentUserId=$this->getCurrentUser()->userId;
    //kontrola, jestli je daný datový zdroj namapován na knowledge base
    if (!$this->datasourcesFacade->checkDatasourceColumnsFormatsMappings($datasource,true)){
      $this->databasesFacade->openDatabase($datasource->getDbConnection());
      foreach ($datasource->datasourceColumns as $datasourceColumn){
        if (empty($datasourceColumn->format)){
          //automatické vytvoření formátu
          $metaAttribute=$this->metaAttributesFacade->findOrCreateMetaAttributeWithName(Strings::lower($datasourceColumn->name));
          $existingFormats=$this->metaAttributesFacade->findFormatsForUser($metaAttribute,$currentUserId);
          $existingFormatNames=[];
          if (!empty($existingFormats)){
            foreach ($existingFormats as $format){
              $existingFormatNames[]=$format->name;
            }
          }
          $basicFormatName=str_replace('-','_',Strings::webalize($datasource->dbTable));
          $i=1;
          do{
            $formatName=$basicFormatName.($i>1?'_'.$i:'');
            $i++;
          }while(in_array($formatName,$existingFormatNames));
          $datasourceColumnValuesStatistic=$this->databasesFacade->getColumnValuesStatistic($datasource->dbTable,$datasourceColumn->name);
          $formatType=($datasourceColumn->type==DatasourceColumn::TYPE_STRING?Format::DATATYPE_VALUES:Format::DATATYPE_INTERVAL);
          $format=$this->metaAttributesFacade->createFormatFromDatasourceColumn($metaAttribute,$formatName,$datasourceColumn,$datasourceColumnValuesStatistic,$formatType,false,$currentUserId);
          $datasourceColumn->format=$format;
          $this->datasourcesFacade->saveDatasourceColumn($datasourceColumn);
        }
      }
    }
  }

  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade) {
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function injectMetaAttributesFacade(MetaAttributesFacade $metaAttributesFacade) {
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
  /**
   * @param DatabasesFacade $databasesFacade
   */
  public function injectDatabasesFacade(DatabasesFacade $databasesFacade) {
    $this->databasesFacade=$databasesFacade;
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
 *   @SWG\Property(property="type",type="string",description="Type of data mining backend",enum={"r","lm"})
 * )
 * @SWG\Definition(
 *   definition="MinerInput",
 *   title="Miner",
 *   required={"name","type","datasourceId"},
 *   @SWG\Property(property="name",type="string",description="Human-readable name of the miner"),
 *   @SWG\Property(property="type",type="string",description="Type of data mining backend",enum={"r","lm"}),
 *   @SWG\Property(property="datasourceId",type="integer",description="ID of existing datasource"),
 *   @SWG\Property(property="ruleSetId",type="integer",description="ID of existing rule set (optional)"),
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