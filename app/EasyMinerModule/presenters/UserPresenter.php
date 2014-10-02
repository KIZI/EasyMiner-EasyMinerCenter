<?php

namespace App\EasyMinerModule\Presenters;

use Nette;
use App\Model\EasyMiner\Facades\UsersFacade;
use Kdyby\Facebook\Facebook;
use Kdyby\Facebook\Dialog\LoginDialog as FacebookLoginDialog;
use Kdyby\Facebook\FacebookApiException;
use Kdyby\Google\Google;
use Kdyby\Google\Dialog\LoginDialog as GoogleLoginDialog;

class UserPresenter  extends BasePresenter{
  /** @var Facebook $facebook*/
  private $facebook;

  /** @var  Google $google */
  private $google;

  /** @var UsersFacade $usersFacade */
  public $usersFacade;

  /**
   * Akce pro odhlášení uživatele
   */
  public function actionLogout(){
    $this->getUser()->logout();
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
    $form->setMethod('POST');
    $form->addText('email', 'E-mail:')
      ->setRequired('You have to input your e-mail!');
    $form->addPassword('password', 'Heslo:')
      ->setRequired('You have to input your password!');
    //TODO
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
      ->setRequired('You have to input e-mail!');
    $form->addPassword('password', 'Heslo:')
      ->setRequired('You have to input password!');
    $form->addCheckbox('remember', 'Login permanently');
    $form->addSubmit('send', 'Login...');

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