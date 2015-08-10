<?php

namespace EasyMinerCenter\InstallModule\Model;
use Nette\Neon\Neon;
use Nette\Security\Passwords;

/**
 * Class ConfigModel - třída spravující aktuální konfiguraci aplikace
 * @package EasyMinerCenter\InstallModule\Model
 */
class ConfigManager {
  /** @var string $neonFileName */
  private $neonFileName;
  /** @var mixed $data - dekódovaná data konfigurace */
  public $data;

  /**
   * @param string $neonFileName
   */
  public function __construct($neonFileName){
    $this->neonFileName=$neonFileName;
    $this->data=Neon::decode(file_get_contents($neonFileName));
  }

  /**
   * Funkce kontrolující, zda zadané heslo odpovídá hodnotě uložené v configu
   * @param string $password
   * @return bool
   */
  public function checkInstallationPassword($password) {
    $passwordHash='';
    if (Passwords::verify($password,$passwordHash)){
      if (Passwords::needsRehash($password)){
        $this->saveInstallationPassword($password);
      }
      return true;
    }
    return false;
  }

  /**
   * Funkce pro uložení nového instalačního hesla do config souboru
   * @param string $newPassword
   */
  public function saveInstallationPassword($newPassword) {
    $this->data['parameters']['install']['password']=Passwords::hash($newPassword);
    $this->saveConfig();
  }

  /**
   * Funkce pro kontrolu, jestli je nastaveno instalační heslo
   * @return bool
   */
  public function isSetInstallationPassword() {
    return !empty($this->data['parameters']['install']['password']);
  }

  /**
   * Funkce pro uložení konfigurace do souboru
   */
  public function saveConfig() {
    $encodedData=Neon::encode($this->data,Neon::BLOCK);
    file_put_contents($this->neonFileName,$encodedData);
  }

}