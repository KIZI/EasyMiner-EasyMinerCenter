<?php

namespace EasyMinerCenter\InstallModule\Model;
use Nette\Neon\Neon;

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
   * Funkce pro uložení konfigurace do souboru
   */
  public function saveConfig() {
    $encodedData=Neon::encode($this->data,Neon::BLOCK);
    file_put_contents($this->neonFileName,$encodedData);
  }

}