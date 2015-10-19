<?php
/*XXX pracovní nová verze presenteru!*/
namespace EasyMinerCenter\EasyMinerModule\Presenters;


use EasyMinerCenter\Model\Data\Entities\DbColumn;
use EasyMinerCenter\Model\Data\Facades\FileImportsFacade;
use EasyMinerCenter\Model\Data\Files\CsvImport;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

class NewDataPresenter extends \EasyMinerCenter\Presenters\BaseRestPresenter{
  /** @var  FileImportsFacade $fileImportsFacade */
  private $fileImportsFacade;

  public function renderUpload() {
    //akce pro upload souboru...
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
    $filename='/*FIXME*/'/*FIXME*/;
    if ($delimiter==''){
      //detekce výchozího separátoru
      $delimiter=$this->fileImportsFacade->getCSVDelimiter($filename);
    }
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
    $columns=$this->fileImportsFacade->getColsInCSV($filename,$delimiter,$enclosure,$escapeCharacter,$rowsCount+1);
    if (empty($columns)){
      throw new BadRequestException('No columns detected!');
    }
    foreach($columns as $column) {
      $resultArr['columnNames'][]=$column->name;
      $resultArr['dataTypes'][]=$column->dataType;
    }
    //načtení potřebných řádků...
    $resultArr['data']=$this->fileImportsFacade->getRowsFromCSV($filename,$rowsCount,$delimiter,$enclosure,$escapeCharacter,$nullValue,1);
    //odeslání kompletní odpovědi...
    $this->sendJsonResponse($resultArr);
  }


  public function createComponentUploadForm() {
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addUpload('file','Upload file:')
      ->setRequired('Je nutné vybrat soubor pro import!')
//      ->addRule(Form::MAX_FILE_SIZE,'Nahrávaný soubor je příliš velký',$this->fileImportsFacade->getMaximumFileUploadSize())
    ;
    //přidání submit tlačítek
    $form->addSubmit('submit','Configure upload...')
      ->onClick[]=function(){
      //nepodporujeme upload bez javascriptové konfigurace
      $this->flashMessage('For file upload using UI, you have to enable the javascript code!','error');
      $this->redirect('newMiner');
    };
    $form->addSubmit('storno','storno')
      ->setValidationScope([])
      ->onClick[]=function(){
      //bylo využito tlačítko storno
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

    $form->addText('enclosure','Enclosure:',1,1)->setDefaultValue('"');
    $form->addText('escape','Escape:',1,1)->setDefaultValue('\\');
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
    $form->addSubmit('storno','storno')
      ->setValidationScope([])
      ->onClick[]=function(){
      //bylo využito tlačítko storno
      $this->redirect('newMiner');
    };
    return $form;
  }


  /**
   * @param FileImportsFacade $fileImportsFacade
   */
  public function injectFileImportsFacade(FileImportsFacade $fileImportsFacade){
    $this->fileImportsFacade=$fileImportsFacade;
  }
}