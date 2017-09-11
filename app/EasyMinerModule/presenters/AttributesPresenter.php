<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
use EasyMinerCenter\Model\EasyMiner\Entities\Format;
use EasyMinerCenter\Model\EasyMiner\Entities\Interval;
use EasyMinerCenter\Model\EasyMiner\Entities\MetasourceTask;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Entities\Value;
use EasyMinerCenter\Model\EasyMiner\Entities\ValuesBin;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\PreprocessingsFacade;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\NotImplementedException;
use Nette\Utils\Strings;

/**
 * Class AttributesPresenter
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class AttributesPresenter extends BasePresenter{
  use MinersFacadeTrait;
  use UsersTrait;
  use ResponsesTrait;

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
   * Action for redirection to the right action for definition of a new preprocessing
   * @param int $miner
   * @param int $column
   * @param string $type
   */
  public function actionNewPreprocessing($miner,$column,$type){
    $this->redirect('newPreprocessing'.Strings::firstUpper($type),['miner'=>$miner,'column'=>$column]);
  }

  /**
   * Action for usage of the preprocessing "each value - one bin"
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
    //check, if the each value - one bin preprocessing already exists
    $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($format);
    $this->template->preprocessing=$preprocessing;
    /** @var Form $form */
    $form=$this->getComponent('newAttributeForm');
    $form->setDefaults([
      'miner'=>$miner->minerId,
      'column'=>$column,
      'preprocessing'=>$preprocessing->preprocessingId,
      'attributeName'=>$datasourceColumn->name,
      'supportLongNames'=>$this->metasourcesFacade->metasourceSupportsLongNames($miner->metasource)?'1':'0'
    ]);
  }

  /**
   * Action for usage of the preprocessing "interval enumeration"
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
      'attributeName'=>$datasourceColumn->name,
      'supportLongNames'=>$this->metasourcesFacade->metasourceSupportsLongNames($miner->metasource)?'1':'0'
    ));
  }

  /**
   * Action for usage of the preprocessing "equidistant intervals"
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

    $definitionRangeInterval=$format->getAllIntervalsRange();

    /** @var Form $form */
    $form=$this->getComponent('newEquidistantIntervalsForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'attributeName'=>$datasourceColumn->name,
      'supportLongNames'=>$this->metasourcesFacade->metasourceSupportsLongNames($miner->metasource)?'1':'0',
      'minLeftMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->leftMargin:''),
      'maxRightMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->rightMargin:''),
      'equidistantLeftMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->leftMargin:''),
      'equidistantRightMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->rightMargin:''),
      'equidistantIntervalsCount'=>5
    ));
  }

  /**
   * Action for usage of the preprocessing "equidistant intervals"
   * @param int $miner
   * @param int $column
   * @throws BadRequestException
   */
  public function renderNewPreprocessingEquifrequentIntervals($miner, $column){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $format=$datasourceColumn->format;
    $this->template->format=$format;

    $definitionRangeInterval=$format->getAllIntervalsRange();

    /** @var Form $form */
    $form=$this->getComponent('newEquifrequentIntervalsForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'attributeName'=>$datasourceColumn->name,
      'supportLongNames'=>$this->metasourcesFacade->metasourceSupportsLongNames($miner->metasource)?'1':'0',
      'minLeftMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->leftMargin:''),
      'maxRightMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->rightMargin:''),
      'equifrequentLeftMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->leftMargin:''),
      'equifrequentRightMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->rightMargin:''),
      'equifrequentIntervalsCount'=>5
    ));
  }

  /**
   * Action for usage of the preprocessing "equidistant intervals"
   * @param int $miner
   * @param int $column
   * @throws BadRequestException
   */
  public function renderNewPreprocessingEquisizedIntervals($miner, $column){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);
    $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
    $this->template->datasourceColumn=$datasourceColumn;
    $format=$datasourceColumn->format;
    $this->template->format=$format;

    $definitionRangeInterval=$format->getAllIntervalsRange();

    /** @var Form $form */
    $form=$this->getComponent('newEquisizedIntervalsForm');
    $form->setDefaults(array(
      'miner'=>$miner->minerId,
      'column'=>$column,
      'attributeName'=>$datasourceColumn->name,
      'supportLongNames'=>$this->metasourcesFacade->metasourceSupportsLongNames($miner->metasource)?'1':'0',
      'minLeftMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->leftMargin:''),
      'maxRightMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->rightMargin:''),
      'equisizedLeftMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->leftMargin:''),
      'equisizedRightMargin'=>($definitionRangeInterval instanceof Interval?$definitionRangeInterval->rightMargin:''),
      'equisizedIntervalsCount'=>5
    ));
  }

  /**
   * Action for usage of the preprocessing "nominal enumeration"
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
    //prepare the complete list of values
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
      'attributeName'=>$datasourceColumn->name,
      'supportLongNames'=>$this->metasourcesFacade->metasourceSupportsLongNames($miner->metasource)?'1':'0'
    ));
  }

  /**
   * Action for creation of a new attribute based on an existing preprocessing
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
      'attributeName'=>$datasourceColumn->name,
      'supportLongNames'=>$this->metasourcesFacade->metasourceSupportsLongNames($miner->metasource)?'1':'0'
    ));
  }

  /**
   * Action for creation of one attribute (display list of possible preprocessing types)
   * @param int $miner
   * @param int|null $column=null
   * @param string|null $columnName=null
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function renderAddAttribute($miner,$column=null,$columnName=null){
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);

    $this->minersFacade->checkMinerState($miner, $this->getCurrentUser());

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
    $currentUser=$this->getCurrentUser();

    if (!$format){
      //format initialization (meta-attribute association)
      //TODO implementovat podporu automatického mapování
      $format=$this->metaAttributesFacade->simpleCreateMetaAttributeWithFormatFromDatasourceColumn($datasourceColumn, $currentUser);
      $datasourceColumn->format=$format;
      $this->datasourcesFacade->saveDatasourceColumn($datasourceColumn);
    }

    $this->template->format=$format;
    $this->template->preprocessings=$this->metaAttributesFacade->findPreprocessingsForUser($format,$this->user->id);
    $this->template->supportedPreprocessingTypes=$this->metasourcesFacade->getSupportedPreprocessingTypes($miner->metasource, $currentUser);
  }


  /**
   * Action for creation of more attributes (in one step, displays table of datasource columns and list of possible preprocessing types)
   * @param int $miner
   * @param int[]|null $columns=null
   * @param string[]|null $columnNames=null
   * @throws BadRequestException
   * @throws ForbiddenRequestException
   */
  public function renderAddAttributes($miner, $columns=null, $columnNames=null) {
    /** @var Miner $miner */
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);

    $this->minersFacade->checkMinerState($miner, $this->getCurrentUser());

    $this->template->miner=$miner;
    $this->template->metasource=$miner->metasource;
    /** @var DatasourceColumn[] $datasourceColumns */
    $datasourceColumns=[];

    if (trim(@$columns,' ,;')!=''){
      //process the datasource columns by their IDs
      $columns=explode(',',str_replace(';',',',$columns));
      if (!empty($columns)){
        foreach($columns as $column){
          $datasourceColumns[]=$this->datasourcesFacade->findDatasourceColumn($miner->datasource,$column);
        }
      }
    }elseif(trim(@$columnNames,' ,;')!=''){
      //process the datasource columns by their names
      $columnNames=explode(',',str_replace(';',',',$columnNames));
      if (!empty($columnNames)){
        foreach($columnNames as $columnName){
          $datasourceColumns[]=$this->datasourcesFacade->findDatasourceColumnByName($miner->datasource,$columnName);
        }
      }
    }
    if (empty($datasourceColumns)){
      throw new BadRequestException('No data columns found.');
    }
    $datasourceColumnsIds=[];
    $currentUser=$this->getCurrentUser();
    foreach($datasourceColumns as $datasourceColumn){
      $datasourceColumnsIds[]=$datasourceColumn->datasourceColumnId;

      //check, if each datasource column if association with a format
      if (empty($datasourceColumn->format)){
        //format initialization (meta-attribute association)
        //TODO support for automatic mapping
        $format=$this->metaAttributesFacade->simpleCreateMetaAttributeWithFormatFromDatasourceColumn($datasourceColumn, $currentUser);
        $datasourceColumn->format=$format;
        $this->datasourcesFacade->saveDatasourceColumn($datasourceColumn);
      }
    }
    $this->template->datasourceColumns=$datasourceColumns;
    $this->template->datasourceColumnsIds=implode(',',$datasourceColumnsIds);
    $this->template->miner=$miner;
    //TODO kontrola, jestli je možné nabídnout další typy preprocessingu...
  }

  /**
   * @param int $miner
   * @param int[]|int|string $columns
   * @param $type
   */
  public function actionPreprocessAttributes($miner,$columns,$type) {
    if (is_string($columns)){
      $columns=explode(',',str_replace(';',',',trim($columns,' ;,')));
    }
    /** @var Miner $miner */
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);

    if ($type==Preprocessing::SPECIALTYPE_EACHONE){
      #region preprocessing eachOne
      $newAttributesArr=[];
      $metasource=$miner->metasource;
      foreach($columns as $column){
        $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$column);
        $this->template->datasourceColumn=$datasourceColumn;
        $format=$datasourceColumn->format;
        $preprocessing=$this->metaAttributesFacade->findPreprocessingEachOne($format);
        //připravíme příslušný atribut
        $attribute=new Attribute();
        $attribute->metasource=$metasource;
        $attribute->datasourceColumn=$datasourceColumn;
        $attribute->name=$this->minersFacade->prepareNewAttributeName($miner,$datasourceColumn->name);
        $attribute->type=$attribute->datasourceColumn->type;
        $attribute->preprocessing=$preprocessing;
        $attribute->active=false;
        $this->metasourcesFacade->saveAttribute($attribute);
        $newAttributesArr[]=$attribute;
      }
      $metasourceTask=$this->metasourcesFacade->startAttributesPreprocessing($metasource,$newAttributesArr);
      $this->redirect('preprocessingTask',['id'=>$metasourceTask->metasourceTaskId]);
      #endregion
    }else{
      throw new NotImplementedException();
    }
  }

  /**
   * Action for display "wait" template
   * @param $id
   */
  public function renderPreprocessingTask($id) {
    $this->template->metasourceTask=$this->metasourcesFacade->findMetasourceTask($id);
  }

  /**
   * Action for miner initialization (metasource creation)
   * @param int $id - PrepocessingTask ID
   * @throws BadRequestException
   */
  public function actionPreprocessingTaskRun($id) {
    $metasourceTask=$this->metasourcesFacade->findMetasourceTask($id);
    $metasourceTask=$this->metasourcesFacade->preprocessAttributes($metasourceTask);
    switch($metasourceTask->state){
      case MetasourceTask::STATE_DONE:
        //task finished => delete it and reload the UI
        $this->metasourcesFacade->deleteMetasourceTask($metasourceTask);
        $this->sendJsonResponse(['redirect'=>$this->link('reloadUI')]);
        return;
      case MetasourceTask::STATE_IN_PROGRESS:
        $this->sendJsonResponse(['message'=>$metasourceTask->getPpTask()->statusMessage,'state'=>$metasourceTask->state]);
        return;
      default:
        throw new BadRequestException();
    }
  }


  /**
   * Function for retrievind the relevant DatasourceColumn or return an error
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
   * Form for definition of a new preprocessing using interval enumeration
   * @return Form
   */
  protected function createComponentNewIntervalEnumerationForm(){
    $form=new Form();
    $form->setTranslator($this->translator);
    $supportLongNamesInput=$form->addHidden('supportLongNames','0');
    $form->addText('preprocessingName','Preprocessing name:')
      ->setRequired(false)
      ->setAttribute('placeholder',$this->translate('Interval bins'))
      ->setAttribute('title',$this->translate('You can left this field blank, it will be filled in automatically.'))
      ->addRule(function(TextInput $textInput){
        $form=$textInput->getForm(true);
        $formValues=$form->getValues(true);
        $textInputValue=$textInput->value;
        //find the relevant format
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
    $attributeNameInput=$form->addText('attributeName','Create attribute with name:');
    $attributeNameInput
      ->setRequired('Input attribute name!')
      ->addRule(function(TextInput $input){
        //check, if there is an existing attribute with the given name
        $values=$input->getForm(true)->getValues();
        $miner=$this->findMinerWithCheckAccess($values->miner);
        $attributes=$miner->metasource->attributes;
        if (!empty($attributes)){
          foreach ($attributes as $attribute){
            if ($attribute->active && $attribute->name==$input->value){
              return false;
            }
          }
        }
        return true;
      },'Attribute with this name already exists!');
    $attributeNameInput
      ->addConditionOn($supportLongNamesInput,Form::NOT_EQUAL,'1')
        ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
        ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::SHORT_NAMES_MAX_LENGTH)
      ->elseCondition()
        ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::LONG_NAMES_MAX_LENGTH)
      ->endCondition();

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
          //check, if each BIN has an unique name
          /** @noinspection PhpUndefinedMethodInspection */
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
      /** @noinspection PhpUndefinedMethodInspection */
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
          /** @noinspection PhpUndefinedMethodInspection */
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
            /** @noinspection PhpUndefinedMethodInspection */
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
            //check overlap of the intervals
            /** @noinspection PhpUndefinedMethodInspection */
            $parentValues=$input->getParent()->getValues(true);
            $interval=Interval::create($parentValues['leftBound'],$parentValues['leftValue'],$parentValues['rightValue'],$parentValues['rightBound']);
            /** @noinspection PhpUndefinedMethodInspection */
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
        /** @noinspection PhpUndefinedMethodInspection */
        $interval=$intervals->createOne();
        /** @noinspection PhpUndefinedMethodInspection */
        $interval->setValues([
          'leftBound'=>$values['leftBound'],
          'rightBound'=>$values['rightBound'],
          'leftValue'=>$values['leftValue'],
          'rightValue'=>$values['rightValue'],
          'text'=>StringsHelper::formatIntervalString($values['leftBound'],$values['leftValue'],$values['rightValue'],$values['rightBound'])
        ]);
        $intervalsForm->setValues(['leftValue'=>'','rightValue'=>'']);
      };
      /** @noinspection PhpUndefinedMethodInspection */
      $valuesBin->addSubmit('remove','Remove bin')
        ->setAttribute('class','removeBin')
        ->setValidationScope([])
        ->onClick[]=function(SubmitButton $submitButton){
        /** @noinspection PhpUndefinedMethodInspection */
        $submitButton->getParent()->getParent()->remove($submitButton->getParent(),true);
      };
    }, 0);

    $valuesBins->addSubmit('addBin','Add bin')
      ->setValidationScope([])
      ->onClick[]=function(SubmitButton $submitButton){
      /** @noinspection PhpUndefinedMethodInspection */
      $submitButton->getParent()->createOne();
    };
    $form->addSubmit('submitAll','Save preprocessing & create attribute')
      ->onClick[]=function(SubmitButton $submitButton){
      #region preprocessing creation
      $values=$submitButton->getForm(true)->getValues(true);
      $miner=$this->findMinerWithCheckAccess($values['miner']);
      $this->minersFacade->checkMinerMetasource($miner);

      //create preprocessing
      $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$values['column']);
      $format=$datasourceColumn->format;

      $preprocessing=new Preprocessing();
      $preprocessing->name=($values['preprocessingName']!=''?$values['preprocessingName']:$this->prepareIntervalEnumerationPreprocessingName($format,$values));
      $preprocessing->format=$format;
      $preprocessing->user=$this->getCurrentUser();
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

      //create attribute
      $attribute=new Attribute();
      $attribute->metasource=$miner->metasource;
      $attribute->datasourceColumn=$datasourceColumn;
      $attribute->name=$values['attributeName'];
      $attribute->type=$attribute->datasourceColumn->type;
      $attribute->preprocessing=$preprocessing;
      $attribute->active=false;
      $this->metasourcesFacade->saveAttribute($attribute);

      //start preprocessing task
      $metasourceTask=$this->metasourcesFacade->startAttributesPreprocessing($miner->metasource,[$attribute]);
      $this->redirect('preprocessingTask',['id'=>$metasourceTask->metasourceTaskId]);
      #endregion preprocessing creation
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
   * Form for definition of a preprocessing using equidistant intervals
   * @return Form
   */
  protected function createComponentNewEquidistantIntervalsForm(){
    $form=new Form();
    $form->setTranslator($this->translator);
    $supportLongNamesInput=$form->addHidden('supportLongNames','0');
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
        //find the relevant format
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
    $attributeNameInput=$form->addText('attributeName','Create attribute with name:');
    $attributeNameInput
      ->setAttribute('class','normalWidth')
      ->setRequired('Input attribute name!')
      ->addRule(function(TextInput $input){
        //check, if there is an existing attribute with the given name
        $values=$input->getForm(true)->getValues();
        $miner=$this->findMinerWithCheckAccess($values->miner);
        $attributes=$miner->metasource->attributes;
        if (!empty($attributes)){
          foreach ($attributes as $attribute){
            if ($attribute->active && $attribute->name==$input->value){
              return false;
            }
          }
        }
        return true;
      },'Attribute with this name already exists!');
    $attributeNameInput
      ->addConditionOn($supportLongNamesInput,Form::NOT_EQUAL,'1')
        ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
        ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::SHORT_NAMES_MAX_LENGTH)
      ->elseCondition()
        ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::LONG_NAMES_MAX_LENGTH)
      ->endCondition();

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
      ->setRequired('You have to input an integer value bigger than 1!')
      ->addRule(Form::INTEGER,'You have to input an integer value bigger than 1!')
      ->addRule(Form::MIN,'You have to input an integer value bigger than 1!',2);

    $form->addSubmit('submit','Save preprocessing & create attribute')
      ->onClick[]=function(SubmitButton $submitButton){
      #region preprocessing creation
      $values=$submitButton->getForm(true)->getValues(true);
      //find the relevant format
      $miner=$this->findMinerWithCheckAccess($values['miner']);
      $this->minersFacade->checkMinerMetasource($miner);
      $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$values['column']);
      $format=$datasourceColumn->format;

      //create preprocessing
      $preprocessing=new Preprocessing();
      $preprocessing->name=($values['preprocessingName']!=''?$values['preprocessingName']:$this->prepareEquidistantPreprocessingName($format,$values));
      $preprocessing->format=$format;
      $preprocessing->user=$this->getCurrentUser();
      $this->preprocessingsFacade->savePreprocessing($preprocessing);

      //create all intervals in the form of standalone bins
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
          //generate the interval to the end
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

      //create attribute
      $attribute=new Attribute();
      $attribute->metasource=$miner->metasource;
      $attribute->datasourceColumn=$datasourceColumn;
      $attribute->name=$values['attributeName'];
      $attribute->type=$attribute->datasourceColumn->type;
      $attribute->preprocessing=$preprocessing;
      $attribute->active=false;
      $this->metasourcesFacade->saveAttribute($attribute);

      //start preprocessing task
      $metasourceTask=$this->metasourcesFacade->startAttributesPreprocessing($miner->metasource,[$attribute]);
      $this->redirect('preprocessingTask',['id'=>$metasourceTask->metasourceTaskId]);
      #endregion preprocessing creation
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
   * Form for definition of a preprocessing using equifrequent intervals
   * @return Form
   */
  protected function createComponentNewEquifrequentIntervalsForm(){
    $form=new Form();
    $form->setTranslator($this->translator);
    $supportLongNamesInput=$form->addHidden('supportLongNames','0');
    $form->addHidden('column');
    $form->addHidden('miner');
    $form->addHidden('minLeftMargin');
    $form->addHidden('maxRightMargin');

    $form->addText('preprocessingName','Preprocessing name:')
      ->setRequired(false)
      ->setAttribute('placeholder',$this->translate('Equifrequent intervals'))
      ->setAttribute('title',$this->translate('You can left this field blank, it will be filled in automatically.'))
      ->addRule(function(TextInput $textInput){
        $form=$textInput->getForm(true);
        $formValues=$form->getValues(true);
        $textInputValue=$textInput->value;
        //find the relevant format
        $miner=$this->findMinerWithCheckAccess($formValues['miner']);
        $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$formValues['column']);
        $format=$datasourceColumn->format;
        $textInput->setAttribute('placeholder',$this->prepareEquifrequentPreprocessingName($format,$formValues));
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
    $attributeNameInput=$form->addText('attributeName','Create attribute with name:');
    $attributeNameInput
      ->setAttribute('class','normalWidth')
      ->setRequired('Input attribute name!')
      ->addRule(function(TextInput $input){
        //check, if there is an existing attribute with the given name
        $values=$input->getForm(true)->getValues();
        $miner=$this->findMinerWithCheckAccess($values->miner);
        $attributes=$miner->metasource->attributes;
        if (!empty($attributes)){
          foreach ($attributes as $attribute){
            if ($attribute->active && $attribute->name==$input->value){
              return false;
            }
          }
        }
        return true;
      },'Attribute with this name already exists!');
    $attributeNameInput
      ->addConditionOn($supportLongNamesInput,Form::NOT_EQUAL,'1')
      ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
      ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::SHORT_NAMES_MAX_LENGTH)
      ->elseCondition()
      ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::LONG_NAMES_MAX_LENGTH)
      ->endCondition();

    $form->addText('equifrequentLeftMargin','Equifrequent intervals from:')
      ->setRequired('You have to input number!')
      ->addRule(Form::FLOAT,'You have to input number!')
      ->addRule(function(TextInput $textInput){
        $values=$textInput->getForm(true)->getValues(true);
        if ($values['equifrequentLeftMargin']<$values['minLeftMargin'] || $values['equifrequentLeftMargin']>=$values['maxRightMargin']){
          return false;
        }
        return true;
      },'Start value cannot be out of the data range!');

    $form->addText('equifrequentRightMargin','Equifrequent intervals to:')
      ->setRequired('You have to input number!')
      ->addRule(Form::FLOAT,'You have to input number!')
      ->addRule(function(TextInput $textInput){
        $values=$textInput->getForm(true)->getValues(true);
        if ($values['equifrequentRightMargin']>$values['maxRightMargin'] || $values['equifrequentRightMargin']<=$values['minLeftMargin']){
          return false;
        }
        return true;
      },'End value cannot be out of the data range!')
      ->addRule(function(TextInput $textInput){
        $values=$textInput->getForm(true)->getValues(true);
        if ($values['equifrequentLeftMargin']>=$values['equifrequentRightMargin']){
          return false;
        }
        return true;
      },'Start value has to be lower than end value!');

    $form->addText('equifrequentIntervalsCount','Intervals count:')
      ->setRequired('You have to input an integer value bigger than 1!')
      ->addRule(Form::INTEGER,'You have to input an integer value bigger than 2!')
      ->addRule(Form::MIN,'You have to input an integer value bigger than 2!',2);

    $form->addSubmit('submit','Save preprocessing & create attribute')
      ->onClick[]=function(SubmitButton $submitButton){
      #region preprocessing creation
      $values=$submitButton->getForm(true)->getValues(true);
      //find the relevant format
      $miner=$this->findMinerWithCheckAccess($values['miner']);
      $this->minersFacade->checkMinerMetasource($miner);
      $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$values['column']);
      $format=$datasourceColumn->format;

      //create preprocessing
      $preprocessing=$this->metaAttributesFacade->generateNewPreprocessingFromDefinitionArray($format,[
        'type'=>Preprocessing::TYPE_EQUIFREQUENT_INTERVALS,
        'from'=>$values['equifrequentLeftMargin'],
        'to'=>$values['equifrequentRightMargin'],
        'count'=>$values['equifrequentIntervalsCount']
      ]);
      $preprocessing->name=($values['preprocessingName']!=''?$values['preprocessingName']:$this->prepareEquidistantPreprocessingName($format,$values));
      $preprocessing->format=$format;
      $preprocessing->user=$this->getCurrentUser();
      $this->preprocessingsFacade->savePreprocessing($preprocessing);

      //create attribute
      $attribute=new Attribute();
      $attribute->metasource=$miner->metasource;
      $attribute->datasourceColumn=$datasourceColumn;
      $attribute->name=$values['attributeName'];
      $attribute->type=$attribute->datasourceColumn->type;
      $attribute->preprocessing=$preprocessing;
      $attribute->active=false;
      $this->metasourcesFacade->saveAttribute($attribute);

      //start preprocessing task
      $metasourceTask=$this->metasourcesFacade->startAttributesPreprocessing($miner->metasource,[$attribute]);
      $this->redirect('preprocessingTask',['id'=>$metasourceTask->metasourceTaskId]);
      #endregion preprocessing creation
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
   * Form for definition of a preprocessing using equisized intervals
   * @return Form
   */
  protected function createComponentNewEquisizedIntervalsForm(){
    //FIXME implementovat
  }

  /**
   * Form for definition of a new preprocessing using nominal enumeration
   * @return Form
   */
  protected function createComponentNewNominalEnumerationForm(){
    $form=new Form();
    $form->setTranslator($this->translator);
    $supportLongNamesInput=$form->addHidden('supportLongNames','0');
    $form->addText('preprocessingName','Preprocessing name:')
      ->setRequired(false)
      ->setAttribute('placeholder',$this->translate('Nominal bins'))
      ->setAttribute('title',$this->translate('You can left this field blank, it will be filled in automatically.'))
      ->addRule(function(TextInput $textInput){
        $form=$textInput->getForm(true);
        $formValues=$form->getValues(true);
        $textInputValue=$textInput->value;
        //find the relevant format
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
    $attributeNameInput=$form->addText('attributeName','Create attribute with name:');
    $attributeNameInput
      ->setRequired('Input attribute name!')
      ->addRule(function(TextInput $input){
        //check, if there is an attribute with the given name
        $values=$input->getForm(true)->getValues();
        $miner=$this->findMinerWithCheckAccess($values->miner);
        $attributes=$miner->metasource->attributes;
        if (!empty($attributes)){
          foreach ($attributes as $attribute){
            if ($attribute->active && $attribute->name==$input->value){
              return false;
            }
          }
        }
        return true;
      },'Attribute with this name already exists!');
    $attributeNameInput
      ->addConditionOn($supportLongNamesInput,Form::NOT_EQUAL,'1')
        ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
        ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::SHORT_NAMES_MAX_LENGTH)
      ->elseCondition()
        ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::LONG_NAMES_MAX_LENGTH)
      ->endCondition();
    $form->addHidden('column');
    $form->addHidden('miner');
    $form->addHidden('formatType');
    $form->addHidden('formatId');
    /** @var Container $valuesBins */
    /** @noinspection PhpUndefinedMethodInspection */
    $valuesBins=$form->addDynamic('valuesBins', function (Container $valuesBin){
      $valuesBin->addText('name','Bin name:')->setRequired(true)
        ->setRequired('Input bin name!')
        ->addRule(function(TextInput $input){
          /** @noinspection PhpUndefinedMethodInspection */
          $values=$input->parent->getValues(true);
          return (count($values['values'])>0);
        },'Add at least one value!')
        ->addRule(function(TextInput $input){
          //check, if each bin has a unique name
          /** @noinspection PhpUndefinedMethodInspection */
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
      /** @noinspection PhpUndefinedMethodInspection */
      $intervals=$valuesBin->addDynamic('values',function(Container $interval){
        $interval->addText('value')->setAttribute('readonly');
        /** @noinspection PhpUndefinedMethodInspection */
        $interval->addSubmit('remove','x')
          ->setAttribute('class','removeValue')
          ->setValidationScope([])
          ->onClick[]=function(SubmitButton $submitButton){
          /** @noinspection PhpUndefinedFieldInspection */
          $intervals = $submitButton->parent->parent;
          /** @noinspection PhpUndefinedMethodInspection */
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
        /** @noinspection PhpUndefinedMethodInspection */
        $values=$submitButton->getParent()->getValues(true);
        /** @noinspection PhpUndefinedMethodInspection */
        $valueItem=$submitButton->getParent()['values']->createOne();
        /** @noinspection PhpUndefinedMethodInspection */
        $valueItem->setValues([
          'value'=>$values['value']
        ]);
        /** @noinspection PhpUndefinedMethodInspection */
        $submitButton->getParent()->setValues(['value'=>'']);
      };
      /** @noinspection PhpUndefinedMethodInspection */
      $valuesBin->addSubmit('remove','Remove bin')
        ->setAttribute('class','removeBin')
        ->setValidationScope([])
        ->onClick[]=function(SubmitButton $submitButton){
        /** @noinspection PhpUndefinedMethodInspection */
        $submitButton->getParent()->getParent()->remove($submitButton->getParent(),true);
      };
    }, 0);

    $valuesBins->addSubmit('addBin','Add bin')
      ->setValidationScope([])
      ->onClick[]=function(SubmitButton $submitButton){
      /** @noinspection PhpUndefinedMethodInspection */
      $submitButton->getParent()->createOne();
    };
    $form->addSubmit('submitAll','Save preprocessing & create attribute')
      ->onClick[]=function(SubmitButton $submitButton){
      #region preprocessing creation
      $values=$submitButton->getForm(true)->getValues(true);
      $miner=$this->findMinerWithCheckAccess($values['miner']);
      $this->minersFacade->checkMinerMetasource($miner);

      //create preprocessing
      $datasourceColumn=$this->findDatasourceColumn($miner->datasource,$values['column']);
      $format=$datasourceColumn->format;

      $preprocessing=new Preprocessing();
      $preprocessing->name=($values['preprocessingName']!=''?$values['preprocessingName']:$this->prepareNominalEnumerationPreprocessingName($format,$values));
      $preprocessing->format=$format;
      $preprocessing->user=$this->getCurrentUser();
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

      //create attribute
      $attribute=new Attribute();
      $attribute->metasource=$miner->metasource;
      $attribute->datasourceColumn=$datasourceColumn;
      $attribute->name=$values['attributeName'];
      $attribute->type=$attribute->datasourceColumn->type;
      $attribute->preprocessing=$preprocessing;
      $attribute->active=false;
      $this->metasourcesFacade->saveAttribute($attribute);

      //start preprocessing task
      $metasourceTask=$this->metasourcesFacade->startAttributesPreprocessing($miner->metasource,[$attribute]);
      $this->redirect('preprocessingTask',['id'=>$metasourceTask->metasourceTaskId]);
      #endregion preprocessing creation
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
   * Function returning a form for creation of a new attribute based on selected DatasourceColumn and Preprocessing
   * @return Form
   */
  protected function createComponentNewAttributeForm(){
    $form = new Form();
    $presenter=$this;
    $form->setTranslator($this->translator);
    $supportLongNamesInput=$form->addHidden('supportLongNames','0');
    $form->addHidden('miner');
    $form->addHidden('column');
    $form->addHidden('preprocessing');
    $attributeNameInput=$form->addText('attributeName','Attribute name:');
    $attributeNameInput
      ->setRequired('Input attribute name!')
      ->addRule(function(TextInput $input){
        //check, if there is an attribute with the given name
        $values=$input->getForm(true)->getValues();
        $miner=$this->findMinerWithCheckAccess($values->miner);
        $attributes=$miner->metasource->attributes;
        if (!empty($attributes)){
          foreach ($attributes as $attribute){
            if ($attribute->active && $attribute->name==$input->value){
              return false;
            }
          }
        }
        return true;
      },'Attribute with this name already exists!');
    $attributeNameInput
      ->addConditionOn($supportLongNamesInput,Form::NOT_EQUAL,'1')
        ->addRule(Form::PATTERN,'Attribute name can contain only letters, numbers and _ and has start with a letter.','[a-zA-Z]{1}\w*')
        ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::SHORT_NAMES_MAX_LENGTH)
      ->elseCondition()
        ->addRule(Form::MAX_LENGTH,'Max length of the column name is %s characters.',MetasourcesFacade::LONG_NAMES_MAX_LENGTH)
      ->endCondition();
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
      $attribute->active=false;
      $this->metasourcesFacade->saveAttribute($attribute);

      $metasourceTask=$this->metasourcesFacade->startAttributesPreprocessing($miner->metasource,[$attribute]);
      $this->redirect('preprocessingTask',['id'=>$metasourceTask->metasourceTaskId]);
    };
    $storno=$form->addSubmit('storno','storno');
    $storno->setValidationScope(array());
    $storno->onClick[]=function(SubmitButton $button)use($presenter){
      //redirect to the preprocessing selection
      $values=$button->form->getValues();
      $presenter->redirect('addAttribute',array('column'=>$values->column,'miner'=>$values->miner));
    };
    return $form;
  }

  /**
   * Function for selection of the appropriate layout based on the "mode" attribute in the URL (normal or iframe view)
   */
  protected function beforeRender(){
    if ($this->mode=='component' || $this->mode=='iframe'){
      $this->layout='iframe';
      $this->template->layout='iframe';
    }
    parent::beforeRender();
  }

  /**
   * Function for generation of a default preprocessing name - for equidistant intervals
   * @param Format $format
   * @param array $formValues
   * @return string
   */
  private function prepareEquidistantPreprocessingName(Format $format,$formValues) {
    $preprocessingNameBase=$this->translate('Equidistant intervals ({:count}) from {:from} to {:to}');
    $preprocessingNameBase=str_replace(['{:count}','{:from}','{:to}'],[$formValues['equidistantIntervalsCount'],$formValues['equidistantLeftMargin'],$formValues['equidistantRightMargin']],$preprocessingNameBase);

    #region solving of the uniqueness of the name
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
    #endregion solving of the uniqueness of the name
    return $preprocessingName;
  }

  /**
   * Function for generation of a default preprocessing name - for equidistant intervals
   * @param Format $format
   * @param array $formValues
   * @return string
   */
  private function prepareEquifrequentPreprocessingName(Format $format,$formValues) {
    $preprocessingNameBase=$this->translate('Equifrequent intervals ({:count}) from {:from} to {:to}');
    $preprocessingNameBase=str_replace(['{:count}','{:from}','{:to}'],[$formValues['equidistantIntervalsCount'],$formValues['equidistantLeftMargin'],$formValues['equidistantRightMargin']],$preprocessingNameBase);

    #region solving of the uniqueness of the name
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
    #endregion solving of the uniqueness of the name
    return $preprocessingName;
  }

  /**
   * Function for generation of a default preprocessing name - for equidistant intervals
   * @param Format $format
   * @param array $formValues
   * @return string
   */
  private function prepareEquisizedPreprocessingName(Format $format,$formValues) {
    $preprocessingNameBase=$this->translate('Equisized intervals ({:count}) from {:from} to {:to}');
    $preprocessingNameBase=str_replace(['{:count}','{:from}','{:to}'],[$formValues['equidistantIntervalsCount'],$formValues['equidistantLeftMargin'],$formValues['equidistantRightMargin']],$preprocessingNameBase);

    #region solving of the uniqueness of the name
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
    #endregion solving of the uniqueness of the name
    return $preprocessingName;
  }

  /**
   * Function for generation of a default preprocessing name - for nominale enumeration
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

    #region solving of the uniqueness of the name
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
    #endregion solving of the uniqueness of the name
    return $preprocessingName;
  }

  /**
   * Function for generation of a default preprocessing name - for interval enumeration
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

    #region solving of the uniqueness of the name
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
    #endregion solving of the uniqueness of the name
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
  #endregion injections
} 