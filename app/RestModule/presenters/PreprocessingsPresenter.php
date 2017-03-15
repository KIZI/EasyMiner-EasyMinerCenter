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
use EasyMinerCenter\Model\EasyMiner\Facades\PreprocessingsFacade;

/**
 * Class PreprocessingsPresenter
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 */
class PreprocessingsPresenter extends BaseResourcePresenter {
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
   *   tags={"Preprocessing"},
   *   path="/preprocessings",
   *   summary="Create new preprocessing definition",
   *   consumes={"application/json","application/xml"},
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}},{"apiKeyHeader":{}}},
   *   @SWG\Parameter(
   *     description="New preprocessing",
   *     name="body",
   *     required=true,
   *     @SWG\Schema(ref="#/definitions/NewPreprocessingInput"),
   *     in="body"
   *   ),
   *   @SWG\Response(
   *     response=201,
   *     description="Preprocessing created",
   *     @SWG\Schema(
   *       ref="#/definitions/PreprocessingResponse"
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
      $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($datasourceColumn->format);
      $attribute->preprocessing=$preprocessing;
    }else{
      throw new \BadMethodCallException('Selected preprocessing type is not supported.');
      //FIXME je nutné nalézt příslušný preprocessing...
    }
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
    //TODO kontrola vstupních parametrů
    /*
    $this->input->field('miner')
      ->addRule(IValidator::REQUIRED,'You have to select miner ID!');
    $this->input->field('columnName')
      ->addRule(IValidator::CALLBACK,'You have to input column name or ID!',function(){
      $inputData=$this->input->getData();
      return (@$inputData['columnName']!="" || $inputData['column']>0);
    });
    $this->input->field('name')
      ->addRule(IValidator::REQUIRED,'You have to input attribute name!');
    $this->input->field('preprocessing')
      ->addRule(IValidator::CALLBACK,'Requested preprocessing was not found!',function($value){
        if ($value>0){
          try{
            $this->preprocessingsFacade->findPreprocessing($value);
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
    }*/
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
