<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;
use EasyMinerCenter\EasyMinerModule\Components\IMetaAttributesSelectControlFactory;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\Data\Facades\FileImportsFacade;
use EasyMinerCenter\Model\Data\Files\CsvImport;
////use EasyMinerCenter\Model\EasyMiner\Entities\DatasourceColumn;
////use EasyMinerCenter\Model\EasyMiner\Entities\Format;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Controls\UploadControl;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Nette\Utils\Strings;

/**
 * Class OLD_DataPresenter - K NÁHRADĚ NOVOU VERZÍ
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 */
class OLD_DataPresenter extends BasePresenter{
  use MinersFacadeTrait;

  /** @var DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  FileImportsFacade $fileImportsFacade */
  private $fileImportsFacade;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;
  /** @var  IMetaAttributesSelectControlFactory $iMetaAttributesSelectControlFactory */
  private $iMetaAttributesSelectControlFactory;

  /**
   * Akce pro vygenerování náhledu na data
   * @param string $file
   * @param string $separator
   * @param string $encoding
   * @param string $enclosure
   * @param string $escape
   * @param string $nullValue="none"
   */
  public function renderImportCsvDataPreview($file,$separator=',',$encoding='utf8',$enclosure='"',$escape='\\',$nullValue="none"){
    $this->layout='blank';
    $this->fileImportsFacade->changeFileEncoding($file,$encoding);
    $this->template->colsCount=$this->fileImportsFacade->getColsCountInCSV($file,$separator,$enclosure,$escape);
    $rows=$this->fileImportsFacade->getRowsFromCSV($file,20,$separator,$enclosure,$escape,($nullValue=='none'?null:$nullValue),0);
    $rows[0]=CsvImport::sanitizeColumnNames($rows[0]);
    $this->template->rows=$rows;
  }

  /**
   * Akce pro upload uživatelských dat - výběr odpovídajícího view...
   * @param string $file = ''
   * @param string $type = ''
   * @param string $name=''
   */
  public function actionUploadData($file='',$type='',$name='') {
    if ($type==FileImportsFacade::FILE_TYPE_CSV){
      $this->redirect('importCsv',['file'=>$file,'name'=>$name]);
    }elseif($type==FileImportsFacade::FILE_TYPE_ZIP){
      $this->redirect('importZip',['file'=>$file]);
    }
  }

  /**
   * Akce pro zobrazení formuláře pro upload souborů
   */
  public function renderUploadData() {
    $maxFileSize=$this->fileImportsFacade->getMaximumFileUploadSize();
    if ($maxFileSize>0){
      $this->template->maxUploadSize=$maxFileSize;
    }
    #region kontrola, jestli nedošlo k selhání uploadu
    if($this->request->isMethod('post')){
      /** @var Form $form */
      $form=$this->getComponent('uploadForm');
      /** @var UploadControl $file */
      $file=$form->getComponent('file');
      if (!$file->isFilled()){
        $file->addError('File upload failed!');
      }
    }
    #endregion kontrola, jestli nedošlo k selhání uploadu
  }

  /**
   * Akce pro import CSV
   * @param string $file
   * @param string $name
   * @throws BadRequestException
   * @throws \Exception
   */
  public function actionImportCsv($file,$name) {
    $type=FileImportsFacade::FILE_TYPE_CSV;
    //kontrola, jestli zadaný soubor existuje
    if (!$this->fileImportsFacade->checkFileExists($file)){
      throw new BadRequestException('Requested file was not found.');
    }
    /** @var Form $form */
    $form=$this->getComponent('importCsvForm');
    $defaultsArr=['file'=>$file,'type'=>$type,'table'=>$this->databasesFacade->prepareNewTableName($name,false)];
    //detekce pravděpodobného oddělovače
    $separator=$this->fileImportsFacade->getCSVDelimiter($file);
    $defaultsArr['separator']=$separator;
    //připojení k DB pro zjištění názvu tabulky, který zatím není obsazen (dle typu preferované databáze)
    $csvColumnsCount=$this->fileImportsFacade->getColsCountInCSV($file,$separator);
    $databaseType=$this->databasesFacade->prefferedDatabaseType($csvColumnsCount);
    $newDatasource=$this->datasourcesFacade->prepareNewDatasourceForUser($this->usersFacade->findUser($this->user->id),$databaseType);
    $this->databasesFacade->openDatabase($newDatasource->getDbConnection());
    $defaultsArr['table']=$this->databasesFacade->prepareNewTableName($name);
    $form->setDefaults($defaultsArr);
  }

