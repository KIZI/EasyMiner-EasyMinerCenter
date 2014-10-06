<?php

namespace App\EasyMinerModule\Presenters;
use App\Libs\StringsHelper;
use App\Model\Data\Facades\FileImportsFacade;
use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\EasyMiner\Facades\MinersFacade;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;
use Tracy\Debugger;

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

  public function renderMapping(){
    //TODO akce a rozhraní pro mapování na KnowledgeBase!!!
    echo 'MAPPING!!!';
    $this->terminate();
  }

  public function renderImportCsvDataPreview($file,$separator=',',$encoding='utf8',$enclosure='"',$escape='\\'){
    $this->layout='blank';
    $this->fileImportsFacade->changeFileEncoding($file,$encoding);
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

  public function renderNewMinerFromDatasource($datasource){
    //TODO
  }

  /**
   * Akce pro založení nového EasyMineru či otevření stávajícího
   */
  public function renderNewMiner(){
    if ($this->user->id){
      $this->template->miners=$this->minersFacade->findMinersByUser($this->user->id);
      $this->template->datasources=$this->datasourcesFacade->findDatasourcesByUser($this->user->id);
    }else{
      $this->redirect('User:login');
    }
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
      $function='createComponentImport'.$type.'Form';
      /** @var Form $form */
      $form=$this->$function();
      $defaultsArr=array('file'=>$file,'type'=>$type,'table'=>$name);
      //TODO analýza nahraného souboru...
      $form->setDefaults($defaultsArr);//TODO příprava odpovídajícího názvu tabulky
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

  public function renderAttributeHistogram($miner,$attribute){
    //TODO vykreslení histogramu pro konkrétní atribut
  }

  public function renderColumnHistogram($miner,$column){
    //TODO vykreslení histogramu pro konkrétní datový sloupec
  }

  #region componentUploadForm
  public function createComponentUploadForm(){
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addHidden('type','Csv');
    $form->addUpload('file','CSV file:')
      ->setRequired('Je nutné nahrát soubor pro import!')
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

  #region componentImportCsvForm
  public function createComponentImportCsvForm(){
    $form = new Form();
    $form->setTranslator($this->translator);
    $tableName=$form->addText('table','Table name:')
      ->setRequired('Input table name!');//TODO
    $presenter=$this;
    $currentUserId=$this->user->id;
    $tableName->addRule(Form::MAX_LENGTH,'Table name is too long!',30)
      ->addRule(Form::PATTERN,'Invalid table name!','\w+')
      ->setAttribute('class','normalWidth')
      ->addRule(function($control)use($presenter,$currentUserId){
        return true;
        //TODO return (!$datasourcesFacade->checkTableNameExists($currentUserId,$type->value,$control->value));
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
    $form->addHidden('file');
    $form->addHidden('type');
    $form->addText('enclosure','Enclosure:',1,1)->setDefaultValue('"');
    $form->addText('escape','Escape:',1,1)->setDefaultValue('\\');
    $form->addSubmit('submit','Import data into database...')->onClick[]=array($this,'importCsvFormSubmitted');
    $presenter=$this;
    $storno=$form->addSubmit('storno','storno');
    $storno->setValidationScope(array());
    $storno->onClick[]=function()use($presenter){
      $presenter->redirect('Data:newMiner');
    };
    return $form;
  }

  public function importCsvFormSubmitted(SubmitButton $submitButton){
    /** @var Form $form */
    $form=$submitButton->form;
    $values=$form->getValues();
    var_dump($values);//TODO import!!!
    $this->terminate();
    $this->redirect('Data:mapping');
  }
  #endregion importCsvForm

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
  #endregion


  public function startup(){
    parent::startup();//TODO lepší kontrola přihlášení uživatele
    if (!$this->user->isLoggedIn()){
      $this->flashMessage('For using of EasyMiner, you have to log in...','error');
      $this->redirect('User:login');
    }
  }
}
