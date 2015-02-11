<?php

namespace App\EasyMinerModule\Presenters;


use App\Libs\StringsHelper;
use App\Model\EasyMiner\Entities\Attribute;
use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Entities\Interval;
use App\Model\EasyMiner\Entities\Preprocessing;
use App\Model\EasyMiner\Entities\ValuesBin;
use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\EasyMiner\Facades\MetasourcesFacade;
use App\Model\EasyMiner\Facades\MetaAttributesFacade;
use App\Model\EasyMiner\Facades\PreprocessingsFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Neon\Exception;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

class AttributesPresenter extends BasePresenter{

  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var  PreprocessingsFacade $preprocessingsFacade */
  private $preprocessingsFacade;

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

  public function actionNewPreprocessing($miner,$column,$type){
    $this->redirect('newPreprocessing'.Strings::firstUpper($type),['miner'=>$miner,'column'=>$column]);
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
    $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($format);
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
   * Funkce pro pouřití preprocessingu each value - one category
   * @param int $miner
   * @param int $column
   * @throws BadRequestException
   */
  public function renderNewPreprocessingIntervalEnumeration($miner, $column){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $format=$datasourceColumn->format;
    //kontrola, jestli už existuje preprocessing tohoto typu
    $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($format);
    $this->template->preprocessing=$preprocessing;
    /** @var Form $form */
    $form=$this->getComponent('newIntervalEnumerationForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
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
   * Formulář pro zadání preprocessingu pomocí interval enumeration
   * @return Form
   */
  protected function createComponentNewIntervalEnumerationForm(){
    $form=new Form();
    $form->setTranslator($this->translator);
    $form->addText('preprocessingName','Preprocessing name:')
      ->setRequired('Input preprocessing name!');
    $form->addText('attributeName','Create attribute with name:')
      ->setRequired('Input attribute name!')
      ->addRule(function(TextInput $input){
        //kontrola, jestli již existuje atribtu se zadaným názvem
        $values=$input->getForm(true)->getValues();
        $miner=$this->findMinerWithCheckAccess($values->miner);
        $attributes=$miner->metasource->attributes;
        if (!empty($attributes)){
          foreach ($attributes as $attribute){
            if ($attribute->name==$input->value){
              return false;
            }
          }
        }
        return true;
      },'Attribute with this name already exists!')
      ->addRule(function(TextInput $input){
        $values=$input->getForm(true)->getValues(true);
        return (count($values['valuesBins'])>0);
      },'You have to input at least one bin!');
    $form->addHidden('column');
    $form->addHidden('miner');
    /** @var Container $valuesBins */
    $valuesBins=$form->addDynamic('valuesBins', function (Container $valuesBin){
      $valuesBin->addText('name','Bin name:')->setRequired(true)
        ->setRequired('Input bin name!')
        ->addRule(function(TextInput $input){
          $values=$input->parent->getValues(true);
          return (count($values['intervals'])>0);
        },'Add at least one interval!')
        ->addRule(function(TextInput $input){
          //kontrola, jestli má každý BIN jiný název
          $values=$input->getParent()->getParent()->getValues(true);
          $inputValue=$input->getValue();
          $usesCount=0;
          if (!empty($values)){
            foreach ($values as $value){
              if ($value['name']==$inputValue){
                $usesCount++;
              }
            }
          }
          return $usesCount<=1;
        },'This name is used for other bin!');
      /** @var Container $intervals */
      $intervals=$valuesBin->addDynamic('intervals',function(Container $interval){
        $interval->addHidden('leftValue');
        $interval->addHidden('leftBound');
        $interval->addHidden('rightValue');
        $interval->addHidden('rightBound');
        $interval->addText('text')->setAttribute('readonly');
        $interval->addSubmit('remove','x')
          ->setValidationScope([])
          ->onClick[]=function(SubmitButton $submitButton){
          $intervals = $submitButton->parent->parent;
          $intervals->remove($submitButton->parent, TRUE);
        };
      });
      $addIntervalSubmit=$valuesBin->addSubmit('addInterval','Add interval');
      $valuesBin->addSelect('leftBound',null,['closed'=>'[','open'=>'(']);
      $leftValue=$valuesBin->addText('leftValue')->setDefaultValue('');
      $leftValue
        ->addConditionOn($addIntervalSubmit,Form::SUBMITTED)
          ->setRequired('Input start value!')
          ->addRule(Form::FLOAT,'You have to input number!');
      $rightValue=$valuesBin->addText('rightValue')->setDefaultValue('');
      $rightValue
        ->addConditionOn($addIntervalSubmit,Form::SUBMITTED)
          ->setRequired('Input end value!')
          ->addRule(Form::FLOAT,'You have to input number!')
          ->addRule(function(TextInput $input)use($addIntervalSubmit){
            $values=$input->getParent()->getValues(true);
            if ($values['leftValue']>$values['rightValue']){
              return false;
            }
            if (($values['leftValue']==$values['rightValue'])&&($values['leftBound']!=Interval::CLOSURE_CLOSED || $values['rightBound']!=Interval::CLOSURE_CLOSED)){
              return false;
            }
            return true;
          },'Interval end cannot be lower than start value!')
          ->addRule(function(TextInput $input){
            //kontrola překryvu intervalu
            $parentValues=$input->getParent()->getValues(true);
            $interval=Interval::create($parentValues['leftBound'],$parentValues['leftValue'],$parentValues['rightValue'],$parentValues['rightBound']);
            $valuesBinsValues=$input->getParent()->getParent()->getValues(true);
            if (!empty($valuesBinsValues)){
              foreach($valuesBinsValues as $valuesBin){
                if (!empty($valuesBin['intervals'])){
                  foreach($valuesBin['intervals'] as $intervalValues){
                    if ($interval->isInOverlapWithInterval(Interval::create($intervalValues['leftBound'],$intervalValues['leftValue'],$intervalValues['rightValue'],$intervalValues['rightBound']))){
                      return false;
                    }
                  }
                }
              }
            }
            return true;
          },'Interval overlaps with another one!');
      $valuesBin->addSelect('rightBound',null,['closed'=>']','open'=>')']);
      $addIntervalSubmit
        ->setValidationScope([$leftValue,$rightValue])
        ->onClick[]=function(SubmitButton $submitButton)use($intervals){
        /** @var Container $intervalsForm */
        $intervalsForm=$submitButton->parent;
        $values=$intervalsForm->getValues(true);
        $interval=$intervals->createOne();
        $interval->setValues([
          'leftBound'=>$values['leftBound'],
          'rightBound'=>$values['rightBound'],
          'leftValue'=>$values['leftValue'],
          'rightValue'=>$values['rightValue'],
          'text'=>StringsHelper::formatIntervalString($values['leftBound'],$values['leftValue'],$values['rightValue'],$values['rightBound'])
        ]);
        $intervalsForm->setValues(['leftValue'=>'','rightValue'=>'']);
      };
      $valuesBin->addSubmit('remove','Remove bin')
        ->setValidationScope([])
        ->onClick[]=function(SubmitButton $submitButton){
        $submitButton->getParent()->getParent()->remove($submitButton->getParent(),true);
      };
    }, 0);

    $valuesBins->addSubmit('submit','Add intervals bin')
      ->onClick[]=function(SubmitButton $submitButton){
      $submitButton->getParent()->createOne();
    };
    $form->addSubmit('submitAll','Save preprocessing & create attribute')
      ->onClick[]=function(SubmitButton $submitButton){
      #region vytvoření preprocessingu
      $values=$submitButton->getForm(true)->getValues(true);
      $miner=$this->findMinerWithCheckAccess($values['miner']);
      $this->minersFacade->checkMinerMetasource($miner);

      //vytvoření preprocessingu
      $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$values['column']);
      $format=$datasourceColumn->format;

      $preprocessing=new Preprocessing();
      $preprocessing->name=$values['preprocessingName'];
      $preprocessing->format=$format;
      $this->preprocessingsFacade->savePreprocessing($preprocessing);
      foreach($values['valuesBins'] as $valuesBinValues){
        $valuesBin=new ValuesBin();
        $valuesBin->format=$format;
        $valuesBin->name=$valuesBinValues['name'];
        $this->metaAttributesFacade->saveValuesBin($valuesBin);
        foreach ($valuesBinValues['intervals'] as $intervalValues){
          $interval=Interval::create($intervalValues['leftBound'],$intervalValues['leftValue'],$intervalValues['rightValue'],$intervalValues['rightBound']);
          $interval->format=null;//TODO kontrola, aby se zbytečně nevytvářely stejné atributy
          $this->metaAttributesFacade->saveInterval($interval);
          $valuesBin->addToIntervals($interval);
        }
        $this->metaAttributesFacade->saveValuesBin($valuesBin);
        $preprocessing->addToValuesBins($valuesBin);
      }
      $this->preprocessingsFacade->savePreprocessing($preprocessing);

      //vytvoření atributu
      $attribute=new Attribute();
      $attribute->metasource=$miner->metasource;
      $attribute->datasourceColumn=$datasourceColumn;
      $attribute->name=$values['attributeName'];
      $attribute->type=$attribute->datasourceColumn->type;
      $attribute->preprocessing=$preprocessing;
      $this->minersFacade->prepareAttribute($miner,$attribute);
      $this->metasourcesFacade->saveAttribute($attribute);
      $this->minersFacade->checkMinerState($miner);

      $this->redirect('reloadUI');
      #endregion vytvoření preprocessingu
    };
    $presenter=$this;
    $form->addSubmit('storno','storno')
      ->setValidationScope([])
      ->onClick[]=function(SubmitButton $submitButton)use($presenter){
      $values=$submitButton->getForm()->getValues(true);
      $presenter->redirect('addAttribute',array('column'=>$values->column,'miner'=>$values->miner));
    };
    return $form;
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
    $form->addText('attributeName','Attribute name:')
      ->setRequired('Input attribute name!')
      ->addRule(function(TextInput $input){
        //kontrola, jestli již existuje atribtu se zadaným názvem
        $values=$input->getForm(true)->getValues();
        $miner=$this->findMinerWithCheckAccess($values->miner);
        $attributes=$miner->metasource->attributes;
        if (!empty($attributes)){
          foreach ($attributes as $attribute){
            if ($attribute->name==$input->value){
              return false;
            }
          }
        }
        return true;
      },'Attribute with this name already exists!');
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
  /**
   * @param PreprocessingsFacade $preprocessingsFacade
   */
  public function injectPreprocessingsFacade(PreprocessingsFacade $preprocessingsFacade){
    $this->preprocessingsFacade=$preprocessingsFacade;
  }
  #endregion injections
} 