  /**
   * Akce pro import ZIP archívu
   * @param $file
   * @throws BadRequestException
   */
  public function actionImportZip($file) {
    $type=FileImportsFacade::FILE_TYPE_ZIP;
    //kontrola, jestli zadaný soubor existuje
    if (!$this->fileImportsFacade->checkFileExists($file)){
      throw new BadRequestException('Requested file was not found.');
    }
    $zipFilesList=$this->fileImportsFacade->getZipArchiveProcessableFilesList($file);
    if (empty($zipFilesList)){
      $this->flashMessage($this->translate('No acceptable files found in ZIP archive.'),'error');
      $this->redirect('Data:uploadData');
      return;
    }
    /** @var Form $form */
    $form=$this->getComponent('importZipForm');
    /** @var SelectBox $unzipFileSelect */
    $unzipFileSelect=$form->getComponent('unzipFile');
    $unzipFileSelect->setItems($zipFilesList);
    $form->setDefaults(array('file'=>$file,'type'=>$type));
  }
  /**
   * Akce pro smazání konkrétního mineru
   * @param int $id
   */
  public function renderDeleteMiner($id){
    $miner=$this->findMinerWithCheckAccess($id);
    //TODO
  }

  /**
   * Akce pro vykreslení histogramu z hodnot konkrétního atributu
   * @param int $miner = null
   * @param string $attribute
   * @param string $mode = 'default'|'component'|'iframe'
   * @throws BadRequestException
   * @throws \Nette\Application\ApplicationException
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function renderAttributeHistogram($miner,$attribute, $mode='default'){
    $miner=$this->findMinerWithCheckAccess($miner);
    try{
      $metasource=$miner->metasource;
      $this->databasesFacade->openDatabase($metasource->getDbConnection());
      $this->template->attributeValuesStatistic=$this->databasesFacade->getColumnValuesStatistic($metasource->attributesTable,$attribute);
    }catch (\Exception $e){
      throw new BadRequestException('Requested attribute not found!',500,$e);
    }
    if ($this->isAjax() || $mode=='component' || $mode=='iframe'){
      $this->layout='iframe';
      $this->template->layout=$mode;
    }
  }

  /**
   * Akce pro vykreslení histogramu z hodnot konkrétního sloupce v DB
   * @param int $datasource = null
   * @param int $miner = null
   * @param string $columnName
   * @param string $mode = 'default'|'component'|'iframe'
   * @throws BadRequestException
   * @throws \Nette\Application\ApplicationException
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function renderColumnHistogram($datasource=null, $miner=null ,$columnName, $mode='default'){
    if ($miner){
      $miner=$this->findMinerWithCheckAccess($miner);
      $datasource=$miner->datasource;
    }elseif($datasource){
      $datasource=$this->datasourcesFacade->findDatasource($datasource);
      $this->checkDatasourceAccess($datasource);
    }
    if (!$datasource){
      throw new BadRequestException('Requested data not specified!');
    }

    $this->databasesFacade->openDatabase($datasource->getDbConnection());
    $this->template->dbColumnValuesStatistic=$this->databasesFacade->getColumnValuesStatistic($datasource->dbTable,$columnName);

    if ($this->isAjax() || $mode=='component' || $mode=='iframe'){
      $this->layout='iframe';
      $this->template->layout=$this->layout;
    }
  }

  /**
   * Formulář pro upload souboru
   * @return Form
   */
  public function createComponentUploadForm(){
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addUpload('file','Upload file:')
      ->setRequired('Je nutné vybrat soubor pro import!')
      ->addRule(Form::MAX_FILE_SIZE,'Nahrávaný soubor je příliš velký',$this->fileImportsFacade->getMaximumFileUploadSize());
    $form->addSubmit('submit','Upload file...')
      ->onClick[]=function(SubmitButton $submitButton){
        /** @var Form $form */
        $form=$submitButton->getForm(true);
        /** @var FileUpload $file */
        $file=$form->getValues()->file;
        #region detekce typu souboru
        $fileType=$this->fileImportsFacade->detectFileType($file->getName());
        if ($fileType==FileImportsFacade::FILE_TYPE_UNKNOWN){
          //jedná se o nepodporovaný typ souboru
          try{
            FileSystem::delete($this->fileImportsFacade->getTempFilename());
          }catch (\Exception $e){}
          $form->addError($this->translate('Incorrect file type!'));
          return;
        }
        #endregion detekce typu souboru
        #region přesun souboru
        $filename=$this->fileImportsFacade->getTempFilename();
        $file->move($this->fileImportsFacade->getFilePath($filename));
        #endregion přesun souboru
        #region pokus o automatickou extrakci souboru
        if ($fileType==FileImportsFacade::FILE_TYPE_ZIP){
          $fileType=$this->fileImportsFacade->tryAutoUnzipFile($filename);
        }
        #endregion pokus o automatickou extrakci souboru
        #region výběr akce dle typu souboru
        $this->redirect('Data:uploadData',array('file'=>$filename,'type'=>$fileType,'name'=>FileImportsFacade::sanitizeFileNameForImport($file->getName())));
        #endregion výběr akce dle typu souboru
      };
    $form->addSubmit('storno','storno')
      ->setValidationScope([])
      ->onClick[]=function(){
        $this->redirect('Data:newMiner');
      };
    return $form;
  }

