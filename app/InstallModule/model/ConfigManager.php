<?php

namespace EasyMinerCenter\InstallModule\Model;
use Nette\Neon\Neon;
use Nette\Security\Passwords;

/**
 * Class ConfigModel - model class for management of actual configuration of EasyMiner app
 * @package EasyMinerCenter\InstallModule\Model
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
    try{
      $this->data=Neon::decode(file_get_contents($neonFileName));
    }catch (\Exception $e){
      $this->data=[];
    }
  }

  /**
   * Method for check, if the given installation password matches the value saved in config file
   * @param string $password
   * @return bool
   */
  public function checkInstallationPassword($password) {
    $passwordHash=$this->data['parameters']['install']['password'];
    if (Passwords::verify($password,$passwordHash)){
      if (Passwords::needsRehash($password)){
        $this->saveInstallationPassword($password);
      }
      return true;
    }
    return false;
  }

  /**
   * Method for saving of new installation password to config file
   * @param string $newPassword
   */
  public function saveInstallationPassword($newPassword) {
    $this->data['parameters']['install']['password']=Passwords::hash($newPassword);
    $this->saveConfig();
  }

  /**
   * Method for checking, if there is a installation password in config file
   * @return bool
   */
  public function isSetInstallationPassword() {
    return !empty($this->data['parameters']['install']['password']);
  }

  /**
   * Method for updating of config file
   */
  public function saveConfig() {
    $encodedData=Neon::encode($this->data,Neon::BLOCK);
    file_put_contents($this->neonFileName,$encodedData);
  }

}