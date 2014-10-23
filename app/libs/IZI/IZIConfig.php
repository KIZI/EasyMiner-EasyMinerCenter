<?php
namespace IZI;

use Nette\Object;

/**
 * Class IZIConfig - konfigurace IziUI
 * @package IZI
 * @property-read $DDPath
 * @property-read $FLPath
 * @property-read $FGCPath
 * @property-read $ERPath
 * @property-read $ETreePath
 * @property-read $FAPath
 * @property-read $KB_CONF_ID
 * @property-read $KB_EXC_ID
 * @property-read $MAX_INITIALIZATION_REQUESTS
 * @property-read $MAX_MINING_REQUESTS
 * @property-read $MAX_ETREE_REQUESTS
 */
class IZIConfig{

  private $params;

  public function __construct($params){
    $this->params=$params;
  }

  /**
   * @param string $name
   * @return bool
   */
  public function __isset($name){
    return (isset($this->params[$name]));
  }

  /**
   * @param string $name
   * @return string|null
   */
  public function __get($name){
    return @$this->params[$name];
  }

  /**
   * @param string $name
   * @param mixed $value
   * @throws \Exception
   */
  public function __set($name,$value){
    throw new \Exception('IZIConfig: It is not possible set config values!');
  }

} 