<?php

namespace EasyMinerCenter\EasyMinerModule\Components;

use EasyMinerCenter\Model\EasyMiner\Entities\UserForgottenPassword;
use Nette\Application\UI\Control;
use Nette\Application\UI\ITemplate;
use Nette\Localization\ITranslator;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

/**
 * Class MailerControl
 * @package EasyMinerCenter\EasyMinerModule\Components
 */
class MailerControl extends Control{

  /** @var  ITranslator $translator */
  private $translator;
  /** @var  IMailer $mailer */
  private $mailer;
  /** @var string $mailFrom */
  private $mailFrom;


  /**
   * Funkce pro odeslání e-mailu pro obnovu zapomenutého hesla
   * @param UserForgottenPassword $userForgottenPassword
   * @return bool
   */
  public function sendMailForgottenPassword(UserForgottenPassword $userForgottenPassword){
    $mailMessage=$this->prepareMailMessage('ForgottenPassword',['userForgottenPassword'=>$userForgottenPassword]);
    $mailMessage->addTo($userForgottenPassword->user->email);
    try {
      $this->mailer->send($mailMessage);
    }catch (\Exception $e){
      return false;
    }
    return true;
  }



  /**
   * @param $templateName
   * @param array $params=[]
   * @return Message
   */
  private function prepareMailMessage($templateName,$params=[]){
    //připravení šablony a naplnění parametry
    $template=$this->createTemplate();
    //připravení mailu
    $mailMessage = new Message();

    $template->setFile(__DIR__.'/mail'.$templateName.'.latte');
    if (!empty($params)){
      foreach ($params as $paramName=>$param){
        $template->$paramName=$param;
      }
    }
    $template->mail=$mailMessage;
    $mailMessage->setFrom($this->mailFrom);
    $mailMessage->setHtmlBody($template);
    return $mailMessage;
  }

  
  /**
   * @param ITranslator $translator
   * @param IMailer $mailer
   * @param string $mailFrom
   */
  public function __construct(ITranslator $translator, IMailer $mailer, $mailFrom){
    $this->translator=$translator;
    $this->mailer=$mailer;
    $this->mailFrom=$mailFrom;
  }
  
  /**
   * @return ITemplate
   */
  public function createTemplate(){
    $template=parent::createTemplate();
    /** @noinspection PhpUndefinedMethodInspection */
    $template->setTranslator($this->translator);
    return $template;
  }


} 