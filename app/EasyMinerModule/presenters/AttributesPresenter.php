<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\Format;
use EasyMinerCenter\Model\EasyMiner\Entities\Interval;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Entities\Value;
use EasyMinerCenter\Model\EasyMiner\Entities\ValuesBin;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\PreprocessingsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Strings;

class AttributesPresenter extends BasePresenter{

  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var  PreprocessingsFacade $preprocessingsFacade */
  private $preprocessingsFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;

  /**
   * @var string $mode
   * @persistent
   */
  public $mode='default';

  /**
   * @param int $miner
   * @param int $column
   * @param string $preprocessing
   * @throws BadRequestException
   */
  public function renderShowPreprocessing($miner, $column, $preprocessing){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $preprocessing=$this->metaAttributesFacade->findPreprocessing($preprocessing);
    if ($datasourceColumn->format->formatId!=$preprocessing->format->formatId){
      throw new BadRequestException($this->translate('Selected preprocessing is not usable in the context of selected column.'));
    }
    if (!$preprocessing->shared && ($this->user->id!=$preprocessing->user->userId)){
      throw new ForbiddenRequestException($this->translate('You are not authorized to use the selected preprocessing.'));
    }
    $this->template->preprocessing=$preprocessing;
    $this->template->miner=$miner;
    $this->template->column=$column;
  }

  /**
   * Akce pro přesměrování na správnou akci pro definici nového preprocessingu
   * @param int $miner
   * @param int $column
   * @param string $type
   */
  public function actionNewPreprocessing($miner,$column,$type){
    $this->redirect('newPreprocessing'.Strings::firstUpper($type),['miner'=>$miner,'column'=>$column]);
  }