  /**
   * Handler po nahrání souboru => uloží soubor do temp složky a přesměruje uživatele na formulář pro konfiguraci importu
   * @param SubmitButton $submitButton
   */
  public function uploadFormSubmitted(SubmitButton $submitButton){

  }
  #endregion componentUploadForm

  /**
   * @return Form
   * @throws \Exception
   */
  public function createComponentNewMinerForm() {
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addHidden('datasource');
    $minersFacade=$this->minersFacade;
    $currentUserId = $this->user->id;
    $form->addText('datasourceName','Datasource:')
      ->setAttribute('class','noBorder normalWidth')
      ->setAttribute('readonly');
    $form->addText('name', 'Miner name:')
      ->setRequired('Input the miner name!')
      ->setAttribute('autofocus')
      ->setAttribute('class','normalWidth')
      ->addRule(Form::MAX_LENGTH,'Max length of miner name is %s characters!',100)
      ->addRule(Form::MIN_LENGTH,'Min length of miner name is %s characters!',3)
      ->addRule(function(TextInput $control)use($currentUserId,$minersFacade){
        try{
          $miner=$minersFacade->findMinerByName($currentUserId,$control->value);
          if ($miner instanceof Miner){
            return false;
          }
        }catch (\Exception $e){/*chybu ignorujeme (nenalezený miner je OK)*/}
        return true;
      },'Miner with this name already exists!');

    $availableMinerTypes=$this->minersFacade->getAvailableMinerTypes();
    if (empty($availableMinerTypes)){
      throw new \Exception('No miner type found! Please check the configuration...');
    }
    $form->addSelect('type','Miner type:',$availableMinerTypes)
      ->setAttribute('class','normalWidth')
      ->setDefaultValue(Miner::DEFAULT_TYPE);

    $form->addSubmit('submit','Create miner...')
      ->onClick[]=function(SubmitButton $submitButton){
        /** @var Form $form */
        $form=$submitButton->form;
        $values=$form->getValues();
        $miner=new Miner();
        $miner->user=$this->usersFacade->findUser($this->user->id);
        $miner->name=$values['name'];
        $miner->datasource=$this->datasourcesFacade->findDatasource($values['datasource']);
        $miner->created=new DateTime();
        $miner->type=$values['type'];
        $this->minersFacade->saveMiner($miner);
        $this->redirect('Data:openMiner',array('id'=>$miner->minerId));
      };
    $form->addSubmit('storno','storno')
      ->setValidationScope(array())
      ->onClick[]=function(SubmitButton $button){
        /** @var Presenter $presenter */
        $presenter=$button->form->getParent();
        $presenter->redirect('Data:newMiner');
      };
    return $form;
  }

