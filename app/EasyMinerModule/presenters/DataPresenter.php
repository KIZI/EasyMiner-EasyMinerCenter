<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\Data\Entities\DbField;
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

/**
 * Class DataPresenter
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 */
class DataPresenter extends BasePresenter{
  use ResponsesTrait;
  use MinersFacadeTrait;
  use UsersTrait;

  /** @var  FileImportsFacade $fileImportsFacade */
  private $fileImportsFacade;
  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;


  #region vytvoření nového mineru

  /**
   * Akce pro inicializaci mineru (vytvoření metasource)
   * @param int $id
   * @throws BadRequestException
   */
  public function actionInitMiner($id) {
    //najdeme miner a zkontroluejem oprávnění
    $miner=$this->findMinerWithCheckAccess($id);
    //zkontrolujeme stav metasource
    if ($miner->metasource){
      //jde o miner s existujícím metasource => zkontrolujeme jeho stav
      switch ($miner->metasource->state){
        case Metasource::STATE_AVAILABLE:
          $this->redirect('openMiner',['id'=>$id]);
          return;
        case Metasource::STATE_PREPARATION:
          $metasourceTasks=$miner->metasource->metasourceTasks;
          if (!empty($metasourceTasks)){
            foreach($metasourceTasks as $metasourceTaskItem){
              if (!$metasourceTaskItem->attribute){
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
      //jde o miner bez metasource
      $metasourceTask=$this->metasourcesFacade->startMinerMetasourceInitialization($miner, $this->minersFacade);
    }
    $this->template->miner=$miner;
    $this->template->metasourceTask=$metasourceTask;
  }

  /**
   * Akce pro inicializaci mineru (vytvoření metasource) - může trvat delší dobu...
   * @param int $id - ID mineru
   * @param int $task - ID dlouhotrvající úlohy
   * @throws BadRequestException
   * @throws \Exception
   */
  public function actionInitMinerMetasource($id,$task) {
    $miner = $this->findMinerWithCheckAccess($id);
    $metasourceTask=$this->metasourcesFacade->findMetasourceTask($task);
    $metasourceTask=$this->metasourcesFacade->initializeMinerMetasource($miner, $metasourceTask);
    if ($metasourceTask->state==MetasourceTask::STATE_DONE){
      //úloha již doběhla - přesměrujeme uživatele na vytvořený miner
      $this->metasourcesFacade->deleteMetasourceTask($metasourceTask);
      $this->sendJsonResponse(['redirect'=>$this->link('openMiner',['id'=>$miner->minerId])]);
    }elseif($metasourceTask->state==MetasourceTask::STATE_IN_PROGRESS){
      //úloha je v průběhu
      $this->sendJsonResponse(['message'=>$metasourceTask->getPpTask()->statusMessage,'state'=>$metasourceTask->state]);
    }else{
      throw new BadRequestException();
    }
  }


  /**
   * Akce pro vytvoření nového mineru - výchozí pohled...
   */
  public function actionNewMiner() {
    $this->redirect('default');
  }

  /**
   * Akce pro založení nového EasyMineru či otevření stávajícího
   */
  public function renderDefault(){
    $currentUser=$this->getCurrentUser();
    $this->template->miners=$this->minersFacade->findMinersByUser($currentUser);
    $this->datasourcesFacade->updateRemoteDatasourcesByUser($currentUser);
    $this->template->datasources=$this->datasourcesFacade->findDatasourcesByUser($currentUser, true);
  }

  /**
   * Akce pro vytvoření mineru nad konkrétním datovým zdrojem
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
      //nebyl nalezen žádný odpovídající miner
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
   * Funkce pro vytvoření formuláře pro zadání nového mineru z existujícího datového zdroje
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
        }catch (\Exception $e){/*chybu ignorujeme (nenalezený miner je OK)*/}
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





  #endregion

  #region upload datasetu

  public function renderUpload() {
    //akce pro upload souboru...
    /** @noinspection PhpUndefinedFieldInspection */
    $this->template->apiKey=$this->user->getIdentity()->apiKey;
    $this->template->dataServiceUrl='http://br-dev.lmcloud.vse.cz/easyminer-data/api/v1/';//TOOD načtení URL z configu
  }

  /**
   * Akce pro zobrazení náhledu na prvních X řádků nahrávaného souboru - pracující s lokálními soubory...
   * @param string $file
   * @param string $delimiter
   * @param string $encoding
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string $nullValue
   * @param string $locale
   * @param int $rowsCount = 20
   * @throws BadRequestException
   */
  public function actionPreviewData($file,$delimiter='',$encoding='utf-8',$enclosure='"',$escapeCharacter='\\',$nullValue='',$locale='en',$rowsCount=20) {
    //TODO detekce locale... (podle desetinné tečky/čárky...)
    if ($delimiter==''){
      //detekce výchozího separátoru
      $delimiter=$this->fileImportsFacade->getCSVDelimiter($file);
    }
    //změna kódování souboru
    $this->fileImportsFacade->changeFileEncoding($file,$encoding);
    //připravení výstupního pole s daty
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
    //načtení informací o datových sloupcích
    $columns=$this->fileImportsFacade->getColsInCSV($file,$delimiter,$enclosure,$escapeCharacter,$rowsCount+1);
    if (empty($columns)){
      throw new BadRequestException('No columns detected!');
    }
    foreach($columns as $column) {
      $resultArr['columnNames'][]=$column->name;
      $resultArr['dataTypes'][]=($column->type==DbField::TYPE_NOMINAL?'nominal':'numeric');
    }
    //načtení potřebných řádků...
    $resultArr['data']=$this->fileImportsFacade->getRowsFromCSV($file,$rowsCount,$delimiter,$enclosure,$escapeCharacter,$nullValue,1);
    //odeslání kompletní odpovědi...
    $this->sendJsonResponse($resultArr);
  }

  public function actionUploadPreview() {
    $previewRawData=$this->getHttpRequest()->getRawBody();
    //TODO inicializace uploadu pomocí datové služby...

    $filename=$this->fileImportsFacade->saveTempFile($previewRawData);
    $this->sendJsonResponse(['file'=>$filename]);
  }

  /**
   * @return Form
   */
  public function createComponentUploadForm() {
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addUpload('file','Upload file:')
      ->setRequired('Je nutné vybrat soubor pro import!')
    ;
    $dbTypes=$this->datasourcesFacade->getDbTypes(true);//TODO
    if (count($dbTypes)==1){
      reset($dbTypes);
      $form->addHidden('dbType',key($dbTypes));
    }else{
      $form->addSelect('dbType','Database type:',$dbTypes)
        ->setDefaultValue($this->datasourcesFacade->getPreferredDbType());
    }
    //přidání submit tlačítek
    $form->addSubmit('submit','Configure upload...')
      ->onClick[]=function(){
      //nepodporujeme upload bez javascriptové konfigurace
      $this->flashMessage('For file upload using UI, you have to enable the javascript code!','error');
      $this->redirect('newMiner');
    };
    return $form;
  }

  public function createComponentUploadConfigForm() {
    $form = new Form();
    $form->setTranslator($this->translator);
//    $tableName=$form->addText('table','Table name:')
//      ->setRequired('Input table name!');
//    $tableName->addRule(Form::MAX_LENGTH,'Max length of the table name is %s characters!',30)
//      ->addRule(Form::MIN_LENGTH,'Min length of the table name is %s characters!',3)
//      ->addRule(Form::PATTERN,'Table name can contain only letters, numbers and underscore and start with a letter!','[a-zA-Z0-9_]+')
//      /*TODO kontrola existence DB tabulky
//      ->addRule(function(TextInput $control)use($presenter){
//        $formValues=$control->form->getValues(true);
//        $csvColumnsCount=$presenter->fileImportsFacade->getColsCountInCSV($formValues['file'],$formValues['separator'],$formValues['enclosure'],$formValues['escape']);
//        $databaseType=$presenter->databasesFacade->prefferedDatabaseType($csvColumnsCount);
//        $newDatasource=$presenter->datasourcesFacade->prepareNewDatasourceForUser($this->usersFacade->findUser($presenter->user->id),$databaseType);
//        $presenter->databasesFacade->openDatabase($newDatasource->getDbConnection());
//        return (!$presenter->databasesFacade->checkTableExists($control->value));
//      },'Table with this name already exists!')*/;

    $form->addSelect('separator','Separator:',[
      ','=>'Comma (,)',
      ';'=>'Semicolon (;)',
      '|'=>'Vertical line (|)',
      '\t'=>'Tab (\t)'
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

  #endregion


  #region otevření mineru

  /**
   * Akce pro otevření existujícího mineru
   * @param int $id
   * @throws BadRequestException
   */
  public function actionOpenMiner($id){
    $miner=$this->findMinerWithCheckAccess($id);

    if (!$miner->metasource){
      //přesměrování na skript přípravy metasource...
      $this->redirect('initMiner',['id'=>$id]);
      return;
    }else{
      //kontrola stavu metasource...
      $metasource=$miner->metasource;
      if (!empty($metasource->metasourceTasks)){
        $metasourceTasks=$metasource->metasourceTasks;
        foreach($metasourceTasks as $metasourceTask){
          if ($metasourceTask->attribute==null){
            //přesměrování na skript přípravy metasource...
            $this->redirect('initMiner',['id'=>$id]);
            return;
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
          throw new InvalidStateException('Requested miner is not available! (metasource error)',$e);
        }
      }
    }

    //zaktualizujeme info o posledním otevření mineru
    $miner->lastOpened=new \DateTime();
    $this->minersFacade->saveMiner($miner);

    $this->redirect('MiningUi:',['id'=>$miner->minerId]);
  }
  #endregion otevření mineru

  #region funkce pro přístup k datům
  /**
   * Funkce pro zobrazení histogramu pro datový sloupec
   * @param int $miner - Miner ID
   * @param int $column - DatasourceColum ID
   */
  public function renderColumnHistogram($miner, $column) {
    //TODO implementovat podporu vykreslení histogramu

  }
  
  
  #endregion funkce pro přístup k datům
  
  
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