<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\Data\Entities\DbField;
use EasyMinerCenter\Model\Data\Facades\FileImportsFacade;
use EasyMinerCenter\Model\Data\Files\CsvImport;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;

/**
 * Class DataPresenter
 * @author Stanislav Vojíř
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 */
class DataPresenter extends BasePresenter{
  use ResponsesTrait;
  use MinersFacadeTrait;

  /** @var  FileImportsFacade $fileImportsFacade */
  private $fileImportsFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;
  /** @var  DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;


  #region vytvoření nového mineru

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
    $this->template->datasources=$this->datasourcesFacade->findDatasourcesByUser($currentUser);
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

    #region kontrola, jestli je daný datový zdroj namapován na knowledge base
    /*TODO refaktorovat... (zároveň je v MinersPresenteru v REST modulu)
    if (!$this->datasourcesFacade->checkDatasourceColumnsFormatsMappings($datasource,true)){
      foreach ($datasource->datasourceColumns as $datasourceColumn){
        if (empty($datasourceColumn->format)){
          //automatické vytvoření formátu
          $metaAttribute=$this->metaAttributesFacade->findOrCreateMetaAttributeWithName(Strings::lower($datasourceColumn->name));
          $existingFormats=$this->metaAttributesFacade->findFormatsForUser($metaAttribute,$this->user->getId());
          $existingFormatNames=[];
          if (!empty($existingFormats)){
            foreach ($existingFormats as $format){
              $existingFormatNames[]=$format->name;
            }
          }
          $basicFormatName=str_replace('-','_',Strings::webalize($datasource->dbTable));
          $i=1;
          do{
            $formatName=$basicFormatName.($i>1?'_'.$i:'');
            $i++;
          }while(in_array($formatName,$existingFormatNames));
          $datasourceColumnValuesStatistic=$this->databasesFacade->getColumnValuesStatistic($datasource->dbTable,$datasourceColumn->name);
          $formatType=($datasourceColumn->type==DatasourceColumn::TYPE_STRING?Format::DATATYPE_VALUES:Format::DATATYPE_INTERVAL);
          $format=$this->metaAttributesFacade->createFormatFromDatasourceColumn($metaAttribute,$formatName,$datasourceColumn,$datasourceColumnValuesStatistic,$formatType,false,$this->user->getId());
          $datasourceColumn->format=$format;
          $this->datasourcesFacade->saveDatasourceColumn($datasourceColumn);
        }
      }
    }
    */
    #endregion kontrola, jestli je daný datový zdroj namapován na knowledge base

    $this->template->datasource=$datasource;
    /** @var Form $form */
    $form=$this->getComponent('newMinerForm');
    $dateTime=new \DateTime();
    $form->setDefaults(array('datasource'=>$datasource->datasourceId,'datasourceName'=>$datasource->type.': '.$datasource->getName(),'name'=>$datasource->dbTable.' '.$dateTime->format('Y-m-d H:i:s')));
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
      $miner->created=new \DateTime();
      $miner->type=$values['type'];
      $this->minersFacade->saveMiner($miner);
      $this->redirect('Data:openMiner',array('id'=>$miner->minerId));
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
      $resultArr['dataTypes'][]=($column->dataType==DbField::TYPE_STRING?'nominal':'numerical');
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

  public function createComponentUploadForm() {
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addUpload('file','Upload file:')
      ->setRequired('Je nutné vybrat soubor pro import!')
    ;
    $databaseTypes=$this->databasesFacade->getDbTypes(true);
    if (count($databaseTypes)==1){
      reset($databaseTypes);
      $form->addHidden('dbType',key($databaseTypes));
    }else{
      $form->addSelect('dbType','Database type:',$databaseTypes)
        ->setDefaultValue($this->databasesFacade->getPreferredDatabaseType());
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

    //zaktualizujeme info o posledním otevření mineru
    $miner->lastOpened=new \DateTime();
    $this->minersFacade->saveMiner($miner);

    $this->redirect('MiningUi:default',['id_dm'=>$miner->minerId]);
  }

  #endregion otevření mineru

  /**
   * Funkce vracející entitu konkrétního uživatele
   * @return \EasyMinerCenter\Model\EasyMiner\Entities\User
   */
  protected function getCurrentUser() {
    return $this->usersFacade->findUser($this->user->id);
  }

  #region injections
  /**
   * @param FileImportsFacade $fileImportsFacade
   */
  public function injectFileImportsFacade(FileImportsFacade $fileImportsFacade){
    $this->fileImportsFacade=$fileImportsFacade;
  }
  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade) {
    $this->usersFacade=$usersFacade;
  }
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade) {
    $this->datasourcesFacade=$datasourcesFacade;
  }
  #endregion injections
}