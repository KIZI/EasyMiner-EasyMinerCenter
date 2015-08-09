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
    'details',  //zadání dalších parametrů/detailů
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
    //naplnění výchozích parametrů
    $configManager=$this->createConfigManager();
    /** @var Form $form */
    $form=$this->getComponent('loginsForm');

    if(!empty($configManager->data['facebook'])) {
      //set currently defined params
      try{
        $form->setDefaults([
          'allow_facebook'=>1,
          'facebookAppId'=>$configManager->data['facebook']['appId'],
          'facebookAppSecret'=>$configManager->data['facebook']['appSecret']
        ]);
      }catch (\Exception $e){/*ignore error*/}
    }

    if(!empty($configManager->data['google'])) {
      //set currently defined params
      try{
        $form->setDefaults([
          'allow_google'=>1,
          'googleClientId'=>$configManager->data['google']['clientId'],
          'googleClientSecret'=>$configManager->data['google']['clientSecret']
        ]);
      }catch (\Exception $e){/*ignore error*/}
    }

  }

  /**
   * Akce pro volbu podporovaných typů minerů
   */
  public function actionMiners() {
    //TODO actionMiners
  }

  /**
   * Akce pro zadání doplňujících parametrů
   */
  public function actionDetails() {
    $configManager=$this->createConfigManager();
    if(!empty($configManager->data['parameters']['mail_from'])) {
      //set currently defined database connection params
      try{
        /** @var Form $form */
        $form=$this->getComponent('detailsForm');
        $form->setDefaults(['mail_from'=>$configManager->data['parameters']['mail_from']]);
      }catch (\Exception $e){/*ignore error*/}
    }
    //TODO configure automatically filled-in params
  }
  
  /**
   * Akce pro ukončení průvodce - smazání cache, přesměrování
   */
  public function actionFinish() {
    //TODO actionFinish
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
    /*FIXME createComponentDataMysqlForm
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
   * Formulář pro zadání údajů pro přihlašování přes sociální sítě
   * @return Form
   */
  public function createComponentLoginsForm() {
    $form=new Form();
    $form->addSelect('allow_local','Allow local user accounts:',[1=>'yes'])
      ->setAttribute('readonly')
      ->setAttribute('class','withSpace')
      ->setDisabled(true);
    $allowFacebook=$form->addSelect('allow_facebook','Allow Facebook login:',[0=>'no',1=>'yes']);
    $allowFacebook->addCondition(Form::EQUAL,1)
      ->toggle('facebookAppId',true)
      ->toggle('facebookAppSecret',true);
    $form->addText('facebookAppId','Facebook App ID:')
      ->setOption('id','facebookAppId')
      ->addConditionOn($allowFacebook,Form::EQUAL,1)
        ->setRequired('You have to input Facebook App ID!');
    $form->addText('facebookAppSecret','Facebook Secret Key:',null,32)
      ->setAttribute('class','withSpace')
      ->setOption('id','facebookAppSecret')
      ->addConditionOn($allowFacebook,Form::EQUAL,1)
        ->setRequired('You have to input Facebook Secret Key!')
        ->addRule(Form::LENGTH,'Secret Key length has to be %s chars.',32);

    $allowGoogle=$form->addSelect('allow_google','Allow Google login:',[0=>'no',1=>'yes']);
    $allowGoogle->addCondition(Form::EQUAL,1)
      ->toggle('googleClientId',true)
      ->toggle('googleClientSecret',true);
    $form->addText('googleClientId','Google Client ID:')
      ->setOption('id','googleClientId')
      ->addConditionOn($allowGoogle,Form::EQUAL,1)
        ->setRequired('You have to input Google Client ID!');
    $form->addText('googleClientSecret','Google Secret Key:',null,24)
      ->setOption('id','googleClientSecret')
      ->addConditionOn($allowGoogle,Form::EQUAL,1)
        ->setRequired('You have to input Google Secret Key!')
        ->addRule(Form::LENGTH,'Secret Key length has to be %s chars.',24);;

    $form->addSubmit('submit','Save & continue...')
      ->onClick[]=function(SubmitButton $submitButton){
        $values=$submitButton->form->getValues(true);
        $configManager=$this->createConfigManager();
        if ($values['allow_facebook']==1){
          $configManager->data['facebook']=[
            'appId'=>$values['facebookAppId'],
            'appSecret'=>$values['facebookAppSecret']
          ];
        }else{
          $configManager->data['facebook']=[
            'appId'=>'',
            'appSecret'=>'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
          ];
        }
        if ($values['allow_google']==1){
          $configManager->data['google']=[
            'clientId'=>$values['googleClientId'],
            'clientSecret'=>$values['googleClientSecret']
          ];
        }else{
          $configManager->data['google']=[
            'clientId'=>'',
            'clientSecret'=>'xxxxxxxxxxxxxxxxxxxxxxxx'
          ];
        }
      };
    return $form;
  }

  /**
   * Formulář pro zadání údajů pro přístup k minerům
   * @return Form
   */
  public function createComponentMinersForm() {
    //FIXME createComponentMinersForm
  }

  /**
   * Formulář pro zadání doplňujících parametrů
   * @return Form
   */
  public function createComponentDetailsForm() {
    $form = new Form();
    $form->addText('mail_from','Send application e-mails from:')
      ->setAttribute('placeholder','sender@server.tld')
      ->setRequired(true)
      ->addRule(Form::EMAIL,'Input valid e-mail address!');
    $form->addSubmit('submit','Save & continue')->onClick[]=function(SubmitButton $submitButton){
      //uložení e-mailu
      $configManager=$this->createConfigManager();
      $values=$submitButton->form->getValues(true);
      $configManager->data['parameters']['mail_from']=$values['mail_from'];
      $configManager->saveConfig();
      $this->redirect($this->getNextStep('details'));
    };
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