  /**
   * Formulář pro import CSV souboru
   * @return Form
   */
  public function createComponentImportCsvForm(){
    $form = new Form();
    $form->setTranslator($this->translator);
    $tableName=$form->addText('table','Table name:')
      ->setAttribute('class','normalWidth')
      ->setRequired('Input table name!');
    $presenter=$this;
    $tableName->addRule(Form::MAX_LENGTH,'Max length of the table name is %s characters!',30)
      ->addRule(Form::MIN_LENGTH,'Min length of the table name is %s characters!',3)
      ->addRule(Form::PATTERN,'Table name can contain only letters, numbers and underscore and start with a letter!','[a-zA-Z0-9_]+')
      ->addRule(function(TextInput $control)use($presenter){
        $formValues=$control->form->getValues(true);
        $csvColumnsCount=$presenter->fileImportsFacade->getColsCountInCSV($formValues['file'],$formValues['separator'],$formValues['enclosure'],$formValues['escape']);
        $databaseType=$presenter->databasesFacade->prefferedDatabaseType($csvColumnsCount);
        $newDatasource=$presenter->datasourcesFacade->prepareNewDatasourceForUser($this->usersFacade->findUser($presenter->user->id),$databaseType);
        $presenter->databasesFacade->openDatabase($newDatasource->getDbConnection());
        return (!$presenter->databasesFacade->checkTableExists($control->value));
      },'Table with this name already exists!');

    $form->addSelect('separator','Separator:',array(
      ','=>'Comma (,)',
      ';'=>'Semicolon (;)',
      '|'=>'Vertical line (|)',
      '\t'=>'Tab (\t)'
    ))->setRequired()
      ->setAttribute('class','normalWidth');

    $form->addSelect('encoding','Encoding:',array(
      'utf8'=>'UTF-8',
      'cp1250'=>'WIN 1250',
      'iso-8859-1'=>'ISO 8859-1',
    ))->setRequired()
      ->setAttribute('class','normalWidth');
    $file=$form->addHidden('file');
    $file->setAttribute('id','frm-importCsvForm-file');
    $form->addHidden('type');
    $form->addText('enclosure','Enclosure:',1,1)->setDefaultValue('"');
    $form->addText('escape','Escape:',1,1)->setDefaultValue('\\');
    $nullValuesArr=CsvImport::getDefaultNullValuesArr();
    $defaultNullValue='none';
    foreach($nullValuesArr as $value=>$text){
      $defaultNullValue=$value;
      break;
    }
    $form->addSelect('nullValue','Null values:',array_merge(['none'=>'--none--'],$nullValuesArr))
      ->setAttribute('title','This value will be imported as missing (null).')
      ->setDefaultValue($defaultNullValue)
      ->setAttribute('class','normalWidth');
    $form->addSubmit('submit','Import data into database...')
      ->onClick[]=function (SubmitButton $submitButton){
        /** @var Form $form */
        $form=$submitButton->form;
        $values=$form->getValues();
        $nullValue=($values->nullValue=='none'?null:$values->nullValue);
        $user=$this->usersFacade->findUser($this->user->id);

        $colsCount=$this->fileImportsFacade->getColsCountInCSV($values->file,$values->separator,$values->enclosure,$values->escape);
        $dbType=$this->databasesFacade->prefferedDatabaseType($colsCount);
        //připravení připojení k DB
        $datasource=$this->datasourcesFacade->prepareNewDatasourceForUser($user,$dbType);
        $this->fileImportsFacade->importCsvFile($values->file,$datasource->getDbConnection(),$values->table,$values->encoding,$values->separator,$values->enclosure,$values->escape,$nullValue);
        $datasource->name=$values->table;
        //uložíme datasource
        $this->datasourcesFacade->saveDatasource($datasource);
        //smažeme dočasné soubory...
        $this->fileImportsFacade->deleteFile($values->file);

        $this->redirect('Data:newMinerFromDatasource',array('datasource'=>$datasource->datasourceId));
      };
    $form->addSubmit('storno','storno')
      ->setValidationScope([])
      ->onClick[]=function(SubmitButton $button)use($file){
        /** @var DataPresenter $presenter */
        $presenter=$button->form->getParent();
        $this->fileImportsFacade->deleteFile($file->value);
        $presenter->redirect('Data:newMiner');
      };
    return $form;
  }

