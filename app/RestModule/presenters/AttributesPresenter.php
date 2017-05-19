<?php
namespace EasyMinerCenter\RestModule\Presenters;
use Drahak\Restful\InvalidArgumentException;
use Drahak\Restful\Validation\IValidator;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\MetasourceTask;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;

/**
 * Class AttributesPresenter
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class AttributesPresenter extends BaseResourcePresenter {
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;

  use MinersFacadeTrait;

  #region actionCreate
  /**
   * Action for creating a new attribute
   * @SWG\Post(
   *   tags={"Attributes"},
   *   path="/attributes",
   *   summary="Create new attribute using defined preprocessing",
   *   consumes={"application/json","application/xml"},
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @SWG\Parameter(
   *     description="New attribute",
   *     name="body",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/NewAttributeInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="Attribute created",
   *     @SWG\Schema(
   *       ref="#/definitions/AttributeResponse"
   *     )
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid API key supplied",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   )
   * )
   * @throws \InvalidArgumentException
   */
  public function actionCreate() {
    /** @var array $inputData */
    $inputData=$this->input->getData();
    $miner=$this->findMinerWithCheckAccess(@$inputData['miner']);
    $this->minersFacade->checkMinerMetasource($miner);

    $currentUser=$this->getCurrentUser();
    //update list of datasource columns
    $this->datasourcesFacade->updateDatasourceColumns($miner->datasource,$currentUser);

    try{
      if (!empty($inputData['column'])){
        $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($miner->datasource,@$inputData['column']);
      }else{
        $datasourceColumn=$this->datasourcesFacade->findDatasourceColumnByName($miner->datasource,@$inputData['columnName']);
      }
    }catch (\Exception $e){
      throw new InvalidArgumentException("Datasource columns was not found: ".@$inputData['columnName']);
    }

    //initialize the Format
    $format=$datasourceColumn->format;
    if (!$format){
      //TODO implementovat podporu automatického mapování
      $format=$this->metaAttributesFacade->simpleCreateMetaAttributeWithFormatFromDatasourceColumn($datasourceColumn, $currentUser);
      $datasourceColumn->format=$format;
      $this->datasourcesFacade->saveDatasourceColumn($datasourceColumn);
    }

    //creation of a new Attribute
    $attribute=new Attribute();
    $attribute->metasource=$miner->metasource;
    $attribute->datasourceColumn=$datasourceColumn;
    $attribute->name=$this->minersFacade->prepareNewAttributeName($miner,$inputData['name']);
    $attribute->type=$attribute->datasourceColumn->type;

    if (@$inputData['specialPreprocessing']==Preprocessing::SPECIALTYPE_EACHONE){
      //use special mapping
      $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($datasourceColumn->format);
    }elseif(!empty($inputData['newPreprocessing'])){
      //create new preprocessing from definition array [from input]
      $preprocessing=$this->metaAttributesFacade->generateNewPreprocessingFromDefinitionArray($datasourceColumn->format,$inputData['newPreprocessing']);
    }elseif(!empty($inputData['preprocessing'])){
      //fing actual preprocessing
      $preprocessing=$this->metaAttributesFacade->findPreprocessing($inputData['preprocessing']);
    }
    if (!isset($preprocessing) || !($preprocessing instanceof Preprocessing)){
      throw new \InvalidArgumentException('Preprocessing not found or invalid definition of preprocessing given!');
    }

    //we have preprocessing => attach it to the attribute and let it preprocess...
    $attribute->preprocessing=$preprocessing;
    $attribute->active=false;
    $this->metasourcesFacade->saveAttribute($attribute);

    //preprocessing initialization
    $metasourceTask=$this->metasourcesFacade->startAttributesPreprocessing($miner->metasource,[$attribute]);
    while($metasourceTask && $metasourceTask->state!=MetasourceTask::STATE_DONE){
      $metasourceTask=$this->metasourcesFacade->preprocessAttributes($metasourceTask);
    }
    //delete finished preprocessing task
    $this->metasourcesFacade->deleteMetasourceTask($metasourceTask);

    $this->setXmlMapperElements('attribute');
    $this->resource=$attribute->getDataArr();
    $this->sendResource();
  }

  /**
   * Method for validation of input values for actionCreate()
   * @throws \Drahak\Restful\Application\BadRequestException
   */
  public function validateCreate() {
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('miner')
      ->addRule(IValidator::REQUIRED,'You have to select miner ID!');
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('columnName')
      ->addRule(IValidator::CALLBACK,'You have to input column name or ID!',function(){
      $inputData=$this->input->getData();
      return (@$inputData['columnName']!="" || $inputData['column']>0);
    });
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('name')
      ->addRule(IValidator::REQUIRED,'You have to input attribute name!');
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('preprocessing')
      ->addRule(IValidator::CALLBACK,'Requested preprocessing was not found!',function($value){
        if ($value>0){
          try{
            $this->metaAttributesFacade->findPreprocessing($value);
            //TODO doplnění kontroly, jestli preprocessing patří k danému metaatributu
          }catch (\Exception $e){
            return false;
          }
        }
        return true;
      });
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('specialPreprocessing')
      ->addRule(IValidator::CALLBACK,'Requested special preprocessing does not exist!',function($value){
        return ($value=="")||in_array($value,Preprocessing::getSpecialTypes());
      });
    $inputData=$this->input->getData();

    $newPreprocessingValidation=function(){
      $inputData=$this->input->getData();
      if (empty($inputData['newPreprocessing'])){
        return false;
      }else{
        $inputData=$inputData['newPreprocessing'];
      }
      $preprocessingType=Preprocessing::decodeAlternativePrepreprocessingTypeIdentification(@$inputData['type']);
      if (!in_array($preprocessingType,Preprocessing::getPreprocessingTypes())){
        //check, if there is a supported type of preprocessing
        return false;
      }
      if ($preprocessingType==Preprocessing::TYPE_EQUIFREQUENT_INTERVALS){
        if (!isset($inputData['count']) || !is_numeric($inputData['count'])){return false;}
      }
      if ($preprocessingType==Preprocessing::TYPE_EQUISIZED_INTERVALS){
        if (!isset($inputData['support']) || !is_numeric($inputData['support'])){return false;}
      }
      if($preprocessingType==Preprocessing::TYPE_EQUIDISTANT_INTERVALS){
        if (!isset($inputData['from']) || !is_numeric($inputData['from'])){return false;}
        if (!isset($inputData['to']) || !is_numeric($inputData['to'])){return false;}
        if ((!isset($inputData['count']) || !is_numeric($inputData['count']))&&(empty($inputData['length']) || !is_numeric($inputData['length']))){return false;}
      }
      return true;
    };

    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $this->input->field('newPreprocessing')
      ->addRule(IValidator::CALLBACK,'Invalid definition of new preprocessing!',$newPreprocessingValidation);

    if (empty($inputData['specialPreprocessing'])){
      if (!empty($inputData['newPreprocessing'])){
        if (!$newPreprocessingValidation()){
          $this->error('Invalid new preprocessing config.');
        }
      }else{
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->input->field('preprocessing')->addRule(IValidator::REQUIRED, 'You have to select a preprocessing type or ID!');
      }
    }
  }
  #endregion



  #region injections
  /**
   * @param MetasourcesFacade $metasourcesFacade
   */
  public function injectMetasourcesFacade(MetasourcesFacade $metasourcesFacade) {
    $this->metasourcesFacade=$metasourcesFacade;
  }
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade){
    $this->datasourcesFacade=$datasourcesFacade;
  }
  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function injectMetaAttributesFacade(MetaAttributesFacade $metaAttributesFacade) {
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
  #endregion injections
}

