<?php

namespace App\EasyMinerModule\Presenters;
use App\EasyMinerModule\Components\IMetaAttributesSelectControlFactory;
use App\EasyMinerModule\Components\MetaAttributesSelectControl;
use App\Libs\StringsHelper;
use App\Model\Data\Facades\DatabasesFacade;
use App\Model\Data\Facades\FileImportsFacade;
use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\EasyMiner\Facades\MinersFacade;
use App\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

/**
 * Class DataPresenter - presenter pro práci s daty (import, zobrazování, smazání...)
 * @package App\EasyMinerModule\Presenters
 */
class DataPresenter extends BasePresenter{

  /** @var DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  MinersFacade $minersFacade */
  private $minersFacade;
  /** @var  FileImportsFacade $fileImportsFacade */
  private $fileImportsFacade;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;
  /** @var  IMetaAttributesSelectControlFactory $iMetaAttributesSelectControlFactory */
  private $iMetaAttributesSelectControlFactory;

  /**
   * Akce pro úpravu datasource a nastavení mapování datových sloupců na knowledge base
   * @param int $datasource
   * @param int|null $column
   */
  public function renderMapping($datasource,$column=null){
    /** @var Datasource $datasource */
    $datasource=$this->datasourcesFacade->findDatasource($datasource);
    $this->checkDatasourceAccess($datasource);

    $datasourceColumns=$datasource->datasourceColumns;
    if (empty($datasourceColumns)){
      $this->datasourcesFacade->reloadDatasourceColumns($datasource);
      $datasourceColumns=$datasource->datasourceColumns;
    }

    if ($column){
      //zkusíme najít konkrétní datasourceColumn
      foreach ($datasourceColumns as $datasourceColumn){
        if ($datasourceColumn->datasourceColumnId==$column){
          $this->template->datasourceColumn=$datasourceColumn;
          break;
        }
      }
      if (empty($this->template->datasourceColumn)){
        $this->flashMessage($this->translate('Requested data field not found!'),'error');
      }else{
        $this->databasesFacade->openDatabase($datasource->getDbConnection());
        $this->template->datasourceColumnValuesStatistic=$this->databasesFacade->getColumnValuesStatistic($datasource->dbTable,$this->template->datasourceColumn->name);
      }
    }

    $this->template->datasource=$datasource;
    $this->template->datasourceColumns=$datasourceColumns;
    $this->template->mappingFinished=$this->datasourcesFacade->checkDatasourceColumnsFormatsMappings($datasource,true);
  }

  public function renderImportCsvDataPreview($file,$separator=',',$encoding='utf8',$enclosure='"',$escape='\\'){
    $this->layout='blank';
    $this->fileImportsFacade->changeFileEncoding($file,$encoding);
    $this->template->colsCount=$this->fileImportsFacade->getColsCountInCSV($file,$separator,$enclosure,$escape);
    $this->template->rows=$this->fileImportsFacade->getRowsFromCSV($file,20,$separator,$enclosure,$escape);
  }

  /**
   * Akce pro otevření existujícího mineru (adresa pro přesměrování je brána z konfigu
   * @param int $id
   * @throws BadRequestException
   */
  public function actionOpenMiner($id){
    try{
      $miner=$this->minersFacade->findMiner($id);
    }catch (\Exception $e){
      throw new BadRequestException($this->translate('Requested miner not found!'),404,$e);
    }

    $this->checkMinerAccess($miner);

    $url=$this->context->parameters['urls']['open_miner'];
    $this->redirectUrl(StringsHelper::replaceParams($url,array(':minerId'=>$id)));
  }

  /**
   * Akce pro vytvoření mineru nad konkrétním datovým zdrojem
   * @param int $datasource
   * @throws BadRequestException
   */
  public function renderNewMinerFromDatasource($datasource){
    try{
      $datasource=$this->datasourcesFacade->findDatasource($datasource);
    }catch (\Exception $e){
      throw new BadRequestException('Requested datasource was not found!',404,$e);
    }

    $this->checkDatasourceAccess($datasource);

    //kontrola, jestli je daný datový zdroj namapován na knowledge base
    if (!$this->datasourcesFacade->checkDatasourceColumnsFormatsMappings($datasource,true)){
      //přesměrování na mapování
      $this->flashMessage($this->translate('Select datasource has not been corrently mapped yet. You have to setup mappings...'));
      $this->redirect('Data:mapping',array('datasource'=>$datasource->datasourceId));
      return;
    }

    $this->template->datasource=$datasource;
    /** @var Form $form */
    $form=$this->getComponent('newMinerForm');
    $dateTime=new DateTime();
    $form->setDefaults(array('datasource'=>$datasource->datasourceId,'datasourceName'=>$datasource->type.': '.$datasource->dbTable,'name'=>$datasource->dbTable.' '.$dateTime->format('Y-m-d H:i:s')));
  }