  /**
   * @return Form
   */
  public function createComponentImportZipForm(){
    $form = new Form();
    $form->setTranslator($this->translator);
    $file=$form->addHidden('file');
    $form->addHidden('type');
    $form->addSelect('unzipFile','Extract file:')
      ->setPrompt('--select file--')
      ->setRequired('You have to select one file for extraction!');
    $form->addSubmit('submit','Extract selected file...')->onClick[]=function(SubmitButton $button){
      $values=$button->getForm(true)->getValues();
      $zipFilesList=$this->fileImportsFacade->getZipArchiveProcessableFilesList($values->file);
      if (empty($zipFilesList)){
        $this->flashMessage($this->translate('No acceptable files found in ZIP archive.'),'error');
        $this->redirect('Data:uploadData');
        return;
      }
      $compressedFileName=@$zipFilesList[$values->unzipFile];
      $this->fileImportsFacade->uncompressFileFromZipArchive($values->file,$values->unzipFile);
      $this->redirect('Data:uploadData',['file'=>$values->file,'type'=>$this->fileImportsFacade->detectFileType($compressedFileName),'name'=>FileImportsFacade::sanitizeFileNameForImport($compressedFileName)]);
    };
    $form->addSubmit('storno','storno')
      ->setValidationScope(array())
      ->onClick[]=function(SubmitButton $button)use($file){
        /** @var DataPresenter $presenter */
        $presenter=$button->form->getParent();
        $this->fileImportsFacade->deleteFile($file->value);
        $presenter->redirect('Data:uploadData');
      };
    return $form;
  }

  #region confirmationDialog
  /**
   * Funkce pro vytvoření komponenty potvrzovacího dialogu
   * @return \ConfirmationDialog
   */
  protected function createComponentConfirmationDialog(){
    $form=new \ConfirmationDialog($this->getSession('confirmationDialog'));
    $translator=$this->translator;
    $form->addConfirmer('deleteDatasourceColumn',array($this,'deleteDatasourceColumn'),
      function(/*$dialog,$params*/)use($translator){
        $html=Html::el('div');
        $html->setHtml('<h1>'.$translator->translate('Delete data field').'</h1><p>'.$translator->translate('Do your really want to delete this data field?').'</p>');
        return $html;
      }
    );
    return $form;
  }

  /**
   * Funkce pro smazání datového sloupce z datasource
   * @param int $datasource
   * @param int $column
   */
  function deleteDatasourceColumn($datasource,$column){
    if ($this->datasourcesFacade->deleteDatasourceColumn($datasource,$column)){
      $this->flashMessage($this->translate('Data field successfully removed.'));
    }else{
      $this->flashMessage($this->translate('Error occured while removing of data field.'));
    }
    $this->redirect('mapping',array('datasource'=>$datasource));
  }
  #endregion confirmationDialog

