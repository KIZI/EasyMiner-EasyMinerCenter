<?php

namespace EasyMinerCenter\InstallModule\DevModule\Presenters;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

/**
 * Class DefaultPresenter - výchozí presenter pro DEV submodule
 * @package EasyMinerCenter\InstallModule\DevModule\Presenters
 * @author Stanislav Vojíř
 */
class DefaultPresenter extends BasePresenter{

  /**
   * Výchozí akce, pokud není uživatel přihlášen, je přesměrován...
   */
  public function renderDefault() {
    if (!$this->devConfigManager->checkUserCredentials($this->username,$this->password)){
      $this->setView('login');
    }
  }

  /**
   * Přihlašovací formulář
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
   * Funkce po spuštění, ruší kontrolu uživatelských přístupových údajů
   */
  public function startup() {
    $this->ignoreUserCheck=true;
    parent::startup();
  }
}