  /**
   * Akce pro založení nového EasyMineru či otevření stávajícího
   */
  public function renderNewMiner(){
    $this->template->miners=$this->minersFacade->findMinersByUser($this->user->id);
    $this->template->datasources=$this->datasourcesFacade->findDatasourcesByUser($this->user->id);
  }

  /**
   * Akce pro import dat z nahraného souboru/externí DB
   * @param string $file = ''
   * @param string $type = ''
   * @param string $name=''
   */
  public function renderUploadData($file='',$type='',$name=''){
    if ($file && $type){
      //zobrazení nastavení importu (již máme nahraný soubor
      /** @var Form $form */
      $form=$this->getComponent('import'.$type.'Form');
      $defaultsArr=array('file'=>$file,'type'=>$type,'table'=>$this->databasesFacade->prepareNewTableName($name,false));

      if ($type=='Csv'){
        //detekce pravděpodobného oddělovače
        $separator=$this->fileImportsFacade->getCSVDelimitier($file);
        $defaultsArr['separator']=$separator;
        //připojení k DB pro zjištění názvu tabulky, který zatím není obsazen (dle typu preferované databáze)
        $csvColumnsCount=$this->fileImportsFacade->getColsCountInCSV($file,$separator);
        $databaseType=$this->databasesFacade->prefferedDatabaseType($csvColumnsCount);
        $newDatasource=$this->datasourcesFacade->prepareNewDatasourceForUser($this->usersFacade->findUser($this->user->id),$databaseType);
        $this->databasesFacade->openDatabase($newDatasource->getDbConnection());
        $defaultsArr['table']=$this->databasesFacade->prepareNewTableName($name);
      }else{
        //TODO další možnosti importu (ZIP soubor atd.)
      }

      if (!$form->isSubmitted()){
        $form->setDefaults($defaultsArr);
      }
      $this->template->importForm=$form;
    }
  }

  public function renderImportMysql(){
    throw new BadRequestException('Not implemented yet!');//TODO
  }

  /**
   * Akce pro smazání konkrétního mineru
   * @param int $id
   */
  public function renderDeleteMiner($id){
    $miner=$this->minersFacade->findMiner($id);
    $this->checkMinerAccess($miner);
    //TODO
  }

