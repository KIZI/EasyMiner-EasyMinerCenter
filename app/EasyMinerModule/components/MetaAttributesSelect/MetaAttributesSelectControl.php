<?php

namespace App\EasyMinerModule\Components;

use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\Rdf\Facades\MetaAttributesFacade;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

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

  /** @var callable[] $onComponentShow*/
  public  $onComponentShow=array();
  /** @var callable[] $onComponentHide*/
  public  $onComponentHide=array();

  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function __construct(MetaAttributesFacade $metaAttributesFacade, DatasourcesFacade $datasourcesFacade){
    $this->metaAttributesFacade=$metaAttributesFacade;
    $this->datasourcesFacade=$datasourcesFacade;
  }

  /**
   * Vykreslení komponenty (na základě zavolaného signálu)
   */
  public function render(){//TODO
    if (!empty($this->template)){
      $this->template->translator=$this->presenter->translator;
      $this->template->render();
    }
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
    }catch (\Exception $e){
      $this->onComponentHide();
    }
  }

  /**
   * Signál pro výběr formátu z existujícího metaatributu
   * @param int $datasource
   * @param int $column
   * @param string $metaAttribute
   */
  public function handleSelectFormat($datasource,$column,$metaAttribute){
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
  public function handleHide(){
    $this->onComponentHide();
  }


  /**
   * Formulář pro vytvoření nového metaatributu
   * @return Form
   */
  protected function createComponentNewMetaAttributeForm(){
    $form = new Form();
    $form->addText('name','Meta-attribute name:')->setRequired();//TODO kontrola, jestli zatím neexistuje metaatribut se stejným jménem...
    $form->addHidden('datasource');
    $form->addHidden('column');
    $form->addSubmit('create','Create meta-attribute')->onClick[]=function(SubmitButton $submitButton){
      //vytvoření nového metaatributu
      $this->redirect('selectFormat');
      $this->onComponentHide();
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
    $form->addText('name','Format name:')->setRequired();//TODO kontrola, jestli zatím neexistuje metaatribut se stejným jménem...
    $form->addSubmit('create','Create format')->onClick[]=function(SubmitButton $submitButton){
      //vytvoření nového metaatributu
      exit('vytvoření nového formátu na základě dat z datasource column');


      $this->onComponentHide();
    };
    $form->onError[]=function(){
      $this->onComponentShow();
    };
    return $form;
  }


} 