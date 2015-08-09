<?php

namespace EasyMinerCenter\InstallModule\Presenters;

use EasyMinerCenter\InstallModule\Model\ConfigManager;
use EasyMinerCenter\InstallModule\Model\DatabaseManager;
use EasyMinerCenter\InstallModule\Model\FilesManager;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\SubmitButton;
use Nette\Neon\Neon;

/**
 * Class WizardPresenter
 *
 * @package EasyMinerCenter\InstallModule\Presenters
 */
class WizardPresenter extends Presenter {
  /** @var array $wizardSteps - pole s definicí jednotlivých kroků instalačního wizardu */
  private $wizardSteps=[
    'default',
    'files',    //kontrola přístupů k souborům a adresářům
    'timezone', //nastavení časové zóny
    'database', //inicializace aplikační databáze
    'dataMysql',//přístupy k databázi pro uživatelská data
    'logins',   //typy přihlašování
    'miners',   //zadání přístupů k dolovacím serverům
    'finish'    //zpráva o dokončení
  ];

  /**
   * Tento presenter nemá výchozí view => přesměrování na vhodný krok
   */
  public function actionDefault() {
    $this->redirect($this->getNextStep('default'));
  }

  /**
   * Akce pro kontrolu přístupů ke složkám a souborům
   */
  public function actionFiles() {
    $filesManager=new FilesManager(Neon::decode(file_get_contents(__DIR__.'/../data/files.neon')));
    $writableDirectories=$filesManager->checkWritableDirectories();
    $writableFiles=$filesManager->checkWritableFiles();
    $stateError=false;
    $statesArr=[];
    if(!empty($writableDirectories)) {
      foreach($writableDirectories as $directory=>$state) {
        $statesArr['Directory: '.$directory]=$state;
        if(!$state) {
          $stateError=true;
        }
      }
    }
    if(!empty($writableFiles)) {
      foreach($writableFiles as $file=>$state) {
        $statesArr['File: '.$file]=$state;
        if(!$state) {
          $stateError=true;
        }
      }
    }

    if($stateError) {
      //vyskytla se chyba
      $this->setView('statesTable');
      $this->template->title='Writable files and directories';
      $this->template->text='Following files and directories has to been created and writable. If you are not sure in user access credentials, please modify them to 777. The paths are written from the content root, where the EasyMinerCenter is located. In case of non-existing files, you can also make writable the appropriate directory and the file will be created automatically.';
      $this->template->states=$statesArr;
    } else {
      $this->redirect($this->getNextStep('files'));
    }
  }

  /**
   * Akce pro nastavení vyžadované časové zóny
   */
  public function actionTimezone() {
    $configManager=$this->createConfigManager();
    if(!empty($configManager->data['php']['date.timezone'])) {
      $currentTimezone=$configManager->data['php']['date.timezone'];
    } else {
      $currentTimezone=@date_default_timezone_get();
    }
    if(!empty($currentTimezone)) {
      /** @var Form $form */
      $form=$this->getComponent('timezoneForm');
      $form->setDefaults(['timezone'=>$currentTimezone]);
    }
  }

  /**
 * Akce pro zadání přístupu k hlavní aplikační databázi
 */
  public function actionDatabase() {
    $configManager=$this->createConfigManager();
    if(!empty($configManager->data['parameters']['mainDatabase'])) {
      //set currently defined database connection params
      try{
        /** @var Form $form */
        $form=$this->getComponent('databaseForm');
        $form->setDefaults($configManager->data['parameters']['mainDatabase']);
      }catch (\Exception $e){/*ignore error*/}
    }
  }

  /**
   * Akce pro zadání přístupu k MySQL databázi pro uživatelská data
   */
  public function actionDataMysql() {
    $configManager=$this->createConfigManager();
    if(!empty($configManager->data['parameters']['databases']['mysql'])) {
      //set currently defined database connection params
      try{
        /** @var Form $form */
        $form=$this->getComponent('dataMysqlForm');
        $form->setDefaults($configManager->data['parameters']['databases']['mysql']);
      }catch (\Exception $e){/*ignore error*/}
    }
  }

  /**
   * Akce pro zadání způsobů přihlašování
   */
  public function actionLogins() {
    //TODO
  }

  /**
   * Akce pro volbu podporovaných typů minerů
   */
  public function actionMiners() {
    //TODO
  }

  /**
   * Akce pro ukončení průvodce - smazání cache, přesměrování
   */
  public function actionFinish() {
    //TODO
  }


