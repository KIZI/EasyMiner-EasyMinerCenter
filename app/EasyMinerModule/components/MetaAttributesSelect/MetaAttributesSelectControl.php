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
use Nette\Forms\Controls\TextInput;
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
      $this->flashMessage($this->translator->translate('Ooops, some error...'),'error');
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
      $this->flashMessage($this->translator->translate('Ooops, some error...'),'error');
      $this->onComponentHide();
    }
    try{
      $metaAttribute=$this->metaAttributesFacade->findMetaAttribute($metaAttribute);
      $this->template->metaAttribute=$metaAttribute;
      $this->template->formats=$metaAttribute->formats;
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
      $form->setDefaults($defaults);
      $formatType->setDisabled();
    }else{
      $form->setDefaults($defaults);
    }

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
      $form->setDefaults($defaults);
      $formatType->setDisabled();
    }else{
      $form->setDefaults($defaults);
    }

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
    $metaAttributeName=$form->addText('metaAttributeName','Meta-attribute name:')
      ->setRequired()
      ->setAttribute('class','normalWidth');
    $metaAttributeName->addRule(Form::MIN_LENGTH,'Min length of meta-attribute name is %s characters!',3);
    $metaAttributeName->addRule(function(TextInput $control){
      try{
        $metaAttribute=$this->metaAttributesFacade->findMetaAttributeByName($control->value);
        if ($metaAttribute instanceof MetaAttribute){
          return false;
        }
      }catch (\Exception $e){/*chybu ignorujeme (nenalezený metaatribut je OK)*/}
      return true;
    },'Meta-attribute with this name already exists!');
    $formatName=$form->addText('formatName','Format name:')->setRequired()->addRule(Form::MIN_LENGTH,'Min length of format name is %s characters!',3);
    $formatName->setAttribute('class','normalWidth');
    $form->addHidden('datasource');
    $form->addHidden('column');
    $form->addSelect('formatType','Values range:',array('interval'=>'Continuous values (interval)','values'=>'Distinct values (enumeration)'))
    ->setAttribute('class','normalWidth');
    $submit=$form->addSubmit('create','Create meta-attribute');
    $submit->setValidationScope(array($metaAttributeName,$formatName));
    /*$form->onSubmit[]=function(Form $form){
      exit(var_dump($form->getErrors()));
    };*/
    $submit->onClick[]=function(SubmitButton $button){
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
    $form->onError[]=function($form){
      $values=$form->values;
      $this->handleNewMetaAttribute($values->datasource,$values->column);
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
    $metaAttribute=$form->addHidden('metaAttribute');
    $form->addText('metaAttributeName','Meta-attribute:')->setAttribute('readonly','readonly')->setAttribute('class','normalWidth');
    $formatName=$form->addText('formatName','Format name:')->setRequired()->setAttribute('class','normalWidth');
    $formatName->addRule(Form::MIN_LENGTH,'Min length of format name is %s characters!',3);
    $formatName->addRule(function(TextInput $control)use($metaAttribute){
      try{
        $format=$this->metaAttributesFacade->findFormatByName($metaAttribute->value,$control->value);
        if ($format instanceof Format){
          return false;
        }
      }catch (\Exception $e){/*chybu ignorujeme (nenalezený metaatribut je OK)*/}
      return true;
    },'Format with this name already exists!');

    $form->addSelect('formatType','Values range:',array('interval'=>'Continuous values (interval)','values'=>'Distinct values (enumeration)'))->setAttribute('class','normalWidth')->setDefaultValue('values');
    $submit=$form->addSubmit('create','Create format');
    $submit->setValidationScope(array($formatName));
    $submit->onClick[]=function(SubmitButton $button){
      $values=$button->form->values;
      try{
        $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($values->datasource,$values->column);
        $metaAttribute=$this->metaAttributesFacade->findMetaAttribute($values->metaAttribute);
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
    $form->onError[]=function(Form $form){
      $values=$form->values;
      $this->handleNewFormat($values->datasource,$values->column,$values->metaAttribute);
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
    return $format;
  }





} 