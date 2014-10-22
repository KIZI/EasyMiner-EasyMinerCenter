<?php

namespace App\EasyMinerModule\Components;

use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\MetaAttribute;
use App\Model\Rdf\Facades\MetaAttributesFacade;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Localization\ITranslator;
use Nette\Utils\Strings;

/**
 * Class MetaAttributesSelectControl
 * @package App\EasyMinerModule\Components
 * @method onComponentHide
 * @method onComponentShow
 */
class MetaAttributesSelectControl extends Control{

  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  ITranslator $translator */
  private $translator;

  /** @var callable[] $onComponentShow*/
  public  $onComponentShow=array();
  /** @var callable[] $onComponentHide*/
  public  $onComponentHide=array();

  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function __construct(MetaAttributesFacade $metaAttributesFacade, DatasourcesFacade $datasourcesFacade, ITranslator $translator){
    $this->metaAttributesFacade=$metaAttributesFacade;
    $this->datasourcesFacade=$datasourcesFacade;
    $this->translator=$translator;
  }

  /**
   * Vykreslení komponenty (na základě zavolaného signálu)
   */
  public function render(){
    $template=$this->template;
    $template->render();
  }

  /**
   * Signál pro výběr metaatributu
   * @param int $datasource
   * @param int $column
   */
  public function handleSelectMetaAttribute($datasource,$column){
    $this->onComponentShow();
    $this->template->setFile(__DIR__ . '/selectMetaAttribute.latte');
    try{
      $this->template->datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
      $this->template->metaAttributes=$this->metaAttributesFacade->findMetaAttributes();
    }catch (\Exception $e){
      $this->onComponentHide();
    }
  }

  public function createTemplate(){
    $template=parent::createTemplate();
    $template->setTranslator($this->translator);
    return $template;
  }

  /**
   * Signál pro výběr formátu z existujícího metaatributu
   * @param int $datasource
   * @param int $column
   * @param string $metaAttribute
   */
  public function handleSelectFormat($datasource,$column,$metaAttribute=null){
    $this->onComponentShow();
    $this->template->setFile(__DIR__ . '/selectFormat.latte');
    try{
      $this->template->datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
    }catch (\Exception $e){
      $this->onComponentHide();
    }
    try{
      $this->template->metaAttribute=$this->metaAttributesFacade->findMetaAttribute($metaAttribute);
    }catch (\Exception $e){
      $this->redirect('SelectMetaAttribute',array('datasource'=>$datasource,'column'=>$column));//při chybě při načítání metaatributu přesměrujeme uživatele zpátky na vytvoření metaatributu
    }
  }

  /**
   * Signál pro skrytí komponenty...
   */
  public function handleClose(){
    $this->onComponentHide();
  }

  /**
   * Signál pro zobrazení formuláře pro vytvoření nového metaatributu a formátu
   * @param $datasource
   * @param $column
   */
  public function handleNewMetaAttribute($datasource,$column){
    $this->onComponentShow();
    $this->template->setFile(__DIR__ . '/newMetaAttribute.latte');
    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    /** @var Form $form */
    $form=$this->getComponent('newMetaAttributeForm');
    $defaults=array(
      'datasource'=>$datasource,
      'column'=>$column,
      'metaAttributeName'=>$datasourceColumn->name
    );
    if ($datasourceColumn->type=='string'){
      /** @var SelectBox $formatType */
      $formatType=$form->getComponent('formatType');
      $formatType->setDefaultValue('values');
      $defaults['formatType']='values';
      $formatType->setDisabled();
    }
    $form->setDefaults($defaults);
  }

  /**
   * Signál pro zobrazení formuláře pro vytvoření nového metaatributu a formátu
   * @param int $datasource
   * @param int $column
   * @param string $metaAttribute
   */
  public function handleNewFormat($datasource,$column,$metaAttribute){
    $this->onComponentShow();
    $this->template->setFile(__DIR__ . '/newFormat.latte');
    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;

    $metaAttribute=$this->metaAttributesFacade->findMetaAttribute($metaAttribute);

    $this->template->metaAttribute=$metaAttribute;

    /** @var Form $form */
    $form=$this->getComponent('newFormatForm');
    $defaults=array(
      'datasource'=>$datasource,
      'column'=>$column,
      'metaAttributeName'=>$metaAttribute->name,
      'metaAttribute'=>$metaAttribute->uri,
    );
    if ($datasourceColumn->type=='string'){
      /** @var SelectBox $formatType */
      $formatType=$form->getComponent('formatType');
      $formatType->setDefaultValue('values');
      $defaults['formatType']='values';
      $formatType->setDisabled();
    }
    $form->setDefaults($defaults);
  }

  /**
   * Signál pro propojení DatasourceColumn s Formátem
   * @param int $datasource
   * @param int $column
   * @param string $format
   */
  public function handleSetDatasourceColumnFormat($datasource,$column,$format){
    //TODO propojení a kontrola, jestli datový rozsah formátu odpovídá datovému rozsahu (jestli hodnoty z DatasourceColumn spadají do rozsahu formátu)
  }


