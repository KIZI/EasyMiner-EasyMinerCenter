<?php
namespace EasyMinerCenter\EasyMinerModule\Components;

use Nette\Localization\ITranslator;
use Nette\Mail\IMailer;

class MailerControlFactory {

  /** @var  ITranslator $translator */
  private $translator;
  /** @var  IMailer $mailer */
  private $mailer;
  /** @var  string $mailFrom */
  private $mailFrom;

  /**
   * @param string $mailFrom
   * @param ITranslator $translator
   * @param IMailer $mailer
   */
  public function __construct($mailFrom, ITranslator $translator, IMailer $mailer){
    $this->translator=$translator;
    $this->mailer=$mailer;
    $this->mailFrom=$mailFrom;
  }

  /** @return MailerControl */
  public function create(){
    return new MailerControl($this->translator,$this->mailer,$this->mailFrom);
  }

} 