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
use Nette\NotImplementedException;

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
   * @throws \InvalidArgumentException
   */
  public function actionCreate() {
    throw new NotImplementedException();//FIXME implementovat
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
