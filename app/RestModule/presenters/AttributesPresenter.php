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
 *
 * @package EasyMinerCenter\RestModule\Presenters
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
   * Akce pro vytvoření nového atributu
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
    //aktualizace informace o datových sloupcích
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

    //inicializace formátu
    $format=$datasourceColumn->format;
    if (!$format){
      //TODO implementovat podporu automatického mapování
      $format=$this->metaAttributesFacade->simpleCreateMetaAttributeWithFormatFromDatasourceColumn($datasourceColumn, $currentUser);
      $datasourceColumn->format=$format;
      $this->datasourcesFacade->saveDatasourceColumn($datasourceColumn);
    }

    //vytvoření nového atributu
    $attribute=new Attribute();
    $attribute->metasource=$miner->metasource;
    $attribute->datasourceColumn=$datasourceColumn;
    $attribute->name=$this->minersFacade->prepareNewAttributeName($miner,$inputData['name']);
    $attribute->type=$attribute->datasourceColumn->type;

    if (@$inputData['specialPreprocessing']==Preprocessing::SPECIALTYPE_EACHONE){
      //použití speciálního preprocessingu
      $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($datasourceColumn->format);
    }elseif(!empty($inputData['newPreprocessing'])){
      //vytvoření nového preprocessingu z definičního pole (z inputu)
      $preprocessing=$this->metaAttributesFacade->generateNewPreprocessingFromDefinitionArray($datasourceColumn->format,$inputData['newPreprocessing']);
    }elseif(!empty($inputData['preprocessing'])){
      //nalezení aktuálního preprocessingu
      $preprocessing=$this->metaAttributesFacade->findPreprocessing($inputData['preprocessing']);
    }
    if (!isset($preprocessing) || !($preprocessing instanceof Preprocessing)){
      throw new \InvalidArgumentException('Preprocessing not found or invalid definition of preprocessing given!');
    }

    //máme preprocessing => přiřadíme ho k atributu a necháme ho předzpracovat...
    $attribute->preprocessing=$preprocessing;
    $attribute->active=false;
    $this->metasourcesFacade->saveAttribute($attribute);

    //inicializace preprocessingu
    $metasourceTask=$this->metasourcesFacade->startAttributesPreprocessing($miner->metasource,[$attribute]);
    while($metasourceTask && $metasourceTask->state!=MetasourceTask::STATE_DONE){
      $metasourceTask=$this->metasourcesFacade->preprocessAttributes($metasourceTask);
    }
    //smazání předzpracovávací úlohy
    $this->metasourcesFacade->deleteMetasourceTask($metasourceTask);

    $this->setXmlMapperElements('attribute');
    $this->resource=$attribute->getDataArr();
    $this->sendResource();
  }

  /**
   * Funkce pro validaci vstupních hodnot pro vytvoření nového atributu
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
        //kontrola, jestli je zadán podporovaný typ preprocessingu
        return false;
      }
      if ($preprocessingType==Preprocessing::TYPE_EQUIDISTANT_INTERVALS || $preprocessingType==Preprocessing::TYPE_EQUIFREQUENT_INTERVALS){
        if (!isset($inputData['from']) || !is_numeric($inputData['from'])){return false;}
        if (!isset($inputData['to']) || !is_numeric($inputData['to'])){return false;}
      }
      if ($preprocessingType==Preprocessing::TYPE_EQUIFREQUENT_INTERVALS){
        if (!isset($inputData['count']) || !is_numeric($inputData['count'])){return false;}
      }
      if($preprocessingType==Preprocessing::TYPE_EQUIDISTANT_INTERVALS){
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
 *   )
 * )
 */