/**
 * @SWG\Definition(
 *   definition="AttributeResponse",
 *   title="AttributeBasicInfo",
 *   required={"id","name","preprocessing"},
 *   @SWG\Property(property="id",type="integer",description="Unique ID of the datasource"),
 *   @SWG\Property(property="name",type="string",description="Name of the attribute"),
 *   @SWG\Property(property="preprocessing",type="integer",description="ID of preprocessing"),
 *   @SWG\Property(
 *     property="column",
 *     description="Details of the datasource column",
 *     @SWG\Property(property="id",type="integer",description="ID of datasource column"),
 *     @SWG\Property(property="name",type="string",description="Name of datasource column"),
 *     @SWG\Property(property="type",type="string",description="Type of datasource column"),
 *     @SWG\Property(property="format",type="integer",description="ID of format")
 *   )
 * )
 * @SWG\Definition(
 *   definition="NewAttributeInput",
 *   title="New attribute",
 *   required={"miner","name"},
 *   @SWG\Property(
 *     property="miner",
 *     description="Miner ID",
 *     type="integer",
 *   ),
 *   @SWG\Property(
 *     property="name",
 *     description="New attribute name",
 *     type="string"
 *   ),
 *   @SWG\Property(
 *     property="column",
 *     description="Datasource column ID",
 *     type="integer"
 *   ),
 *   @SWG\Property(
 *     property="columnName",
 *     description="Datasource column name",
 *     type="string"
 *   ),
 *   @SWG\Property(
 *     property="preprocessing",
 *     description="Preprocessing ID",
 *     type="integer"
 *   ),
 *   @SWG\Property(
 *     property="specialPreprocessing",
 *     description="Type of special preprocessing",
 *     type="string",
 *     enum={"eachOne"}
 *   ),
 *   @SWG\Property(
 *     property="newPreprocessing",
 *     description="Definition of new preprocessing - <a href='./swagger/examples'>see examples</a>"
 *   )
 * )
 */