  /**
   * Akce pro vykreslení histogramu z hodnot konkrétního atributu
   * @param int $miner = null
   * @param string $attribute
   * @param string $layout = 'default'|'component'|'iframe'
   * @throws BadRequestException
   * @throws \Nette\Application\ApplicationException
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function renderAttributeHistogram($miner,$attribute, $layout='default'){
    if ($miner){
      try{
        $miner=$this->minersFacade->findMiner($miner);
      }catch (\Exception $e){
        throw new BadRequestException('Requested miner not specified!', 404, $e);
      }
      $this->checkMinerAccess($miner);
    }
    try{
      $datasource=$miner->getAttributesDatasource();
      $this->databasesFacade->openDatabase($datasource->getDbConnection());
      $this->template->attributeValuesStatistic=$this->databasesFacade->getColumnValuesStatistic($datasource->dbTable,$attribute);
    }catch (\Exception $e){
      throw new BadRequestException('Requested attribute not found!',500,$e);
    }
    if ($this->isAjax() || $layout=='component' || $layout=='iframe'){
      $this->layout='iframe';
      $this->template->layout=$layout;
    }
  }

  /**
   * Akce pro vykreslení histogramu z hodnot konkrétního sloupce v DB
   * @param int $datasource = null
   * @param int $miner = null
   * @param string $column
   * @param string $layout = 'default'|'component'|'iframe'
   * @throws BadRequestException
   * @throws \Nette\Application\ApplicationException
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function renderColumnHistogram($datasource=null, $miner=null ,$column, $layout='default'){
    if ($miner){
      $miner=$this->minersFacade->findMiner($miner);
      $this->checkMinerAccess($miner);
      $datasource=$miner->datasource;
    }elseif($datasource){
      $datasource=$this->datasourcesFacade->findDatasource($datasource);
      $this->checkDatasourceAccess($datasource);
    }
    if (!$datasource){
      throw new BadRequestException('Requested data not specified!');
    }

    $this->databasesFacade->openDatabase($datasource->getDbConnection());
    $this->template->dbColumnValuesStatistic=$this->databasesFacade->getColumnValuesStatistic($datasource->dbTable,$column);

    if ($this->isAjax() || $layout=='component' || $layout=='iframe'){
      $this->layout='iframe';
      $this->template->layout=$layout;
    }
  }


  #region componentUploadForm
  public function createComponentUploadForm(){
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addHidden('type','Csv');
    $file=$form->addUpload('file','CSV file:');
    $file->setRequired('Je nutné nahrát soubor pro import!')
      ->addRule(function($control){
        return ($control->value->ok);
      },'Chyba při uploadu souboru!');
    $form->addSubmit('submit','Upload file...')->onClick[]=array($this,'uploadFormSubmitted');
    $presenter=$this;
    $storno=$form->addSubmit('storno','storno');
    $storno->setValidationScope(array());
    $storno->onClick[]=function()use($presenter){
      $presenter->redirect('Data:newMiner');
    };
    return $form;
  }

  /**
   * Handler po nahrání souboru => uloží soubor do temp složky a přesměruje uživatele na formulář pro konfiguraci importu
   * @param SubmitButton $submitButton
   */
  public function uploadFormSubmitted(SubmitButton $submitButton){
    /** @var Form $form */
    $form=$submitButton->form;
    $values=$form->getValues();
    /** @var FileUpload $file */
    $file=$values['file'];
    $filename=$this->fileImportsFacade->getTempFilename();
    $file->move($this->fileImportsFacade->getFilePath($filename));
    $name=$file->getSanitizedName();
    $name=mb_substr($name,0,mb_strrpos($name,'.','utf8'));
    $name=str_replace('.','_',$name);
    $this->redirect('Data:uploadData',array('file'=>$filename,'type'=>$values['type'],'name'=>$name));
  }
  #endregion componentUploadForm