  /**
   * Funkce pro pouřití preprocessingu each value - one bin
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
   * Akce pro použití preprocessingu interval enumeration
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
    $this->template->format=$format;
    /** @var Form $form */
    $form=$this->getComponent('newIntervalEnumerationForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'attributeName'=>$datasourceColumn->name
    ));
  }

  /**
   * Akce pro použití preprocessingu
   * @param int $miner
   * @param int $column
   * @throws BadRequestException
   */
  public function renderNewPreprocessingEquidistantIntervals($miner, $column){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $format=$datasourceColumn->format;
    $this->template->format=$format;

    $minLeftMargin=null;
    $minLeftClosure=null;
    $maxRightMargin=null;
    $maxRightClosure=null;
    $intervals=$format->intervals;
    if (!empty($intervals)){
      foreach($intervals as $interval){
        if ($minLeftMargin==null){
          //jde o první interval
          $minLeftMargin=$interval->leftMargin;
          $minLeftClosure=$interval->leftClosure;
          $maxRightMargin=$interval->rightMargin;
          $maxRightClosure=$interval->rightClosure;
        }else{
          //budeme kontrolovat hranice a případně je zvětšovat
          if ($minLeftMargin>$interval->leftMargin || ($minLeftMargin==$interval->leftMargin && $minLeftClosure==Interval::CLOSURE_OPEN && $interval->leftClosure==Interval::CLOSURE_CLOSED)){
            $minLeftClosure=$interval->leftClosure;
            $minLeftMargin=$interval->leftMargin;
          }
          if ($maxRightMargin<$interval->rightMargin || ($maxRightMargin==$interval->rightMargin && $maxRightClosure==Interval::CLOSURE_OPEN && $interval->rightClosure==Interval::CLOSURE_CLOSED)){
            $maxRightClosure=$interval->rightClosure;
            $maxRightMargin=$interval->rightMargin;
          }
        }
      }
    }

    /** @var Form $form */
    $form=$this->getComponent('newEquidistantIntervalsForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'attributeName'=>$datasourceColumn->name,
      'minLeftMargin'=>$minLeftMargin,
      'minLeftClosure'=>$minLeftClosure,
      'maxRightMargin'=>$maxRightMargin,
      'maxRightClosure'=>$maxRightClosure,
      'equidistantLeftMargin'=>$minLeftMargin,
      'equidistantRightMargin'=>$maxRightMargin,
      'equidistantIntervalsCount'=>5
    ));
  }

  /**
   * Funkce pro pouřití preprocessingu each value - one bin
   * @param int $miner
   * @param int $column
   * @throws BadRequestException
   */
  public function renderNewPreprocessingNominalEnumeration($miner, $column){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $format=$datasourceColumn->format;
    $this->template->format=$format;
    //připravení kompletní sady hodnot
    $valuesArr=[];
    if (!empty($format->values)){
      foreach ($format->values as $value){
        $valuesArr[]=$value->value;
      }
    }
    $this->template->values=$valuesArr;

    /** @var Form $form */
    $form=$this->getComponent('newNominalEnumerationForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'formatType'=>$format->dataType,
      'formatId'=>$format->formatId,
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
      ->setRequired(false)
      ->setAttribute('placeholder',$this->translate('Interval bins'))
      ->setAttribute('title',$this->translate('You can left this field blank, it will be filled in automatically.'))
      ->addRule(function(TextInput $textInput){
        $form=$textInput->getForm(true);
        $formValues=$form->getValues(true);
        $textInputValue=$textInput->value;
        //nalezení aktuálního formátu
        $miner=$this->findMinerWithCheckAccess($formValues['miner']);
        $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$formValues['column']);
        $format=$datasourceColumn->format;
        $textInput->setAttribute('placeholder',$this->prepareIntervalEnumerationPreprocessingName($format,$formValues));
        if ($textInputValue!=''){
          //check preprocessings
          $existingPreprocessings=$format->preprocessings;
          if (!empty($existingPreprocessings)){
            foreach ($existingPreprocessings as $existingPreprocessing){
              if ($existingPreprocessing->name==$textInput){
                return false;
              }
            }
          }
        }
        return true;
      },'This preprocessing name already exists. Please select a new one...')
      ->addRule(function(TextInput $input){
        $values=$input->getForm(true)->getValues(true);
        return (count($values['valuesBins'])>0);
      },'You have to input at least one bin!');
    $form->addText('attributeName','Create attribute with name:')
      ->setRequired('Input attribute name!')
      ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
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
    $form->addHidden('column');
    $form->addHidden('miner');
    /** @var Container $valuesBins */
    /** @noinspection PhpUndefinedMethodInspection */
    $valuesBins=$form->addDynamic('valuesBins', function (Container $valuesBin){
      $valuesBin->addText('name','Bin name:')->setRequired(true)
        ->setRequired('Input bin name!')
        ->addRule(function(TextInput $input){
          /** @var Container $container */
          $container=$input->parent;
          $values=$container->getValues(true);
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
          $intervals = $submitButton->getParent()->getParent();
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
        ->setAttribute('class','removeBin')
        ->setValidationScope([])
        ->onClick[]=function(SubmitButton $submitButton){
        $submitButton->getParent()->getParent()->remove($submitButton->getParent(),true);
      };
    }, 0);

    $valuesBins->addSubmit('addBin','Add bin')
      ->setValidationScope([])
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
      $preprocessing->name=($values['preprocessingName']!=''?$values['preprocessingName']:$this->prepareIntervalEnumerationPreprocessingName($format,$values));
      $preprocessing->format=$format;
      $preprocessing->user=$this->usersFacade->findUser($this->user->id);
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
      $values=$submitButton->getForm()->getValues();
      $presenter->redirect('addAttribute',array('column'=>$values->column,'miner'=>$values->miner));
    };
    return $form;
  }

  /**
   * Formulář pro zadání preprocessingu pomocí interval enumeration
   * @return Form
   */
  protected function createComponentNewEquidistantIntervalsForm(){
    $form=new Form();
    $form->setTranslator($this->translator);
    $form->addHidden('column');
    $form->addHidden('miner');
    $form->addHidden('minLeftMargin');
    $form->addHidden('minLeftClosure');
    $form->addHidden('maxRightMargin');
    $form->addHidden('maxRightClosure');

    $form->addText('preprocessingName','Preprocessing name:')
      ->setRequired(false)
      ->setAttribute('placeholder',$this->translate('Equidistant intervals'))
      ->setAttribute('title',$this->translate('You can left this field blank, it will be filled in automatically.'))
      ->addRule(function(TextInput $textInput){
        $form=$textInput->getForm(true);
        $formValues=$form->getValues(true);
        $textInputValue=$textInput->value;
        //nalezení aktuálního formátu
        $miner=$this->findMinerWithCheckAccess($formValues['miner']);
        $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$formValues['column']);
        $format=$datasourceColumn->format;
        $textInput->setAttribute('placeholder',$this->prepareEquidistantPreprocessingName($format,$formValues));
        if ($textInputValue!=''){
          //check preprocessings
          $existingPreprocessings=$format->preprocessings;
          if (!empty($existingPreprocessings)){
            foreach ($existingPreprocessings as $existingPreprocessing){
              if ($existingPreprocessing->name==$textInput){
                return false;
              }
            }
          }
        }
        return true;
      },'This preprocessing name already exists. Please select a new one...')
      ->setAttribute('class','normalWidth');
    $form->addText('attributeName','Create attribute with name:')
      ->setAttribute('class','normalWidth')
      ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
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

    $form->addText('equidistantLeftMargin','Equidistant intervals from:')
      ->setRequired('You have to input number!')
      ->addRule(Form::FLOAT,'You have to input number!')
      ->addRule(function(TextInput $textInput){
        $values=$textInput->getForm(true)->getValues(true);
        if ($values['equidistantLeftMargin']<$values['minLeftMargin'] || $values['equidistantLeftMargin']>=$values['maxRightMargin']){
          return false;
        }
        return true;
      },'Start value cannot be out of the data range!');

    $form->addText('equidistantRightMargin','Equidistant intervals to:')
      ->setRequired('You have to input number!')
      ->addRule(Form::FLOAT,'You have to input number!')
      ->addRule(function(TextInput $textInput){
        $values=$textInput->getForm(true)->getValues(true);
        if ($values['equidistantRightMargin']>$values['maxRightMargin'] || $values['equidistantRightMargin']<=$values['minLeftMargin']){
          return false;
        }
        return true;
      },'End value cannot be out of the data range!')
      ->addRule(function(TextInput $textInput){
        $values=$textInput->getForm(true)->getValues(true);
        if ($values['equidistantLeftMargin']>=$values['equidistantRightMargin']){
          return false;
        }
        return true;
      },'Start value has to be lower than end value!');

    $form->addText('equidistantIntervalsCount','Intervals count:')
      ->setRequired('You have to input an integer value bigger than 2!')
      ->addRule(Form::INTEGER,'You have to input an integer value bigger than 2!')
      ->addRule(Form::MIN,'You have to input an integer value bigger than 2!',2);

    $form->addSubmit('submit','Save preprocessing & create attribute')
      ->onClick[]=function(SubmitButton $submitButton){
      #region vytvoření preprocessingu
      $values=$submitButton->getForm(true)->getValues(true);
      //nalezení příslušného formátu
      $miner=$this->findMinerWithCheckAccess($values['miner']);
      $this->minersFacade->checkMinerMetasource($miner);
      $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$values['column']);
      $format=$datasourceColumn->format;

      //vytvoření preprocessingu
      $preprocessing=new Preprocessing();
      $preprocessing->name=($values['preprocessingName']!=''?$values['preprocessingName']:$this->prepareEquidistantPreprocessingName($format,$values));
      $preprocessing->format=$format;
      $preprocessing->user=$this->usersFacade->findUser($this->user->id);
      $this->preprocessingsFacade->savePreprocessing($preprocessing);

      //vytvoření jednotlivých intervalů v podobě samostatných binů
      $intervalsCount=$values['equidistantIntervalsCount'];
      $step=$values['equidistantRightMargin']-$values['equidistantLeftMargin'];
      $step=($step/$intervalsCount);
      if (round($step,3)>0){
        $step=round($step,3);
      }
      $firstStart=$values['equidistantLeftMargin'];
      $endMax=$values['equidistantRightMargin'];
      $remainingIntervals=$intervalsCount;
      $lastEnd=$values['equidistantLeftMargin'];
      while($remainingIntervals>0 && $lastEnd<$endMax){
        $interval=new Interval();
        $interval->leftMargin=$lastEnd;
        if ($lastEnd==$firstStart){
          $interval->leftClosure=$values['minLeftClosure'];
        }else{
          $interval->leftClosure=Interval::CLOSURE_CLOSED;
        }
        $lastEnd=min($endMax,$lastEnd+$step);
        if ($remainingIntervals==1){
          //dogenerování intervalu až do konce
          $lastEnd=$endMax;
        }
        $interval->rightMargin=$lastEnd;
        if ($lastEnd==$endMax){
          $interval->rightClosure=$values['maxRightClosure'];
        }else{
          $interval->rightClosure=Interval::CLOSURE_OPEN;
        }
        $valuesBin=new ValuesBin();
        $valuesBin->format=$format;
        $valuesBin->name=$interval->__toString();
        $this->metaAttributesFacade->saveValuesBin($valuesBin);
        $this->metaAttributesFacade->saveInterval($interval);
        $valuesBin->addToIntervals($interval);
        $this->metaAttributesFacade->saveValuesBin($valuesBin);
        $preprocessing->addToValuesBins($valuesBin);

        $remainingIntervals--;
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
      $values=$submitButton->getForm()->getValues();
      $presenter->redirect('addAttribute',array('column'=>$values->column,'miner'=>$values->miner));
    };
    return $form;
  }

  /**
   * Formulář pro zadání preprocessingu pomocí nominal enumeration
   * @return Form
   */
  protected function createComponentNewNominalEnumerationForm(){
    $form=new Form();
    $form->setTranslator($this->translator);
    $form->addText('preprocessingName','Preprocessing name:')
      ->setRequired(false)
      ->setAttribute('placeholder',$this->translate('Nominal bins'))
      ->setAttribute('title',$this->translate('You can left this field blank, it will be filled in automatically.'))
      ->addRule(function(TextInput $textInput){
        $form=$textInput->getForm(true);
        $formValues=$form->getValues(true);
        $textInputValue=$textInput->value;
        //nalezení aktuálního formátu
        $miner=$this->findMinerWithCheckAccess($formValues['miner']);
        $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$formValues['column']);
        $format=$datasourceColumn->format;
        $textInput->setAttribute('placeholder',$this->prepareNominalEnumerationPreprocessingName($format,$formValues));
        if ($textInputValue!=''){
          //check preprocessings
          $existingPreprocessings=$format->preprocessings;
          if (!empty($existingPreprocessings)){
            foreach ($existingPreprocessings as $existingPreprocessing){
              if ($existingPreprocessing->name==$textInput){
                return false;
              }
            }
          }
        }
        return true;
      },'This preprocessing name already exists. Please select a new one...')
      ->addRule(function(TextInput $input){
        $values=$input->getForm(true)->getValues(true);
        return (count($values['valuesBins'])>0);
      },'You have to input at least one bin!');
    $form->addText('attributeName','Create attribute with name:')
      ->setRequired('Input attribute name!')
      ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
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
    $form->addHidden('column');
    $form->addHidden('miner');
    $form->addHidden('formatType');
    $form->addHidden('formatId');
    /** @var Container $valuesBins */
    $valuesBins=$form->addDynamic('valuesBins', function (Container $valuesBin){
      $valuesBin->addText('name','Bin name:')->setRequired(true)
        ->setRequired('Input bin name!')
        //->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
        ->addRule(function(TextInput $input){
          $values=$input->parent->getValues(true);
          return (count($values['values'])>0);
        },'Add at least one value!')
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
      $intervals=$valuesBin->addDynamic('values',function(Container $interval){
        $interval->addText('value')->setAttribute('readonly');
        $interval->addSubmit('remove','x')
          ->setAttribute('class','removeValue')
          ->setValidationScope([])
          ->onClick[]=function(SubmitButton $submitButton){
          $intervals = $submitButton->parent->parent;
          $intervals->remove($submitButton->parent, TRUE);
        };
      });
      $addValueSubmit=$valuesBin->addSubmit('addValue','Add value');
      $value=$valuesBin->addText('value',null,'');//TODO dodělat select...
      $value
        ->addConditionOn($addValueSubmit,Form::SUBMITTED)
          ->setRequired('Input value!')
          ->addConditionOn($valuesBin->getForm(true)->getComponent('formatType'),Form::EQUAL,Format::DATATYPE_VALUES)
            ->addRule(function(TextInput $input){
              $inputValue=$input->getValue();
              $values=$input->getForm(true)->getValues(true);
              $format=$this->metaAttributesFacade->findFormat($values['formatId']);
              $values=$format->values;
              if (!empty($values)){
                foreach ($values as $value){
                  if ($value->value==$inputValue){
                    return true;
                  }
                }
              }
              return false;
            },'You have to input existing value!')
          ->elseCondition()
            ->addRule(Form::FLOAT,'You have to input number!')
            //TODO kontrola, jestli je hodnota ze zadaného intervalu...
          ->endCondition();
      $value->addRule(function(TextInput $input){
        $values=$input->getForm(true)->getValues(true);
        $usedValuesArr=[];
        if (!empty($values['valuesBins'])){
          foreach($values['valuesBins'] as $valuesBin){
            if (!empty($valuesBin['values'])){
              foreach($valuesBin['values'] as $value){
                $usedValuesArr[]=$value['value'];
              }
            }
          }
        }
        return (!in_array($input->value,$usedValuesArr));
      },'This value is already used!');
      $addValueSubmit
        ->setValidationScope([$value])
        ->onClick[]=function(SubmitButton $submitButton)use($intervals){
        $values=$submitButton->getParent()->getValues(true);
        $valueItem=$submitButton->getParent()['values']->createOne();
        $valueItem->setValues([
          'value'=>$values['value']
        ]);
        $submitButton->getParent()->setValues(['value'=>'']);
      };
      $valuesBin->addSubmit('remove','Remove bin')
        ->setAttribute('class','removeBin')
        ->setValidationScope([])
        ->onClick[]=function(SubmitButton $submitButton){
        $submitButton->getParent()->getParent()->remove($submitButton->getParent(),true);
      };
    }, 0);

    $valuesBins->addSubmit('addBin','Add bin')
      ->setValidationScope([])
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
      $preprocessing->name=($values['preprocessingName']!=''?$values['preprocessingName']:$this->prepareNominalEnumerationPreprocessingName($format,$values));
      $preprocessing->format=$format;
      $preprocessing->user=$this->usersFacade->findUser($this->user->id);
      $this->preprocessingsFacade->savePreprocessing($preprocessing);
      foreach($values['valuesBins'] as $valuesBinValues){
        $valuesBin=new ValuesBin();
        $valuesBin->format=$format;
        $valuesBin->name=$valuesBinValues['name'];
        $this->metaAttributesFacade->saveValuesBin($valuesBin);
        foreach ($valuesBinValues['values'] as $valuesValues){
          try{
            $value=$this->metaAttributesFacade->findValue($format,$valuesValues['value']);
          }catch (\Exception $e){
            $value=new Value();
            $value->value=$valuesValues['value'];
            $this->metaAttributesFacade->saveValue($value);
          }
          $valuesBin->addToValues($value);
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
      $values=$submitButton->getForm()->getValues();
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
      ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
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

  /**
   * Funkce pro vygenerování výchozího názvu preprocessingu
   * @param Format $format
   * @param array $formValues
   * @return string
   */
  private function prepareEquidistantPreprocessingName(Format $format,$formValues) {
    $preprocessingNameBase=$this->translate('Equidistant intervals ({:count}) from {:from} to {:to}');
    $preprocessingNameBase=str_replace(['{:count}','{:from}','{:to}'],[$formValues['equidistantIntervalsCount'],$formValues['equidistantLeftMargin'],$formValues['equidistantRightMargin']],$preprocessingNameBase);

    #region vyřešení unikátnosti jména
    $existingPreprocessingsNames=[];
    $existingPreprocessings=$format->preprocessings;
    if (!empty($existingPreprocessings)){
      foreach($existingPreprocessings as $existingPreprocessing){
        $existingPreprocessingsNames[]=$existingPreprocessing->name;
      }
    }
    $counter=1;
    do{
      /** @var string $preprocessingName */
      $preprocessingName=$preprocessingNameBase;
      if ($counter>1){
        $preprocessingName.=' ('.$counter.')';
      }
      $counter++;
    }while(in_array($preprocessingName,$existingPreprocessingsNames));
    #endregion
    return $preprocessingName;
  }

  /**
   * Funkce pro vygenerování výchozího názvu preprocessingu
   * @param Format $format
   * @param array $formValues
   * @return string
   */
  private function prepareNominalEnumerationPreprocessingName($format, $formValues) {
    $preprocessingNameBase=$this->translate('Nominal bins');
    $namesArr=[];
    if (!empty($formValues['valuesBins'])){
      $preprocessingNameBase.=': ';
      foreach($formValues['valuesBins'] as $valuesBin){
        $namesArr[]=$valuesBin['name'];
      }
      $preprocessingNameBase.=implode(', ',$namesArr);
    }
    unset($namesArr);

    #region vyřešení unikátnosti jména
    $existingPreprocessingsNames=[];
    $existingPreprocessings=$format->preprocessings;
    if (!empty($existingPreprocessings)){
      foreach($existingPreprocessings as $existingPreprocessing){
        $existingPreprocessingsNames[]=$existingPreprocessing->name;
      }
    }
    $counter=1;
    do{
      /** @var string $preprocessingName */
      $preprocessingName=$preprocessingNameBase;
      if ($counter>1){
        $preprocessingName.=' ('.$counter.')';
      }
      $counter++;
    }while(in_array($preprocessingName,$existingPreprocessingsNames));
    #endregion
    return $preprocessingName;
  }

  /**
   * Funkce pro vygenerování výchozího názvu preprocessingu
   * @param Format $format
   * @param array $formValues
   * @return string
   */
  private function prepareIntervalEnumerationPreprocessingName($format, $formValues) {
    $preprocessingNameBase=$this->translate('Interval bins');
    $namesArr=[];
    if (!empty($formValues['valuesBins'])){
      $preprocessingNameBase.=': ';
      foreach($formValues['valuesBins'] as $valuesBin){
        $namesArr[]=$valuesBin['name'];
      }
      $preprocessingNameBase.=implode(', ',$namesArr);
    }
    unset($namesArr);

    #region vyřešení unikátnosti jména
    $existingPreprocessingsNames=[];
    $existingPreprocessings=$format->preprocessings;
    if (!empty($existingPreprocessings)){
      foreach($existingPreprocessings as $existingPreprocessing){
        $existingPreprocessingsNames[]=$existingPreprocessing->name;
      }
    }
    $counter=1;
    do{
      /** @var string $preprocessingName */
      $preprocessingName=$preprocessingNameBase;
      if ($counter>1){
        $preprocessingName.=' ('.$counter.')';
      }
      $counter++;
    }while(in_array($preprocessingName,$existingPreprocessingsNames));
    #endregion
    return $preprocessingName;
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
  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
  #endregion injections
} 