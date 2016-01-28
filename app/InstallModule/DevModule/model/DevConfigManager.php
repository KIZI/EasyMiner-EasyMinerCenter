<?php

namespace EasyMinerCenter\InstallModule\DevModule\Model;

/**
 * Class DevConfigManager
 * @package EasyMinerCenter\InstallModule\DevModule\model
 * @author Stanislav Vojíř
 */
class DevConfigManager {

  private $configParams;

  /**
   * @param array $params
   */
  public function __construct($params) {
    $this->configParams=$params;
  }

  /**
   * Funkce pro kontrolu přístupu uživatele
   * @param string $username
   * @param string $password
   * @return bool
   */
  public function checkUserCredentials($username, $password) {
    if (!empty($this->configParams['credentials'])){
      foreach ($this->configParams['credentials'] as $usernameRow=>$hashedPasswordRow){
        if ($usernameRow==$username){
          return sha1($password)==$hashedPasswordRow;
        }
      }
    }
    return false;
  }

  /**
   * Funkce vracející konfiguraci pro sudo
   * @return array
   */
  public function getSudoCredentials() {
    return @$this->configParams['sudo'];
  }
}