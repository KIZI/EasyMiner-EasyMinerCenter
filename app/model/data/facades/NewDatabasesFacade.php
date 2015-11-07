<?php
namespace EasyMinerCenter\Model\Data\Facades;

/**
 * Class NewDatabasesFacade - nová fasáda pro práci s databázemi
 * @package EasyMinerCenter\Model\Data\Facades
 * @author Stanislav Vojíř
 */
class NewDatabasesFacade {
  /** @var  array $databasesConfig - pole s konfigurací přístupů k jednotlivým typům databází*/
  private $databasesConfig;

  /**
   * @param array $databasesConfig
   */
  public function __construct($databasesConfig) {
    $this->databasesConfig=$databasesConfig;
  }

  /**
   * Funkce vracející pole s nakonfigurovanými (tj. dostupnými) typy databází
   * @param bool $assocWithNames=false
   * @return array
   */
  public function getDatabaseTypes($assocWithNames=false) {
    //TODO implement
    if ($assocWithNames){
      return ['mysql'=>'MySQL','limited'=>'Limited data service DB','unlimited'=>'Unlimited data service DB'];
    }else{
      return ['mysql','limited','unlimited'];
    }

  }

  public function getPrefferedDatabaseType() {
    //TODO implement
    return 'mysql';
  }

}