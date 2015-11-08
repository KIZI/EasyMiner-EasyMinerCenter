<?php
namespace EasyMinerCenter\Model\Data\Facades;
use EasyMinerCenter\Model\Data\Databases\IDatabase;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

/**
 * Class NewDatabasesFacade - nová fasáda pro práci s databázemi
 * @package EasyMinerCenter\Model\Data\Facades
 * @author Stanislav Vojíř
 */
class NewDatabasesFacade{
  /** @var  array $databasesConfig - pole s konfigurací přístupů k jednotlivým typům databází*/
  private $databasesConfig;
  /** @var  IDatabase[] $databases */
  private $databases;
  /** @var  User $user */
  private $user;

  /**
   * @param array $databasesConfig
   */
  public function __construct($databasesConfig) {
    $this->databasesConfig=$databasesConfig;
  }

  /**
   * Metoda pro vybrání konkrétního uživatele
   * @param User $user
   */
  public function setUser(User $user) {
    $this->user=$user;
  }

  /**
   * Funkce vracející pole s nakonfigurovanými (tj. dostupnými) typy databází
   * @param bool $assocWithNames=false
   * @return string[]|array
   */
  public function getDbTypes($assocWithNames=false) {
    //TODO implement
    if ($assocWithNames){
      return ['mysql'=>'MySQL','limited'=>'Limited data service DB','unlimited'=>'Unlimited data service DB'];
    }else{
      return ['mysql','limited','unlimited'];
    }

  }

  /**
   * Funkce vracející výchozí typ databáze
   * @return string
   */
  public function getPreferredDatabaseType() {
    //TODO implement
    return 'mysql';
  }

  /**
   * Funkce vracející instanci konkrétního ovladače databáze
   * @param string|null $dbType=null
   * @return IDatabase
   */
  private function getDatabase($dbType=null) {
    if (empty($this->databases[$dbType])){
      //TODO připojení ke konkrétní databázi

    }
    return $this->databases[$dbType];
  }

  /**
   * Funkce vracející datové zdroje ve zvolené databázi
   * @param string $dbType
   * @return DbDatasource[]
   */
  public function getDbDatasources($dbType){
    $database=$this->getDatabase($dbType);
    return $database->getDbDatasources();
  }

}