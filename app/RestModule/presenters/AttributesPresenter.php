<?php
namespace EasyMinerCenter\RestModule\Presenters;
use Drahak\Restful\Validation\IValidator;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\PreprocessingsFacade;

/**
 * Class AttributesPresenter
 *
 * @package EasyMinerCenter\RestModule\Presenters
 */
class AttributesPresenter extends BaseResourcePresenter {
  /** @var  PreprocessingsFacade $preprocessingsFacade */
  private $preprocessingsFacade;
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
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @SWG\Parameter(
   *     name="miner",
   *     description="Miner ID",
   *     required=true,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="name",
   *     description="New attribute name",
   *     required=true,
   *     type="string",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="columnName",
   *     description="Datasource column name",
   *     required=true,
   *     type="string",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="preprocessing",
   *     description="Preprocessing ID",
   *     required=false,
   *     type="integer",
   *     in="query"
   *   ),
   *   @SWG\Parameter(
   *     name="specialPreprocessing",
   *     description="Type of special preprocessing",
   *     required=false,
   *     type="string",
   *     in="query",
   *     enum={"eachOne"}
   *   ),
   *   @SWG\Response(
   *     response=200,
   *     description="Attribute details",
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
    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumnByName($miner->datasource,@$inputData['columnName']);
    //vytvoření nového atributu
    $attribute=new Attribute();
    $attribute->name=$this->minersFacade->prepareNewAttributeName($miner,$inputData['name']);
    $attribute->metasource=$miner->metasource;
    if (@$inputData['specialPreprocessing']==Preprocessing::SPECIALTYPE_EACHONE){
      $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($datasourceColumn->format);
      $attribute->preprocessing=$preprocessing;
    }else{
      //FIXME je nutné nalézt příslušný preprocessing...

    }
    $attribute->datasourceColumn=$datasourceColumn;
    $this->minersFacade->prepareAttribute($miner,$attribute);
    $this->metasourcesFacade->saveAttribute($attribute);
    $this->minersFacade->checkMinerState($miner, $this->getCurrentUser());
    $this->setXmlMapperElements('attribute');
    $this->resource=$attribute->getDataArr();
    $this->sendResource();
  }

  /**
   * Funkce pro validaci vstupních hodnot pro vytvoření nového atributu
   * @throws \Drahak\Restful\Application\BadRequestException
   */
  public function validateCreate() {
    $this->input->field('miner')
      ->addRule(IValidator::REQUIRED,'You have to select miner ID!');
    $this->input->field('columnName')
      ->addRule(IValidator::REQUIRED,'You have to input column name!');
    $this->input->field('name')
      ->addRule(IValidator::REQUIRED,'You have to input attribute name!');
    $this->input->field('preprocessing')
      ->addRule(IValidator::CALLBACK,'Requested preprocessing was not found!',function($value){
        if ($value>0){
          try{
            $this->preprocessingsFacade->findPreprocessing($value);
            //TODO doplnění kontroly, jestli preprocessing patří k danému metaatributu
          }catch (\Exception $e){
            return false;
          }
        }
        return true;
      });
    $this->input->field('specialPreprocessing')
      ->addRule(IValidator::CALLBACK,'Requested special preprocessing does not exist!',function($value){
        return ($value=="")||in_array($value,Preprocessing::getSpecialTypes());
      });
    $inputData=$this->input->getData();
    if (empty($inputData['specialPreprocessing'])){
      $this->input->field('preprocessing')->addRule(IValidator::REQUIRED,'You have to select a preprocessing type or ID!');
    }
  }
  #endregion



  #region injections
  /**
   * @param PreprocessingsFacade $preprocessingsFacade
   */
  public function injectPreprocessingsFacade(PreprocessingsFacade $preprocessingsFacade) {
    $this->preprocessingsFacade=$preprocessingsFacade;
  }
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
 */