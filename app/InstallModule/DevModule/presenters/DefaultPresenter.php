<?php

namespace EasyMinerCenter\InstallModule\DevModule\Presenters;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

/**
 * Class DefaultPresenter - main presenter for DEV module
 * @package EasyMinerCenter\InstallModule\DevModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class DefaultPresenter extends BasePresenter{

  /**
   * Default action, if the User is not logged in, then redirects him to login view
   */
  public function renderDefault() {
    if (!$this->devConfigManager->checkUserCredentials($this->username,$this->password)){
      $this->setView('login');
    }
  }

  /**
   * Factory method returning login form
   * @return Form
   */
  public function createComponentLoginForm() {
    $form = new Form();
    $form->addText('username','DEV username:')
      ->setRequired();
    $form->addPassword('password','DEV password:')
      ->setRequired();
    $form->addSubmit('submit','OK')->onClick[]=function(SubmitButton $submitButton){
      $values=$submitButton->form->getValues();
      $this->username=$values->username;
      $this->password=$values->password;
      $this->redirect('default');
    };
    return $form;
  }

  /**
   * Startup method - does not require logged in User
   */
  public function startup() {
    $this->ignoreUserCheck=true;
    parent::startup();
  }
}