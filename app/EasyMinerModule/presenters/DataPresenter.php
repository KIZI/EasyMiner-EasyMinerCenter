<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\Data\Entities\DbField;
use EasyMinerCenter\Model\Data\Entities\DbValue;
use EasyMinerCenter\Model\Data\Facades\FileImportsFacade;
use EasyMinerCenter\Model\Data\Files\CsvImport;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\MetasourceTask;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\InvalidStateException;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class DataPresenter
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class DataPresenter extends BasePresenter{
  use ResponsesTrait;
  use MinersFacadeTrait;
  use UsersTrait;

  const HISTOGRAM_MAX_COLUMNS_COUNT=1000;
  const VALUES_TABLE_VALUES_PER_PAGE=200;

  /** @var  FileImportsFacade $fileImportsFacade */
  private $fileImportsFacade;
  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;

  /**
   * @var string $mode
   * @persistent
   */
  public $mode='default';

  #region new miner creation

  /**
   * Action for initialization of the selected miner (checks state of metasource)
   * @param int $id
   * @throws BadRequestException
   */
  public function actionInitMiner($id) {
    //find the miner and check user privileges
    $miner=$this->findMinerWithCheckAccess($id);
    //check the existence of metasource
    if ($miner->metasource){
      //it is miner with an existing metasource => check the state of the metasource
      switch ($miner->metasource->state){
        case Metasource::STATE_AVAILABLE:
          $this->redirect('openMiner',['id'=>$id]);
          return;
        case Metasource::STATE_PREPARATION:
          $metasourceTasks=$miner->metasource->metasourceTasks;
          if (!empty($metasourceTasks)){
            foreach($metasourceTasks as $metasourceTaskItem){
              if (empty($metasourceTaskItem->attributes)){//TODO tohle je potřeba opravit (došlo ke změně konstrukce MetasourceTask, žádný attribute už tam není)
                $metasourceTask=$metasourceTaskItem;
                break;
              }
            }
          }
          break;
        case Metasource::STATE_UNAVAILABLE:
          throw new BadRequestException('Requested miner is not available!');
      }
    }
    if (empty($metasourceTask)){
      //metasource does not exist
      $metasourceTask=$this->metasourcesFacade->startMinerMetasourceInitialization($miner, $this->minersFacade);
    }
    $this->template->miner=$miner;
    $this->template->metasourceTask=$metasourceTask;
  }

  /**
   * Akce pro inicializaci mineru (vytvoření metasource) - může trvat delší dobu...
   * Action for
   * @param int $id - miner ID
   * @param int $task - long running task ID
   * @throws BadRequestException
   * @throws \Exception
   */
  public function actionInitMinerMetasource($id,$task) {
    $miner = $this->findMinerWithCheckAccess($id);
    $metasourceTask=$this->metasourcesFacade->findMetasourceTask($task);
    $metasourceTask=$this->metasourcesFacade->initializeMinerMetasource($miner, $metasourceTask);
    if ($metasourceTask->state==MetasourceTask::STATE_DONE){
      //task has been finished - redirect the user to the created miner
      $this->metasourcesFacade->deleteMetasourceTask($metasourceTask);
      $this->sendJsonResponse(['redirect'=>$this->link('openMiner',['id'=>$miner->minerId])]);
    }elseif($metasourceTask->state==MetasourceTask::STATE_IN_PROGRESS){
      //task is still running
      $this->sendJsonResponse(['message'=>$metasourceTask->getPpTask()->statusMessage,'state'=>$metasourceTask->state]);
    }else{
      throw new BadRequestException();
    }
  }


  /**
   * Action for redirect to new miner creation
   */
  public function actionNewMiner() {
    $this->redirect('default');
  }

  /**
   * Action for opening of an existing instance of miner or creation a new one
   */
  public function renderDefault(){
    $currentUser=$this->getCurrentUser();
    $this->template->miners=$this->minersFacade->findMinersByUser($currentUser);
    $this->datasourcesFacade->updateRemoteDatasourcesByUser($currentUser);
    $this->template->datasources=$this->datasourcesFacade->findDatasourcesByUser($currentUser, true);
    $this->template->dbTypes=$this->datasourcesFacade->getDbTypes(true);
  }

  /**
   * Action for creation of a new miner based on an existing datasource
   * @param int $datasource
   * @throws BadRequestException
   */
  public function renderNewMinerFromDatasource($datasource){
    try{
      $datasource=$this->datasourcesFacade->findDatasourceWithCheckAccess($datasource, $this->getCurrentUser());
    }catch (\Exception $e){
      throw new BadRequestException('Requested datasource was not found!',404,$e);
    }
    $this->template->datasource=$datasource;

    $availableMinerTypes = $this->minersFacade->getAvailableMinerTypes($datasource->type);
    if (empty($availableMinerTypes)){
      //error while selection of available miner driver
      $this->flashMessage('No suitable mining service found. Please update the configuration for support of this datasource type!','error');
      $this->redirect('default');
    }

    /** @var Form $form */
    $form=$this->getComponent('newMinerForm');
    $dateTime=new \DateTime();
    $form->setDefaults([
      'datasource'=>$datasource->datasourceId,
      'datasourceName'=>$datasource->type.': '.$datasource->name,
      'name'=>$datasource->name.' '.$dateTime->format('Y-m-d H:i:s')
    ]);
    /** @var SelectBox $typeSelect */
    $typeSelect=$form->getComponent('type');
    $typeSelect->setItems($availableMinerTypes);
  }

  /**
   * Factory method for creation of a form for creation of a new miner from a concrete, existing datasource
   * @return Form
   * @throws \Exception
   */
  public function createComponentNewMinerForm() {
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addHidden('datasource');
    $form->addText('datasourceName','Datasource:')
      ->setAttribute('class','noBorder normalWidth')
      ->setAttribute('readonly');
    /** @noinspection PhpUndefinedMethodInspection */
    $form->addText('name', 'Miner name:')
      ->setRequired('Input the miner name!')
      ->setAttribute('autofocus')
      ->setAttribute('class','normalWidth')
      ->addRule(Form::MAX_LENGTH,'Max length of miner name is %s characters!',100)
      ->addRule(Form::MIN_LENGTH,'Min length of miner name is %s characters!',3)
      ->addRule(function(TextInput $control){
        try{
          $miner=$this->minersFacade->findMinerByName($this->getCurrentUser()->userId,$control->value);
          if ($miner instanceof Miner){
            return false;
          }
        }catch (\Exception $e){/*ignore error, it is OK*/}
        return true;
      },'Miner with this name already exists!');

    $availableMinerTypes=$this->minersFacade->getAvailableMinerTypes();
    if (empty($availableMinerTypes)){
      throw new \Exception('No miner type found! Please check the configuration...');
    }
    $form->addSelect('type','Miner type:',$availableMinerTypes)
      ->setAttribute('class','normalWidth');

    $form->addSubmit('submit','Create miner...')
      ->onClick[]=function(SubmitButton $submitButton){
      /** @var Form $form */
      $form=$submitButton->form;
      $values=$form->getValues();
      $miner=new Miner();
      $miner->user=$this->getCurrentUser();
      $miner->name=$values['name'];
      $miner->datasource=$this->datasourcesFacade->findDatasource($values['datasource']);
      $miner->created=new \DateTime();
      $miner->type=$values['type'];
      $this->minersFacade->saveMiner($miner);
      $this->redirect('Data:initMiner',['id'=>$miner->minerId]);
    };
    $form->addSubmit('storno','storno')
      ->setValidationScope(array())
      ->onClick[]=function(SubmitButton $button){
      /** @var Presenter $presenter */
      $presenter=$button->form->getParent();
      $presenter->redirect('Data:');
    };
    return $form;
  }
  #endregion new miner creation

  #region data upload

  /**
   * Action after upload finish (process the gained JSON, go to new datasource creation)
   * @param string $uploadConfig
   * @param string $dataServiceResult
   * @throws \Exception
   * @throws \Nette\Utils\JsonException
   */
  public function actionUploadFinish($uploadConfig,$dataServiceResult){
    $uploadConfig=Json::decode($uploadConfig,Json::FORCE_ARRAY);
    $dataServiceResult=Json::decode($dataServiceResult,Json::FORCE_ARRAY);
    $currentUser=$this->getCurrentUser();
    //create a new dataset and save it to DB
    if (!empty($dataServiceResult['id'])&&!empty($dataServiceResult['type'])&&!empty($dataServiceResult['name'])){
      $dbDatasource=new DbDatasource(@$dataServiceResult['id'],@$dataServiceResult['name'],@$dataServiceResult['type'],@$dataServiceResult['size']);
      $datasource=$this->datasourcesFacade->prepareNewDatasourceFromDbDatasource($dbDatasource,$currentUser);
      $this->datasourcesFacade->saveDatasource($datasource);
    }else{
      throw new \Exception('Upload finish failed.');
    }

    //refresh of names of data columns
    if (!empty($uploadConfig['columnNames'])){
      $datasourceColumnNames=[];
      //create list of data columns
      foreach($uploadConfig['dataTypes'] as $i=>$dataType){
        if ($dataType==DbField::TYPE_NOMINAL || $dataType==DbField::TYPE_NUMERIC){
          $datasourceColumnNames[]=$uploadConfig['columnNames'][$i];
        }
      }
      $this->datasourcesFacade->renameDatasourceColumns($datasource,$datasourceColumnNames,$currentUser);
    }
    //rend redirect URL to frontend
    $this->sendJsonResponse(['state'=>'OK','message'=>'Datasource created successfully.','redirect'=>$this->link('Data:newMinerFromDatasource',['datasource'=>$datasource->datasourceId])]);
  }

  /**
   * Action for rendering of upload form
   */
  public function renderUpload() {
    /** @noinspection PhpUndefinedFieldInspection */
    $this->template->apiKey=$this->user->getIdentity()->apiKey;
    $this->template->dataServicesConfigByDbTypes=$this->datasourcesFacade->getDataServicesConfigByDbTypes();
    $this->template->zipSupport=($this->datasourcesFacade->getDatabaseUnzipServiceType()!='');
  }

  /**
   * Action for preparation of data preview from the uploaded file - works with locally uploaded files
   * @param string $file
   * @param string $delimiter
   * @param string $encoding
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string $nullValue
   * @param string $locale
   * @param string $requireSafeNames
   * @param int $rowsCount = 20
   * @throws BadRequestException
   */
  public function actionPreviewData($file,$delimiter='',$encoding='utf-8',$enclosure='"',$escapeCharacter='\\',$nullValue='',$locale='en',$rowsCount=20,$requireSafeNames='') {
    //TODO detekce locale... (podle desetinné tečky/čárky...)
    if ($delimiter==''){
      //default separator change
      $delimiter=$this->fileImportsFacade->getCSVDelimiter($file);
    }
    //change the file encoding
    $this->fileImportsFacade->changeFileEncoding($file,$encoding);
    //create array with results
    $resultArr=[
      'config'=>[
        'delimiter'=>$delimiter,
        'encoding'=>$encoding,
        'enclosure'=>$enclosure,
        'escapeCharacter'=>$escapeCharacter,
        'nullValue'=>$nullValue,
        'locale'=>$locale
      ],
      'columnNames'=>[],
      'dataTypes'=>[],
      'data'=>[]
    ];
    //read info about data columns
    $columns=$this->fileImportsFacade->getColsInCSV($file,$delimiter,$enclosure,$escapeCharacter,$rowsCount+1,(bool)$requireSafeNames,$nullValue);
    if (empty($columns)){
      throw new BadRequestException('No columns detected!');
    }
    foreach($columns as $column) {
      $resultArr['columnNames'][]=$column->name;
      $resultArr['dataTypes'][]=($column->type==DbField::TYPE_NOMINAL?'nominal':'numeric');
    }
    //read required data lines
    $resultArr['data']=$this->fileImportsFacade->getRowsFromCSV($file,$rowsCount,$delimiter,$enclosure,$escapeCharacter,$nullValue,1);
    //send response
    $this->sendJsonResponse($resultArr);
  }

  /**
   * Action accepting a part of data file, the data are saved in a file
   * @param string $compression=''
   */
  public function actionUploadPreview($compression='') {
    $previewRawData=$this->getHttpRequest()->getRawBody();

    if ($compression!=''){
      $previewRawData=$this->datasourcesFacade->unzipPreviewData($previewRawData,$compression,$this->getCurrentUser());
    }

    $filename=$this->fileImportsFacade->saveTempFile($previewRawData);


    $this->sendJsonResponse(['file'=>$filename]);
  }

  /**
   * Factory method for upload form
   * @return Form
   * @throws \Exception
   */
  public function createComponentUploadForm() {
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addUpload('file','Upload file:')
      ->setRequired('Je nutné vybrat soubor pro import!')
    ;

    $dataServicesConfig=$this->datasourcesFacade->getDataServicesConfigByDbTypes();
    $importTypes=[];
    if (!empty($dataServicesConfig)){
      foreach($dataServicesConfig as $dbType=>$dbConfig){
        if (!empty($dbConfig['supportedImportTypes'])){
          foreach($dbConfig['supportedImportTypes'] as $importType){
            $importTypes[$importType]=Strings::upper($importType);
          }
        }
      }
    }
    if (count($importTypes)==0){
      throw new \Exception('Invalid configuration od databases / data services.');
    }
    $importTypeField=null;
    if (count($importTypes)==1){
      foreach($importTypes as $key=>$value){
        $importTypeField=$form->addHidden('importType',$key);
        break;
      }
    }
    if (empty($importTypeField)){
      $importTypeField=$form->addSelect('importType','Data type:',$importTypes);
    }

    $dbTypes=$this->datasourcesFacade->getDbTypes(true);
    if (count($dbTypes)==1){
      reset($dbTypes);
      $form->addHidden('dbType',key($dbTypes));
    }else{
      $dbTypeSelect=$form->addSelect('dbType','Database type:',$dbTypes);
      $dbTypeSelect->setDefaultValue($this->datasourcesFacade->getPreferredDbType());
      $dbTypesByImportTypes=$this->datasourcesFacade->getDbTypesByImportTypes();
      if (!empty($dbTypesByImportTypes)){
        foreach($dbTypesByImportTypes as $importType=>$dbTypesArr){
          $dbTypeSelect->addConditionOn($importTypeField,Form::EQUAL,$importType)
            ->addRule(FORM::IS_IN,'Selected database does not support selected import type!',$dbTypesArr);
        }
      }
    }

    //add submit buttons
    $form->addSubmit('submit','Configure upload...')
      ->onClick[]=function(){
      //upload without javascript is not supported
      //nepodporujeme upload bez javascriptové konfigurace
      $this->flashMessage('For file upload using UI, you have to enable the javascript code!','error');
      $this->redirect('newMiner');
    };
    $form->addSubmit('cancel','Cancel')
      ->setValidationScope([])
      ->onClick[]=function(){
      //cancel the upload
      $this->redirect('Data:default');
    };
    return $form;
  }

  /**
   * Factory method creating form for configuration of the upload
   * @return Form
   */
  public function createComponentUploadConfigForm() {
    $form = new Form();
    $form->setTranslator($this->translator);
    $allowLongNamesInput=$form->addHidden('allowLongNames','0');
    $name=$form->addText('name','Datasource:')
      ->setAttribute('title','Datasource name')
      ->setRequired('Input datasource name!');
    /** @noinspection PhpUndefinedMethodInspection */
    $name->addRule(Form::MIN_LENGTH,'Min length of the table name is %s characters!',3);
    $name->addConditionOn($allowLongNamesInput,Form::EQUAL,'1')
      ->addRule(Form::MAX_LENGTH,'Max length of the table name is %s characters!',100)
      ->endCondition();
    $name->addConditionOn($allowLongNamesInput,Form::NOT_EQUAL,'1')
      ->addRule(Form::MAX_LENGTH,'Max length of the table name is %s characters!',30)
      ->addRule(Form::PATTERN,'Datasource name can contain only letters, numbers and underscore and start with a letter!','[a-zA-Z0-9_]+')
      ->endCondition();

    $form->addSelect('separator','Separator:',[
      ','=>'Comma (,)',
      ';'=>'Semicolon (;)',
      '|'=>'Vertical line (|)',
      chr(9) =>'Tab (\t)'
    ])->setRequired();

    $form->addSelect('encoding','Encoding:',[
      'utf8'=>'UTF-8',
      'cp1250'=>'WIN 1250',
      'iso-8859-1'=>'ISO 8859-1',
    ])->setRequired();

    $form->addText('enclosure','Enclosure:',1,1)
      ->setDefaultValue('"');
    $form->addText('escape','Escape:',1,1)
      ->setDefaultValue('\\');
    $nullValuesArr=CsvImport::getDefaultNullValuesArr();//XXX check
    $defaultNullValue='none';
    foreach($nullValuesArr as $value=>$text){
      $defaultNullValue=$value;
      break;
    }
    $form->addSelect('nullValue','Null values:',array_merge(['none'=>'--none--'],$nullValuesArr))
      ->setAttribute('title','This value will be imported as missing (null).')
      ->setDefaultValue($defaultNullValue);
    $form->addSelect('locale','Locale:',['en'=>'en (0.00)','cs'=>'cs (0,00)'])->setDefaultValue("en")
      ->setAttribute('title','Select, if you use decimal comma or point.');
    //přidání submit tlačítek
    $form->addSubmit('submit','Confirm upload config...')
      ->onClick[]=function(){
      //nepodporujeme upload bez javascriptové konfigurace
      $this->flashMessage('For file upload using UI, you have to enable the javascript code!','error');
      $this->redirect('newMiner');
    };
    return $form;
  }

  #endregion data upload


  #region open miner

  /**
   * Action for opening of an existing miner
   * @param int $id
   * @throws BadRequestException
   */
  public function actionOpenMiner($id){
    $miner=$this->findMinerWithCheckAccess($id);

    if (!$miner->metasource){
      //no metasource - redirect to initialization...
      $this->redirect('initMiner',['id'=>$id]);
      return;
    }else{
      //check metasource state
      $metasource=$miner->metasource;
      if (!empty($metasource->metasourceTasks)){
        $metasourceTasks=$metasource->metasourceTasks;
        foreach($metasourceTasks as $metasourceTask){
          if ($metasourceTask->type==MetasourceTask::TYPE_INITIALIZATION){
            //redirect to initialization...
            $this->redirect('initMiner',['id'=>$id]);
            return;
          }elseif($metasourceTask->type==MetasourceTask::TYPE_PREPROCESSING && !empty($metasourceTask->attributes)){
            //TODO nutnost přesměrování na inicializaci atributů
          }
        }
      }
      $attributesCount=count($metasource->attributes);
      try{
        $this->metasourcesFacade->checkMetasourceState($metasource);
      }catch (\Exception $e){
        if (!$attributesCount==0){
          $this->metasourcesFacade->deleteMetasource($metasource);
          $this->redirect('initMiner',['id'=>$id]);
        }else{
          throw new InvalidStateException('Requested miner is not available! (metasource error)',$e->getCode(),$e);
        }
      }
    }

    //update "lastOpened" timestamp
    $miner->lastOpened=new \DateTime();
    $this->minersFacade->saveMiner($miner);

    $this->redirect('MiningUi:',['id'=>$miner->minerId]);
  }
  #endregion open miner

  #region data access functions

  /**
   * Action for rendering of a histogram from values of a selected datasource column
   * @param int $miner - Miner ID
   * @param int $column - DatasourceColum ID
   * @param int $offset =0
   * @throws BadRequestException
   */
  public function renderColumnHistogram($miner, $column, $offset=0) {
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);

    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($miner->datasource,$column);
    if (!$datasourceColumn->active){
      throw new BadRequestException('Requested column is not available.');
    }

    $this->template->miner=$miner;
    $this->template->datasourceColumn=$datasourceColumn;

    /** @var DbValue[] $dbValues */
    $dbValues=$this->datasourcesFacade->getDatasourceColumnDbValues($datasourceColumn,$offset,self::HISTOGRAM_MAX_COLUMNS_COUNT);
    $dbValueValues=[];
    $dbValueFrequencies=[];
    if (!empty($dbValues)){
      foreach($dbValues as $dbValue){
        $dbValueValues[]=$dbValue->value;
        $dbValueFrequencies[]=$dbValue->frequency;
      }
    }
    $this->template->dbValueValues=$dbValueValues;
    $this->template->dbValueFrequencies=$dbValueFrequencies;
  }

  /**
   * Action for rendering of a histogram from values of a selected datasource column
   * @param int $miner - Miner ID
   * @param int $column - DatasourceColumn ID
   * @param int $offset =0
   * @throws BadRequestException
   */
  public function renderColumnValuesTable($miner, $column, $offset=0) {
    $miner=$this->findMinerWithCheckAccess($miner);
    $this->minersFacade->checkMinerMetasource($miner);

    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($miner->datasource,$column);
    if (!$datasourceColumn->active){
      throw new BadRequestException('Requested column is not available.');
    }

    $offset=intval($offset);

    $this->template->miner=$miner;
    $this->template->datasourceColumn=$datasourceColumn;
    $this->template->dbValues=$this->datasourcesFacade->getDatasourceColumnDbValues($datasourceColumn,$offset,self::VALUES_TABLE_VALUES_PER_PAGE);
    $this->template->offset=$offset;
    $this->template->valuesPerPage=self::VALUES_TABLE_VALUES_PER_PAGE;
  }


  
  #endregion data access functions

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
  
  #region injections
  /**
   * @param FileImportsFacade $fileImportsFacade
   */
  public function injectFileImportsFacade(FileImportsFacade $fileImportsFacade){
    $this->fileImportsFacade=$fileImportsFacade;
  }
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }
  /**
   * @param MetasourcesFacade $metasourcesFacade
   */
  public function injectMetasourcesFacade(MetasourcesFacade $metasourcesFacade) {
    $this->metasourcesFacade=$metasourcesFacade;
  }
  #endregion injections
}