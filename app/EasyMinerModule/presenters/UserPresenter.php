<?php

namespace App\EasyMinerModule\Presenters;

use LeanMapper\Exception\Exception;
use Nette;
use App\Model\EasyMiner\Facades\UsersFacade;
use Kdyby\Facebook\Facebook;
use Kdyby\Facebook\Dialog\LoginDialog as FacebookLoginDialog;
use Kdyby\Facebook\FacebookApiException;
use Kdyby\Google\Google;
use Kdyby\Google\Dialog\LoginDialog as GoogleLoginDialog;

class UserPresenter  extends BaseRestPresenter{
  /** @var Facebook $facebook*/
  private $facebook;

  /** @var  Google $google */
  private $google;

  /** @var UsersFacade $usersFacade */
  public $usersFacade;

  public function actionInfo(){
    if ($identity=$this->getUser()->identity){
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
   * Akce pro odhlášení uživatele
   */
  public function actionLogout(){
    $this->getUser()->logout(true);
    $this->redirect('login');
  }

  /**
   * Akce pro přihlášení uživatele
   */
  public function actionLogin(){
    if ($this->user->isLoggedIn()){
      //pokud je uživatel už přihlášen, přesměrujeme ho na otevření/vytvoření mineru
      $this->redirect('Data:NewMiner');
    }
  }

  public function actionRegister(){
    if ($this->user->isLoggedIn()){
      //pokud je uživatel už přihlášen, nedovolíme mu registrovat nový účet
      $this->flashMessage('You are currently logged in!','error');
      $this->redirect('Data:NewMiner');
    }
  }

  protected function createComponentRegistrationForm(){
    $form = new Nette\Application\UI\Form();
    $presenter=$this;
    $form->setMethod('POST');
    $form->addText('name', 'Name:')
      ->setAttribute('placeholder','Name')
      ->setAttribute('class','text')
      ->addRule(Nette\Forms\Form::FILLED,'You have to input your name!')
      ->addRule(Nette\Forms\Form::MIN_LENGTH,'You have to input your name!',5);
    $form->addText('email', 'E-mail:')
      ->setAttribute('placeholder','E-mail')
      ->setAttribute('class','text')
      ->addRule(Nette\Forms\Form::EMAIL,'You have to input valid e-mail address!')
      ->addRule(Nette\Forms\Form::FILLED,'You have to input your e-mail!')
      ->addRule(function($emailInput)use($presenter){
        try{
          $user=$presenter->usersFacade->findUserByEmail($emailInput->value);
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
      ->validateEqual($password,'Passwords do not match!');

    $form->addSubmit('submit', 'Sign up...')
      ->setAttribute('class','button');

    $form->onSuccess[] = function(Nette\Application\UI\Form $form,$values) use ($presenter){
      try{
        $user = $presenter->usersFacade->registerUser($values);
      }catch (Exception $e){
        $presenter->flashMessage('Welcome! Your user account was successfully registered.');
      }
      $presenter->getUser()->login($values['email'],$values['password']);
      $presenter->redirect('Data:newMiner');
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
        $facebookMe = $fb->api('/me');
        $facebookUserId = $fb->getUser();

        $presenter->getUser()->login($presenter->usersFacade->authenticateUserFromFacebook($facebookUserId,$facebookMe));

        $presenter->flashMessageLoginSuccess('Facebook');
      } catch (FacebookApiException $e) {
        $presenter->flashMessageLoginFailed('Facebook');
        $presenter->redirect('login');
      }
      $presenter->redirect('Data:NewMiner');
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
        $presenter->redirect('Data:NewTask');
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
      ->setRequired('You have to input e-mail!')
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
    $presenter=$this;
    $form->onSuccess[] = function(Nette\Application\UI\Form $form,$values) use ($presenter){
      if ($values->remember) {
        $presenter->getUser()->setExpiration('14 days', false, true);
      } else {
        $presenter->getUser()->setExpiration('20 minutes', true, true);
      }

      try{
        $presenter->getUser()->login($values->email, $values->password);
        $presenter->flashMessageLoginSuccess();
        $presenter->redirect('Data:NewMiner');

      } catch (Nette\Security\AuthenticationException $e) {
        $form->addError($e->getMessage());
      }
    };
    return $form;
  }

  protected function flashMessageLoginSuccess($service=''){
    $this->flashMessage('Welcome to EasyMiner system! You are successfully logged in.','info');
  }
  protected function flashMessageLoginFailed($service){
    $this->flashMessage('Login using '.$service.' failed.');
  }


  public function injectFacebook(Facebook $facebook){
    $this->facebook=$facebook;
  }
  public function injectGoogle(Google $google){
    $this->google=$google;
  }
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
} 