  /**
   * Formulář pro vytvoření nového metaatributu
   * @return Form
   */
  protected function createComponentNewMetaAttributeForm(){
    $form = new Form();
    $form->addText('metaAttributeName','Meta-attribute name:')->setRequired();//TODO kontrola, jestli zatím neexistuje metaatribut se stejným jménem...
    $form->addText('formatName','Format name:')->setRequired();//TODO kontrola, jestli zatím neexistuje metaatribut se stejným jménem...
    $form->addHidden('datasource');
    $form->addHidden('column');
    $form->addSelect('formatType','Values range:',array('interval'=>'Continuous values (interval)','values'=>'Distinct values (enumeration)'));
    $form->addSubmit('create','Create meta-attribute')->onClick[]=function(SubmitButton $button){
      $values=$button->form->values;
      try{
        $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($values->datasource,$values->column);
        $format=$this->createMetaAttributeFromDatasourceColumn($values->metaAttributeName,$values->formatName,$datasourceColumn,@$values->formatType);
        $datasourceColumn->formatId=$format->uri;
        $this->datasourcesFacade->saveDatasourceColumn($datasourceColumn);
      }catch (\Exception $e){
        $this->flashMessage($this->translator->translate('MetaAttribute creation failed.'));
      }
      $this->redirect('Close');
    };
    $storno=$form->addSubmit('storno','Storno');
    $storno->setValidationScope(array());
    $storno->onClick[]=function(SubmitButton $button){
      $values=$button->form->values;
      $this->redirect('SelectMetaAttribute',array('datasource'=>$values->datasource,'column'=>$values->column));
    };
    $form->onError[]=function(){
      $this->onComponentShow();
    };
    return $form;
  }


  /**
   * Formulář pro vytvoření nového formátu
   * @return Form
   */
  protected function createComponentNewFormatForm(){
    $form = new Form();
    $form->addHidden('datasource');
    $form->addHidden('column');
    $form->addHidden('metaAttribute');
    $form->addText('metaAttributeName','Meta-attribute:')->setAttribute('readonly','readonly');
    $form->addText('name','Format name:')->setRequired();//TODO regex pro kontrolu jména formátu a kontrola existence formátu se stejným jménem
    $form->addSelect('formatType','Values range:',array('interval'=>'Continuous values (interval)','values'=>'Distinct values (enumeration)'));
    $form->addSubmit('create','Create format')->onClick[]=function(SubmitButton $button){
      $values=$button->form->values;
      try{
        $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($values->datasource,$values->column);
        $metaAttribute=$this->metaAttributesFacade->findMetaAttribute($values->metaAttributeName);
        $format=$this->createFormatFromDatasourceColumn($metaAttribute,$values->formatName,$datasourceColumn,@$values->formatType);
        $datasourceColumn->formatId=$format->uri;
        $this->datasourcesFacade->saveDatasourceColumn($datasourceColumn);
      }catch (\Exception $e){
        $this->flashMessage($this->translator->translate('Format creation failed.'));
      }
      $this->redirect('Close');
    };
    $storno=$form->addSubmit('storno','Storno');
    $storno->setValidationScope(array());
    $storno->onClick[]=function(SubmitButton $button){
      $values=$button->form->values;
      $this->redirect('SelectFormat',array('datasource'=>$values->datasource,'column'=>$values->column,'metaAttribute'=>$values->metaAttribute));
    };
    $form->onError[]=function(){
      $this->onComponentShow();
    };
    return $form;
  }

  /**
   * Funkce pro vytvoření metaatributu a formátu na základě hodnot datového sloupce
   * @param string $metaAttributeName
   * @param string $formatName
   * @param DatasourceColumn $datasourceColumn
   * @param string $formatType
   * @return Format
   */
  private function createMetaAttributeFromDatasourceColumn($metaAttributeName,$formatName,DatasourceColumn $datasourceColumn,$formatType){
    $metaAttribute=new MetaAttribute();
    $metaAttribute->name=$metaAttributeName;
    $this->metaAttributesFacade->saveMetaAttribute($metaAttribute);
    return $this->createFormatFromDatasourceColumn($metaAttribute,$formatName,$datasourceColumn,$formatType);
  }

  /**
   * Funkce pro vytvoření formátu na základě hodnot datového sloupce
   * @param MetaAttribute $metaAttribute
   * @param string $formatName
   * @param DatasourceColumn $datasourceColumn
   * @param string $formatType=values - 'interval'|'values'
   * @return Format
   */
  private function createFormatFromDatasourceColumn(MetaAttribute $metaAttribute,$formatName,DatasourceColumn $datasourceColumn,$formatType='values'){
    $format=$this->metaAttributesFacade->createFormatFromDatasourceColumn($datasourceColumn,(Strings::lower($formatType)=='interval'?'interval':'values'));
    $format->name=$formatName;
    $format->metaAttribute=$metaAttribute;
    $this->metaAttributesFacade->saveFormat($format);
  }





} 