  /**
   * Formulář pro nastavení časové zóny
   * @return Form
   */
  public function createComponentTimezoneForm() {
    $form=new Form();
    $timezonesIdentifiersArr=\DateTimeZone::listIdentifiers();
    $valuesArr=[];
    foreach($timezonesIdentifiersArr as $identifier) {
      $valuesArr[$identifier]=$identifier;
    }
    $form->addSelect('timezone', 'Use time zone:', $valuesArr);
    $form->addSubmit('save', 'Save & continue...')->onClick[]=function (SubmitButton $submitButton) {
      //save the timezone
      $configManager=$this->createConfigManager();
      $timezoneIdentifier=$submitButton->getForm()->getValues()->timezone;
      $configManager->data['php']['date.timezone']=$timezoneIdentifier;
      $configManager->saveConfig();
      //redirect to the next step
      $this->redirect($this->getNextStep('timezone'));
    };
    return $form;
  }

  /**
   * Formulář pro zadání přístupů k databázi pro aplikační data
   * @return Form
   */
  public function createComponentDatabaseForm() {
    $form=new Form();
    $form->addSelect('driver', 'Database type:', ['mysqli'=>'MySQL']);
    $form->addText('host', 'Server:')
      ->setRequired('Input the server address!');
    $form->addText('username', 'DB username:')
      ->setRequired('Input the database username!');
    $form->addPassword('password', 'DB password:');
    $form->addText('database', 'Database name:')
      ->setRequired('Input the name of a database for the application data!');
    $form->addSubmit('submit', 'Save & continue...')
      ->onClick[]=function (SubmitButton $submitButton) {
        $form=$submitButton->form;
        $databaseConfigArr=$form->getValues(true);
        $error=false;
        try {
          $databaseManager=new DatabaseManager($databaseConfigArr);
          if(!$databaseManager->isConnected()) {
            $error=true;
          }
        } catch(\Exception $e) {
          $error=true;
        }
        if($error || empty($databaseManager)) {
          $form->addError('Database connection failed! Please check, if the database exists and if it is accessible.');
          return;
        }
        //create database
        try{
          /** @noinspection PhpUndefinedVariableInspection */
          $databaseManager->createDatabase();
        }catch (\Exception $e){
          $form->addError('Database structure creation failed! Please check, if the database exists and if it is empty.');
          return;
        }
        //save config and redirect
        $configManager=$this->createConfigManager();
        $configManager->data['parameters']['mainDatabase']=$databaseConfigArr;
        $configManager->saveConfig();
        $this->redirect($this->getNextStep('database'));
      };
    return $form;
  }

  /**
   * Formulář pro zadání přístupů k MySQL pro uživatelská data
   * @return Form
   */
  public function createComponentDataMysqlForm() {
    $form=new Form();
    /*FIXME
      _username: 'user*'
      _database: 'user*'
      username: root
      password:
      server: localhost
      allowFileImport: true
    */

    $form->addText('host', 'Server:')
      ->setRequired('Input the server address!');
    $form->addText('username', 'DB username:')
      ->setRequired('Input the database username!');
    $form->addPassword('password', 'DB password:');
    $form->addText('database', 'Database name:')
      ->setRequired('Input the name of a database for the application data!');
    $form->addSubmit('submit', 'Save & continue...')
      ->onClick[]=function (SubmitButton $submitButton) {
      $form=$submitButton->form;
      $databaseConfigArr=$form->getValues(true);
      $error=false;
      try {
        $databaseManager=new DatabaseManager($databaseConfigArr);
        if(!$databaseManager->isConnected()) {
          $error=true;
        }
      } catch(\Exception $e) {
        $error=true;
      }
      if($error || empty($databaseManager)) {
        $form->addError('Database connection failed! Please check, if the database exists and if it is accessible.');
        return;
      }
      //create database
      try{
        /** @noinspection PhpUndefinedVariableInspection */
        $databaseManager->createDatabase();
      }catch (\Exception $e){
        $form->addError('Database structure creation failed! Please check, if the database exists and if it is empty.');
        return;
      }
      //save config and redirect
      $configManager=$this->createConfigManager();
      $configManager->data['parameters']['mainDatabase']=$databaseConfigArr;
      $configManager->saveConfig();
      $this->redirect($this->getNextStep('database'));
    };
    return $form;
  }



  /**
   * Funkce vracející URL dalšího kroku wizardu
   *
   * @param string $currentStep
   * @return string
   */
  private function getNextStep($currentStep) {
    $index=array_search($currentStep, $this->wizardSteps);
    return $this->wizardSteps[$index+1];
  }

  /**
   * @return ConfigManager
   */
  private function createConfigManager() {
    return new ConfigManager(FilesManager::getRootDirectory().'/app/config/config.local.neon');
  }
}