  #region renameDatasourceColumnDialog
  protected function createComponentDatasourceColumnRenameDialog(){
    $presenter=$this;
    $form = new Form();
    $form->setAction($this->link('getDatasourceColumnRenameDialog!'));
    $form->translator=$this->translator;
    $form->addHidden('datasource');
    $form->addHidden('column');
    $nameInput=$form->addText('name','Data field name:')->setAttribute('class','normalWidth');
    $nameInput->addRule(Form::MAX_LENGTH,'Max length of the data field name is %s characters!',15)
      ->addRule(Form::MIN_LENGTH,'You have to input data field name!',1)
      ->addRule(Form::PATTERN,'Data field name can contain only letters, numbers and underscore and start with a letter!','[a-zA-Z_][a-zA-Z0-9_]+')
      ->addRule(function(TextInput $control)use($presenter){
        //kontrola, jestli existuje data field se stejným jménem
        /** @var HiddenField $datasourceInput */
        $datasourceInput=$control->form->getComponent('datasource');
        $datasource=$presenter->datasourcesFacade->findDatasource($datasourceInput->value);
        $datasourceColumns=$datasource->datasourceColumns;
        foreach ($datasourceColumns as $datasourceColumn){
          if ($datasourceColumn->name==$control->value){
            /** @var HiddenField $columnInput */
            $columnInput=$control->form->getComponent('column');
            if ($columnInput->value==$datasourceColumn->datasourceColumnId){
              return true;
            }else{
              return false;
            }
          }
        }
        return true;
      },'Data field with this name already exists!');
    $form->onError[]=function()use($presenter){
      //při chybě opět zobrazíme přejmenovávací formulář
      $presenter->template->showDatasourceColumnRenameDialog=true;
      if ($presenter->isAjax()) {
        $presenter->redrawControl('datasourceColumnRenameDialog');
      }
    };
    $form->addSubmit('rename','Rename data field')->onClick[]=function(SubmitButton $button)use($presenter){
      //přejmenování data fieldu
      $formValues=$button->form->values;
      $presenter->datasourcesFacade->renameDatasourceColumn($formValues['datasource'],$formValues['column'],$formValues['name']);
      $presenter->redirect('this');
    };
    $stornoButton=$form->addSubmit('storno','Storno');
    $stornoButton->validationScope=array();
    $stornoButton->onClick[]=function()use($presenter){
      if ($presenter->isAjax()){
        $presenter->redrawControl('datasourceColumnRenameDialog');
      }else{
        $presenter->redirect('this');
      }
    };
    return $form;
  }

  /**
   * Signál vracející přejmenovávací dialog
   * @param int $datasource
   * @param int $column
   */
  public function handleGetDatasourceColumnRenameDialog($datasource,$column){
    $this->template->showDatasourceColumnRenameDialog=true;
    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
    /** @var Form $form */
    $form=$this->getComponent('datasourceColumnRenameDialog');
    $form->setDefaults(array(
      'name'=>$datasourceColumn->name,
      'datasource'=>$datasource,
      'column'=>$column
    ));
    if ($this->presenter->isAjax()) {
      $this->redrawControl('datasourceColumnRenameDialog');
    }
  }

  #endregion renameDatasourceColumnDialog


  protected function checkDatasourceAccess($datasource){
    return true;
    //TODO kontrola, jesli má aktuální uživatel právo přistupovat k datovému zdroji
  }

  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade){
    $this->datasourcesFacade=$datasourcesFacade;
  }

  /**
   * @param FileImportsFacade $fileImportsFacade
   */
  public function injectFileImportsFacade(FileImportsFacade $fileImportsFacade){
    $this->fileImportsFacade=$fileImportsFacade;
  }

  /**
   * @param DatabasesFacade $databasesFacade
   */
  public function injectDatabasesFacade(DatabasesFacade $databasesFacade){
    $this->databasesFacade=$databasesFacade;
  }

  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }

  /**
   * @param IMetaAttributesSelectControlFactory $iMetaAttributesSelectControlFactory
   */
  public function injectIMetaAttributesSelectControlFactory(IMetaAttributesSelectControlFactory $iMetaAttributesSelectControlFactory){
    $this->iMetaAttributesSelectControlFactory=$iMetaAttributesSelectControlFactory;
  }

  /**
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function injectMetaAttributesFacade(MetaAttributesFacade $metaAttributesFacade){
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
  #endregion
}
