<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\EasyMinerModule\Components\MailerControlFactory;
use EasyMinerCenter\EasyMinerModule\Components\MailerControl;
use LeanMapper\Exception\Exception;
use Nette;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Kdyby\Facebook\Facebook;
use Kdyby\Facebook\Dialog\LoginDialog as FacebookLoginDialog;
use Kdyby\Facebook\FacebookApiException;
use Kdyby\Google\Google;
use Kdyby\Google\Dialog\LoginDialog as GoogleLoginDialog;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Security\Passwords;

/**
 * Class UserPresenter
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class UserPresenter  extends BasePresenter{
  use ResponsesTrait;

  /** @var Facebook $facebook*/
  private $facebook;
  /** @var  Google $google */
  private $google;
  /** @var UsersFacade $usersFacade */
  public $usersFacade;
  /** @var  MailerControlFactory $mailerControlFactory */
  private $mailerControlFactory;

  /** @persistent */
  public $backlink;

  /**
   * Action sending details about current user as JSON
   */
  public function actionInfo(){
    if ($identity=$this->getUser()->identity){
      /** @noinspection PhpUndefinedFieldInspection */
      $this->sendJsonResponse(array(
        'name'=>$identity->name,
        'email'=>$identity->email,
        'id'=>$this->user->id
      ));
    }else{
      $this->sendJsonResponse(array('name'=>null,'email'=>null,'id'=>0));
    }
  }

  /**
   * Action for displaying of the user account details
   * @param int|null $user
   * @throws Nette\Application\BadRequestException
   */
  public function renderDetails($user=null){
    if (!empty($user)){
      if (!$this->user->isInRole('admin')){
        throw new Nette\Application\BadRequestException('You are not authorized to access details of another users!');
      }
      $user=$this->usersFacade->findUser($user);
    }else{
      $user=$this->usersFacade->findUser($this->user->getId());
    }
    $this->template->selectedUser=$user;
  }

  /**
   * Action for change of the local password
   * @param int|null $user
   * @throws Nette\Application\BadRequestException
   */
  public function renderChangePassword($user=null){
    if (!empty($user)){
      if (!$this->user->isInRole('admin')){
        throw new Nette\Application\BadRequestException('You are not authorized to access details of another users!');
      }
      $user=$this->usersFacade->findUser($user);
    }else{
      $user=$this->usersFacade->findUser($this->user->getId());
    }
    $this->template->selectedUser=$user;
    /** @var Form $form */
    $form=$this->getComponent('changePasswordForm');
    $form->setDefaults(['oldPassword'=>'','newPassword'=>'','newPassword2'=>'','user'=>$user->userId]);
    if ($user->password==''){
      /** @var TextInput $oldPasswordInput */
      $oldPasswordInput=$form->getComponent('oldPassword',true);
      $oldPasswordInput
        ->setAttribute('readonly','readonly')
        ->setDisabled(true);
    }
  }

  /**
   * Action for displaying forgotten password renewal view
   * @param int|null $id
   * @param string|null $user=null
   * @param string|null $code=null
   */
  public function renderForgottenPassword($id=null, $user=null, $code=null){
    if (!empty($id) && !empty($user) && !empty($code)){

      try{
        $user=$this->usersFacade->findUser($user);
        $userForgottenPassword=$this->usersFacade->findUserForgottenPassword($id);
        if ($userForgottenPassword->user->userId!=$user->userId || $userForgottenPassword->code!=$code){
          throw new \Exception('Hacking attempt!');
        }
      }catch (\Exception $e){
        $this->flashMessage('Requested password renewal not found! Maybe, the renewal code was too old. Please go to "Forgotten password".','error');
        $this->redirect('login');
        return;
      }

      /** @var Form $form */
      $form=$this->getComponent('changeForgottenPasswordForm');
      $form->setDefaults([
        'id'=>$id,
        'code'=>$code,
        'email'=>$user->email,
        'user'=>$user->userId
      ]);
      $this->template->form=$form;
      $this->template->title=$this->translate('Forgotten password change');
    }else{
      $this->template->title=$this->translate('Forgotten password');
      $this->template->form=$this->getComponent('forgottenPasswordForm');
    }
  }

  /**
   * Action for User logout
   */
  public function actionLogout(){
    $this->getUser()->logout(true);
    $this->flashMessage('You have been logged out successfully.');
    $this->redirect('Homepage:default');
  }

  /**
   * Action for User login (before renderLogin)
   */
  public function actionLogin(){
    if ($this->user->isLoggedIn()){
      //if the user is already logged in, execute the redirect
      $this->finalRedirect();
    }
  }

  /**
   * Action for displaying login view
   */
  public function renderLogin() {
    //check support of social network logins
    /** @var FacebookLoginDialog $facebookLogin */
    $facebookLogin=$this->getComponent('facebookLogin');
    $this->template->allowFacebook=(!empty($facebookLogin->facebook->config->appId));

    /** @var GoogleLoginDialog $googleLogin */
    $googleLogin=$this->getComponent('googleLogin');
    $this->template->allowGoogle=(!empty($googleLogin->getGoogle()->getConfig()->clientId));
  }

  /**
   * Action for check if the user is not logged in (before renderRegister)
   */
  public function actionRegister(){
    if ($this->user->isLoggedIn()){
      //pokud je uživatel už přihlášen, nedovolíme mu registrovat nový účet
      $this->flashMessage('You are currently logged in!','error');
      $this->finalRedirect();
    }
  }

  /**
   * Action for displaying new user registration view
   */
  public function renderRegister() {
    //kontrola podpory sociálních sítí
    /** @var FacebookLoginDialog $facebookLogin */
    $facebookLogin=$this->getComponent('facebookLogin');
    $this->template->allowFacebook=(!empty($facebookLogin->facebook->config->appId));

    /** @var GoogleLoginDialog $googleLogin */
    $googleLogin=$this->getComponent('googleLogin');
    $this->template->allowGoogle=(!empty($googleLogin->getGoogle()->getConfig()->clientId));
  }

  /**
   * Registration form factory
   * @return Form
   */
  protected function createComponentRegistrationForm(){
    $form = new Nette\Application\UI\Form();
    $presenter=$this;
    $form->setMethod('POST');
    $form->addText('name', 'Name:')
      ->setAttribute('placeholder','Name')
      ->setAttribute('class','text')
      ->addRule(Nette\Forms\Form::FILLED,'You have to input your name!')
      ->addRule(Nette\Forms\Form::MIN_LENGTH,'You have to input your name!',2);
    $form->addText('email', 'E-mail:')
      ->setAttribute('placeholder','E-mail')
      ->setAttribute('class','text')
      ->addRule(Nette\Forms\Form::EMAIL,'You have to input valid e-mail address!')
      ->addRule(Nette\Forms\Form::FILLED,'You have to input your e-mail!')
      ->addRule(function($emailInput)use($presenter){
        try{
          $presenter->usersFacade->findUserByEmail($emailInput->value);
          return false;
        }catch (\Exception $e){}
        return true;
      },'User account with this e-mail already exists!');
    $password=$form->addPassword('password', 'Password:');
    $password->setAttribute('placeholder','Password')
      ->setAttribute('class','text')
      ->addRule(Nette\Forms\Form::FILLED,'You have to input your password!')
      ->addRule(Nette\Forms\Form::MIN_LENGTH,'Minimal length of password is %s characters!',6);
    $form->addPassword('rePassword', 'Password (again):')
      ->setAttribute('placeholder','Password')
      ->setAttribute('class','text')
      ->addRule(Nette\Forms\Form::FILLED,'You have to input your password!')
      ->addRule(Nette\Forms\Form::EQUAL,'Passwords do not match!',$password);

    $submitButton=$form->addSubmit('submit', 'Sign up...');
    $submitButton->setAttribute('class','button');
    $submitButton->onClick[]=function(SubmitButton $submitButton){
      $values=$submitButton->getForm(true)->getValues(true);
      try{
        $this->usersFacade->registerUser($values);
      }catch (Exception $e){
        $this->flashMessage('Welcome! Your user account was successfully registered.');
      }
      $this->getUser()->login($values['email'],$values['password']);
      $this->finalRedirect();
    };
    return $form;
  }

  /** @return FacebookLoginDialog */
  protected function createComponentFacebookLogin() {
    /** @var FacebookLoginDialog $dialog */
    $dialog = $this->facebook->createDialog('login');
    $presenter=$this;
    $dialog->onResponse[] = function (FacebookLoginDialog $dialog) use ($presenter){
      $fb = $dialog->getFacebook();
      if (!$fb->getUser()) {
        $presenter->flashMessageLoginFailed('Facebook');
        return;
      }

      try {
        $facebookMe = $fb->api('/me?fields=id,first_name,last_name,picture,email');
        $facebookUserId = $fb->getUser();
        $presenter->getUser()->login($presenter->usersFacade->authenticateUserFromFacebook($facebookUserId,$facebookMe));

        $presenter->flashMessageLoginSuccess('Facebook');
      } catch (FacebookApiException $e) {
        $presenter->flashMessageLoginFailed('Facebook');
        $presenter->redirect('login');
      }
      $presenter->finalRedirect();
    };

    return $dialog;
  }

  /** @return GoogleLoginDialog */
  protected function createComponentGoogleLogin() {
    /** @var GoogleLoginDialog $dialog */
    $dialog=$this->google->createLoginDialog();
    $presenter=$this;
    $dialog->onResponse[] = function (GoogleLoginDialog $dialog) use ($presenter){
      $google = $dialog->getGoogle();
      if (!($google->getUser() && $google->getProfile())) {
        $presenter->flashMessageLoginFailed('Google');
        return;
      }
      try {
        $googleUser=$google->getUser();
        $googleProfile=$google->getProfile();
        $presenter->getUser()->login($presenter->usersFacade->authenticateUserFromGoogle($googleUser, $googleProfile));

        $presenter->flashMessageLoginSuccess('Google');
        $presenter->finalRedirect();
      } catch (\Google_Exception $e) {
        $presenter->flashMessageLoginFailed('Google');
      }
      $presenter->redirect('login');
    };
    return $dialog;
  }

  /**
   * Login form factory.
   * @return \Nette\Application\UI\Form
   */
  protected function createComponentLoginForm(){
    $form = new Nette\Application\UI\Form();
    $form->setMethod('POST');
    $form->addText('email', 'E-mail:')
      ->setRequired('You have to input your e-mail!')
      ->setAttribute('type','email')
      ->setAttribute('placeholder','E-mail')
      ->setAttribute('class','text');
    $form->addPassword('password', 'Password:')
      ->setRequired('You have to input password!')
      ->setAttribute('placeholder','Password')
      ->setAttribute('class','text');
    $form->addCheckbox('remember', ' Keep me logged in for 2 week...');
    $form->addSubmit('submit', 'Log in...')
      ->setAttribute('class','button');

    // call method signInFormSucceeded() on success
    $form->onSuccess[] = function(Nette\Application\UI\Form $form,$values){
      if ($values->remember) {
        $this->getUser()->setExpiration('14 days', false, true);
      } else {
        $this->getUser()->setExpiration('20 minutes', true, true);
      }

      try{
        $this->getUser()->login($values->email, $values->password);
        $this->usersFacade->cleanForgottenPasswords($this->getUser()->id);
        $this->flashMessageLoginSuccess();
        $this->finalRedirect();

      } catch (Nette\Security\AuthenticationException $e) {
        $form->addError($e->getMessage());
      }
    };
    return $form;
  }

  /**
   * Method for final redirect after login
   */
  protected function finalRedirect(){
    if (!empty($this->backlink)){
      //restore previous request
      $this->restoreRequest($this->backlink);
    }
    //previous request was not found - run default redirect
    if ($this->user->isAllowed('EasyMiner:Data','newMiner')){
      $this->redirect('Data:NewMiner');
    }else{
      $this->redirect('Homepage');
    }
  }

  /**
   * Display flash message after succesfull login
   * @param string $service=''
   */
  protected function flashMessageLoginSuccess($service=''){
    $this->flashMessage('Welcome to EasyMiner system! You are successfully logged in.','info');
  }

  /**
   * Display flash message if login failed
   * @param string $service
   */
  protected function flashMessageLoginFailed($service){
    $this->flashMessage('Login using '.$service.' failed.');
  }

  /**
   * Form for password change
   * @return Form
   */
  protected function createComponentChangePasswordForm() {
    $form=new Form();
    $form->setTranslator($this->getTranslator());
    $userInput=$form->addHidden('user');
    $form->addPassword('oldPassword','Old password:')
      ->addRule(function(TextInput $oldPasswordInput)use($userInput){
        $user=$this->usersFacade->findUser($userInput->value);
        return (($user->password=='')||Passwords::verify($oldPasswordInput->value,$user->password));
      },'Invalid old password!');
    $newPassword=$form->addPassword('newPassword','New password:');
    $newPassword
      ->addRule(Form::MIN_LENGTH,'minimum password length is %d characters',5)
      ->setRequired('Input new password!');
    $form->addPassword('newPassword2','New password:')
      ->setRequired('Input new password!')
      ->addRule(Form::EQUAL,'New passwords do not match!',$newPassword);
    $form->addSubmit('save','Save new password')->onClick[]=function(SubmitButton $button){
      //password change
      $values=$button->getForm(true)->getValues();
      $user=$this->usersFacade->findUser($values->user);
      $user->password=Passwords::hash($values->newPassword);
      if ($this->usersFacade->saveUser($user)){
        $this->flashMessage('New password saved...');
      }
      //final redirect
      $values=$button->getForm(true)->getValues();
      $redirectParams=[];
      if ($this->user->id!=$values->user){
        $redirectParams['user']=$values->user;
        $this->redirect('details',$redirectParams);
      }else{
        $this->redirect('logout');
      }
    };
    $form->addSubmit('storno','storno')
      ->setValidationScope([])
      ->onClick[]=function(){
      $this->redirect('login');
    };
    return $form;
  }

  /**
   * Form for forgotten password change
   * @return Form
   */
  protected function createComponentChangeForgottenPasswordForm() {
    $form=new Form();
    $form->setTranslator($this->getTranslator());
    $form->addHidden('user');
    $form->addHidden('code');
    $form->addHidden('id');
    $form->addText('email','E-mail:')->setAttribute('readonly')->setDisabled(true);
    $newPassword=$form->addPassword('newPassword','New password:');
    $newPassword
      ->addRule(Form::MIN_LENGTH,'minimum password length is %d characters',5)
      ->setRequired('Input new password!');
    $form->addPassword('newPassword2','New password:')
      ->setRequired('Input new password!')
      ->addRule(Form::EQUAL,'New passwords do not match!',$newPassword);
    $form->addSubmit('save','Change password')->onClick[]=function(SubmitButton $button){
      //password change
      $values=$button->getForm(true)->getValues();

      try{
        $user=$this->usersFacade->findUser($values->user);
        $userForgottenPassword=$this->usersFacade->findUserForgottenPassword($values->id);
        if ($userForgottenPassword->user->userId!=$user->userId || $userForgottenPassword->code!=$values->code){
          throw new \Exception('Hacking attempt!');
        }
      }catch (\Exception $e){
        $this->flashMessage('Requested password renewal not found! Maybe, the renewal code was too old. Please go to "Forgotten password".','error');
        $this->redirect('login');
        return;
      }

      $user->password=Passwords::hash($values->newPassword);
      if ($this->usersFacade->saveUser($user)){
        $this->flashMessage('New password saved...');
      }
      //final redirect
      $this->redirect('login');
    };
    $form->addSubmit('storno','storno')
      ->setValidationScope([])
      ->onClick[]=function(SubmitButton $button){
      $values=$button->getForm(true)->getValues();
      $redirectParams=[];
      if ($this->user->id!=$values->user){
        $redirectParams['user']=$values->user;
      }
      $this->redirect('details',$redirectParams);
    };
    return $form;
  }

  /**
   * Form for forgotten password renewal
   * @return Form
   */
  protected function createComponentForgottenPasswordForm(){
    $form = new Form();
    $form->setTranslator($this->translator);
    $form->addProtection('Please submit the form again');
    $form->addText('email','E-mail:')
      ->setRequired('You have to input your e-mail!')
      ->addRule(Form::EMAIL,'You have to input valid e-mail!');

    $form->addSubmit('submit','Submit...')
      ->onClick[]=function(SubmitButton $button){
      $values=$button->getForm(true)->getValues(true);
      try{
        $user=$this->usersFacade->findUserByEmail($values['email']);
        $userForgottenPassword=$this->usersFacade->generateUserForgottenPassword($user);
        /** @var MailerControl $mailerControl */
        $mailerControl=$this->getComponent('mailerControl',true);
        $result=$mailerControl->sendMailForgottenPassword($userForgottenPassword);
        if ($result){
          $this->flashMessage('Information to reset your password has been sent to your e-mail.');
        }else{
          $this->flashMessage('It was not possible to sent you information for password reset. Please try it later, or contact the administrator.');
        }
      }catch (\Exception $e){
        //requested User account was not found
        $this->flashMessage('Selected user account does not exist. Please register a new one...');
        $this->redirect('register');
        return;
      }
      $this->redirect('login');
    };
    $form->addSubmit('storno','storno')
      ->setValidationScope([])
      ->onClick[]=function(){
      $this->redirect('login');
    };
    return $form;
  }

  /**
   * @return \EasyMinerCenter\EasyMinerModule\Components\MailerControl
   */
  public function createComponentMailerControl(){
    return $this->mailerControlFactory->create();
  }

  #region injections
  public function injectFacebook(Facebook $facebook){
    $this->facebook=$facebook;
  }
  public function injectGoogle(Google $google){
    $this->google=$google;
  }
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }

  public function injectMailerControlFactory(MailerControlFactory $mailerControlFactory){
    $this->mailerControlFactory = $mailerControlFactory;
  }
  #endregion injections
} 