<?php

namespace App\EasyMinerModule\Presenters;


use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\Rdf\Facades\MetaAttributesFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

class AttributesPresenter extends BasePresenter{

  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
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
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $format=$this->metaAttributesFacade->findFormat($datasourceColumn->formatId);
    //kontrola, jestli už existuje preprocessing tohoto typu
    $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($format);//TODO
    $this->template->preprocessing=$preprocessing;
    /** @var Form $form */
    $form=$this->getComponent('newAttribute');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'preprocessing'=>$preprocessing->uri,
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
    $format=$this->metaAttributesFacade->findFormat($datasourceColumn->formatId);
    $this->template->format=$format;

    try{
      $this->template->metaAttributeName=$format->metaAttribute->name;
    }catch (\Exception $e){
      /*nebyl nalezen metaatribut*/
    }
    //FIXME zkontrolovat, jestli je ukládána vazba mezi metaatributem a formátem!!!
  }


  protected function beforeRender(){

    if ($this->mode=='component' || $this->mode=='iframe'){
      $this->layout='iframe';
      $this->template->layout='iframe';
    }
    parent::beforeRender();
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
  protected function createComponentNewAttribute(){
    $form = new Form();
    $presenter=$this;
    $form->setTranslator($this->translator);
    $form->addHidden('miner');
    $form->addHidden('column');
    $form->addHidden('preprocessing');
    $name=$form->addText('attributeName','Attribute name:')->setRequired('Input attribute name!');
    //TODO validátor, zda dosud neexistuje atribut se zadaným jménem!!!
    $form->addSubmit('submit','Create attribute')->onClick[]=function(SubmitButton $button){
      //TODO vytvoření atributu...
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


  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade){
    $this->datasourcesFacade=$datasourcesFacade;
  }

  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function injectMetaAttributesFacade(MetaAttributesFacade $metaAttributesFacade){
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
  #endregion injections
} 