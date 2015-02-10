<?php

namespace App\EasyMinerModule\Presenters;


use App\Model\EasyMiner\Entities\Attribute;
use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\EasyMiner\Facades\MetasourcesFacade;
use App\Model\EasyMiner\Facades\MetaAttributesFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Neon\Exception;

class AttributesPresenter extends BasePresenter{

  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;

  /**
   * @var string $mode
   * @persistent
   */
  public $mode='default';

  /**
   * @param int $miner
   * @param int $column
   * @param string $preprocessing
   */
  public function renderShowPreprocessing($miner, $column, $preprocessing){
    //TODO
  }

  /**
   * Funkce pro pouřití preprocessingu each value - one category
   * @param int $miner
   * @param int $column
   * @throws BadRequestException
   */
  public function renderNewPreprocessingEachOne($miner, $column){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $format=$datasourceColumn->format;
    //kontrola, jestli už existuje preprocessing tohoto typu
    $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($format);//TODO
    $this->template->preprocessing=$preprocessing;
    /** @var Form $form */
    $form=$this->getComponent('newAttributeForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'preprocessing'=>$preprocessing->preprocessingId,
      'attributeName'=>$datasourceColumn->name
    ));
  }

  /**
   * Akce pro vytvoření atributu na základě existujícího preprocesingu
   * @param int $miner
   * @param int $column
   * @param string $preprocessing
   * @throws BadRequestException
   */
  public function renderNewAttribute($miner,$column,$preprocessing){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $preprocessing=$this->metaAttributesFacade->findPreprocessing($preprocessing);
    if ($datasourceColumn->format->formatId!=$preprocessing->format->formatId){
      throw new BadRequestException($this->translate('Selected preprocessing is not usable in the context of selected column.'));
    }
    $this->template->preprocessing=$preprocessing;
    /** @var Form $form */
    $form=$this->getComponent('newAttributeForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'preprocessing'=>$preprocessing->preprocessingId,
      'attributeName'=>$datasourceColumn->name
    ));
  }

  /**
   * @param int $miner
   * @param int|null $column=null
   * @param string|null $columnName=null
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function renderAddAttribute($miner,$column=null,$columnName=null){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);

    $this->minersFacade->checkMinerState($miner);

    $this->template->miner=$miner;
    $this->template->metasource=$miner->metasource;
    try{
      if (!empty($column)){
        $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($miner->datasource,$column);
      }else{
        $datasourceColumn=$this->datasourcesFacade->findDatasourceColumnByName($miner->datasource,$columnName);
      }
    }catch (\Exception $e){
      throw new BadRequestException($this->translate('Requested data field not found!'),404);
    }
    $this->template->datasourceColumn=$datasourceColumn;
    $format=$datasourceColumn->format;
    $this->template->format=$format;
    $this->template->metaAttributeName=$format->metaAttribute->name;
    $this->template->preprocessings=$this->metaAttributesFacade->findPreprocessingsForUser($format,$this->user->id);
  }



  /**
   * Funkce pro načtení příslušného DatasourceColumn, případně vrácení chyby
   * @param Datasource|int $datasource
   * @param int $column
   * @throws BadRequestException
   * @return DatasourceColumn
   */
  private function findDatasourceColumn($datasource,$column){
    try{
      $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
      return $datasourceColumn;
    }catch (\Exception $e){
      throw new BadRequestException($this->translate('Requested data field not found!'),404);
    }
  }

  /**
   * Funkce vracející formulář pro vytvoření atributu na základě vybraného sloupce a preprocessingu
   * @return Form
   */
  protected function createComponentNewAttributeForm(){
    $form = new Form();
    $presenter=$this;
    $form->setTranslator($this->translator);
    $form->addHidden('miner');
    $form->addHidden('column');
    $form->addHidden('preprocessing');
    $name=$form->addText('attributeName','Attribute name:')->setRequired('Input attribute name!');
    //TODO validátor, zda dosud neexistuje atribut se zadaným jménem!!!
    $form->addSubmit('submit','Create attribute')->onClick[]=function(SubmitButton $button){
      $values=$button->form->values;
      $miner=$this->findMinerWithCheckAccess($values->miner);
      $this->minersFacade->checkMinerMetasource($miner);
      $attribute=new Attribute();
      $attribute->metasource=$miner->metasource;
      $attribute->datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($miner->datasource,$values->column);
      $attribute->name=$values->attributeName;
      $attribute->type=$attribute->datasourceColumn->type;
      $attribute->preprocessing=$this->metaAttributesFacade->findPreprocessing($values->preprocessing);
      $this->minersFacade->prepareAttribute($miner,$attribute);
      $this->metasourcesFacade->saveAttribute($attribute);
      $this->minersFacade->checkMinerState($miner);

      $this->redirect('reloadUI');
    };
    $storno=$form->addSubmit('storno','storno');
    $storno->setValidationScope(array());
    $storno->onClick[]=function(SubmitButton $button)use($presenter){
      //přesměrování na výběr preprocessingu
      $values=$button->form->getValues();
      $presenter->redirect('addAttribute',array('column'=>$values->column,'miner'=>$values->miner));
    };
    return $form;
  }


  /**
   * Funkce pro přiřazení výchozího layoutu podle parametru v adrese (normální nebo iframe view)
   */
  protected function beforeRender(){
    if ($this->mode=='component' || $this->mode=='iframe'){
      $this->layout='iframe';
      $this->template->layout='iframe';
    }
    parent::beforeRender();
  }

  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade){
    $this->datasourcesFacade=$datasourcesFacade;
  }

  /**
   * @param MetasourcesFacade $metasourcesFacade
   */
  public function injectMetasourcesFacade(MetasourcesFacade $metasourcesFacade){
    $this->metasourcesFacade=$metasourcesFacade;
  }
  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function injectMetaAttributesFacade(MetaAttributesFacade $metaAttributesFacade){
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
  #endregion injections
} 