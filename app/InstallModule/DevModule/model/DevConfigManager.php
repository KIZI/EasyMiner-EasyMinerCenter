<?php

namespace EasyMinerCenter\InstallModule\DevModule\Model;

/**
 * Class DevConfigManager
 * @package EasyMinerCenter\InstallModule\DevModule\model
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
   * Method for check of user credentials
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
   * Method returning config for SUDO
   * @return array
   */
  public function getSudoCredentials() {
    return @$this->configParams['sudo'];
  }
}