  #region componentNewMinerForm
  /**
   * @return Form
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
      ->addRule(Form::MAX_LENGTH,'Max length of miner name is %s characters!',30)
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

    $form->addSelect('type','Miner type:',Miner::getTypes())->setAttribute('class','normalWidth')->setDefaultValue(Miner::DEFAULT_TYPE);

    $form->addSubmit('submit','Create miner...')->onClick[]=array($this,'newMinerFormSubmitted');
    $stornoButton=$form->addSubmit('storno','storno');
    $stornoButton->setValidationScope(array());
    $stornoButton->onClick[]=function(SubmitButton $button){
      /** @var Presenter $presenter */
      $presenter=$button->form->getParent();
      $presenter->redirect('Data:newMiner');
    };
    return $form;
  }

  public function newMinerFormSubmitted(SubmitButton $submitButton){
    /** @var Form $form */
    $form=$submitButton->form;
    $values=$form->getValues();
    $miner=new Miner();
    $miner->user=$this->usersFacade->findUser($this->user->id);
    $miner->name=$values['name'];
    $miner->created=new DateTime();
    $miner->type=$values['type'];
    $this->minersFacade->saveMiner($miner);
    $this->redirect('Data:openMiner',array('id'=>$miner->minerId));
  }
  #endregion componentNewMinerForm

  #region componentImportCsvForm
  /**
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
    ))->setRequired()
      ->setAttribute('class','normalWidth');

    $form->addSelect('encoding','Encoding:',array(
      'utf8'=>'UTF-8',
      'cp1250'=>'WIN 1250',
      'iso-8859-1'=>'ISO 8859-1',
    ))->setRequired()
      ->setAttribute('class','normalWidth');
    $file=$form->addHidden('file');
    $form->addHidden('type');
    $form->addText('enclosure','Enclosure:',1,1)->setDefaultValue('"');
    $form->addText('escape','Escape:',1,1)->setDefaultValue('\\');
    $form->addSubmit('submit','Import data into database...')->onClick[]=array($this,'importCsvFormSubmitted');
    $storno=$form->addSubmit('storno','storno');
    $storno->setValidationScope(array());
    $storno->onClick[]=function(SubmitButton $button)use($file){
      /** @var DataPresenter $presenter */
      $presenter=$button->form->getParent();
      $this->fileImportsFacade->deleteFile($file->value);
      $presenter->redirect('Data:newMiner');
    };
    return $form;
  }

  public function importCsvFormSubmitted(SubmitButton $submitButton){
    /** @var Form $form */
    $form=$submitButton->form;
    $values=$form->getValues();
    $user=$this->usersFacade->findUser($this->user->id);
    #region params
    $table=$values['table'];
    $separator=$values["separator"];
    $encoding=$values["encoding"];
    $file=$values["file"];
    $enclosure=$values["enclosure"];
    $escape=$values["escape"];
    #endregion

    $colsCount=$this->fileImportsFacade->getColsCountInCSV($file,$separator,$enclosure,$escape);
    $dbType=$this->databasesFacade->prefferedDatabaseType($colsCount);
    //připravení připojení k DB
    $datasource=$this->datasourcesFacade->prepareNewDatasourceForUser($user,$dbType);
    $this->fileImportsFacade->importCsvFile($file,$datasource->getDbConnection(),$table,$encoding,$separator,$enclosure,$escape);
    $datasource->dbTable=$table;
    //uložíme datasource
    $this->datasourcesFacade->saveDatasource($datasource);
    //smažeme dočasné soubory...
    $this->fileImportsFacade->deleteFile($file);
    $this->redirect('Data:mapping',array('datasource'=>$datasource->datasourceId));
  }
  #endregion importCsvForm

  #region confirmationDialog
  /**
   * Funkce pro vytvoření komponenty potvrzovacího dialogu
   * @return \ConfirmationDialog
   */
  protected function createComponentConfirmationDialog(){
    $form=new \ConfirmationDialog($this->getSession('confirmationDialog'));
    $translator=$this->translator;
    $form->addConfirmer('deleteDatasourceColumn',array($this,'deleteDatasourceColumn'),
      function($dialog,$params)use($translator){
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

  #region selectMetaAttributeDialog
  /**
   * @return \App\EasyMinerModule\Components\MetaAttributesSelectControl
   */
  protected function createComponentSelectMetaAttributeDialog(){
    /** @var MetaAttributesSelectControl $metaAttributesSelectControl */
    $metaAttributesSelectControl=$this->iMetaAttributesSelectControlFactory->create();
    $presenter=$this;
    $metaAttributesSelectControl->onComponentShow[]=function()use(&$presenter){
      $this->template->showSelectMetaAttributeDialog=true;
    };
    $metaAttributesSelectControl->onComponentHide[]=function()use(&$presenter){
      $this->template->showSelectMetaAttributeDialog=false;
    };
    return $metaAttributesSelectControl;
  }

  /**
   * Signál vracející přejmenovávací dialog
   * @param int $datasource
   * @param int $column
   */
  public function handleGetSelectMetaAttributeDialog($datasource,$column){
    $this->template->showSelectMetaAttributeDialog=true;
    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
    /** @var MetaAttributesSelectControl $metaAttributesSelectControl */
    $metaAttributesSelectControl=$this->getComponent('selectMetaAttributeDialog');
    if ($this->presenter->isAjax()) {
      $this->redrawControl('selectMetaAttributeDialog');
    }
  }

  #endregion selectMetaAttributeDialog


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
      ->addRule(Form::PATTERN,'Data field name can contain only letters, numbers and underscore and start with a letter!','[a-zA-Z0-9_]+')
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

  #region injections
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade){
    $this->datasourcesFacade=$datasourcesFacade;
  }

  /**
   * @param MinersFacade $minersFacade
   */
  public function injectMinersFacade(MinersFacade $minersFacade){
    $this->minersFacade=$minersFacade;
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

  public function injectIMetaAttributesSelectControlFactory(IMetaAttributesSelectControlFactory $iMetaAttributesSelectControlFactory){
    $this->iMetaAttributesSelectControlFactory=$iMetaAttributesSelectControlFactory;
  }
  #endregion


  public function startup(){
    parent::startup();
    if (!$this->user->isLoggedIn()){
      $this->flashMessage('For using of EasyMiner, you have to log in...','error');
      $this->redirect('User:login');
    }
  }
}
