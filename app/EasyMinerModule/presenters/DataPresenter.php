<?php

namespace App\EasyMinerModule\Presenters;
use App\Model\EasyMiner\Repositories\DatasourcesRepository;
use App\Model\EasyMiner\Repositories\MinersRepository;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;

/**
 * Class DataPresenter - presenter pro práci s daty (import, zobrazování, smazání...)
 * @package App\EasyMinerModule\Presenters
 */
class DataPresenter extends BasePresenter{

  const TEMP_UPLOAD_DIR='../temp/dataImport';

  /** @var DatasourcesRepository $datasourcesRepository */
  private $datasourcesRepository;
  /** @var  MinersRepository $minersRepository */
  private $minersRepository;

  public function renderMapping(){
    //TODO akce a rozhraní pro mapování na KnowledgeBase!!!
    echo 'MAPPING!!!';
    $this->terminate();
  }

  /**
   * Akce pro otevření existujícího mineru
   * @param int $id
   * @throws BadRequestException
   */
  public function renderOpenMiner($id){//TODO dodělat javascriptové přesměrování v šabloně
    $miner=$this->minersRepository->findMiner($id);
    if (!$miner){
      throw new BadRequestException($this->translate('Requested miner not found!'),404);
    }
    $this->checkMinerAccess($miner);
    $this->template->miner=$miner;
  }

  public function renderNewMinerFromDatasource($datasource){
    //TODO
  }

  /**
   * Akce pro založení nového EasyMineru či otevření stávajícího
   */
  public function renderNewMiner(){
    if ($this->user->id){
      $this->template->miners=$this->minersRepository->findMinersByUser($this->user->id);
      $this->template->datasources=$this->datasourcesRepository->findDatasourcesByUser($this->user->id);
    }else{
      //pro anonymní uživatele nebudeme načítat existující minery
      $this->template->miners=null;
      $this->template->datasources=null;
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
      $form->setDefaults(array('filename'=>$file,'minerName'=>$name.' '.(new DateTime()),'table'=>$name));
      $this->template->importForm=$form;
    }
  }

  /**
   * Akce pro smazání konkrétního mineru
   * @param int $id
   */
  public function renderDeleteMiner($id){
    $miner=$this->minersRepository->findMiner($id);
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
    $form->addSubmit('submit','Nahrát soubor...')->onClick[]=array($this,'uploadFormSubmitted');
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
    $filename=$this->getTempFilename();
    $file->move($filename);
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
    $minerName=$form->addText('minerName','Miner name:',null,100)
      ->setRequired('Input miner name!');
    if ($this->user->loggedIn){
      $minersRepository=$this->minersRepository;
      $currentUserId=$this->user->id;
      $minerName->addRule(function($control)use($minersRepository,$currentUserId){
        return ($minersRepository->findMinerByName($currentUserId,$control->value)!=null);
      },'Miner with this name already exists!');
    }
    $type=$form->addSelect('type','Database:',$this->datasourcesRepository->getTypes())->setRequired();

    if ($this->user->loggedIn){
      $tableName=$form->addText('table','Table name:')
        ->setRequired('Input table name!');
      $datasourcesRepository=$this->datasourcesRepository;
      $currentUserId=$this->user->id;
      $tableName->addRule(Form::MAX_LENGTH,'Table name is too long!',30)
        ->addRule(Form::PATTERN,'Invalid table name!','\w+')
        ->addRule(function($control)use($datasourcesRepository,$type,$currentUserId){
          return (!$datasourcesRepository->checkTableNameExists($currentUserId,$type->value,$control->value));
        },'Table with this name already exists!');
    }else{
      $form->addHidden('table','');
    }

    $form->addSelect('encoding','Encoding:',array(
      'utf8'=>'UTF-8',
      'cp1250'=>'WIN 1250',
      'iso-8859-1'=>'ISO 8859-1',
    ))->setRequired();
    $form->addText('enclosure','Enclosure:',1,1)->setDefaultValue('"');
    $form->addText('escape','Escape character:',1,1)->setDefaultValue('\\');
    $form->addSubmit('submit','Import data info database...')->onClick[]=array($this,'importCsvFormSubmitted');
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

  /**
   * @return string
   */
  private function getTempFilename(){
    $filename=time();
    while(file_exists(self::TEMP_UPLOAD_DIR.'/'.$filename)){
      $filename.='x';
    }
    return $filename;
  }

  #region injections
  /**
   * @param DatasourcesRepository $datasourcesRepository
   */
  public function injectDatasourceRepository(DatasourcesRepository $datasourcesRepository){
    $this->datasourcesRepository=$datasourcesRepository;
  }

  /**
   * @param MinersRepository $minersRepository
   */
  public function injectMinersRepository(MinersRepository $minersRepository){
    $this->minersRepository=$minersRepository;
  }
  #endregion
} 