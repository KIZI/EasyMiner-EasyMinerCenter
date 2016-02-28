<?php
namespace EasyMinerCenter\Model\Data\Facades;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

/**
 * Class DatabasesFacade - nová fasáda pro práci s databázemi
 * @package EasyMinerCenter\Model\Data\Facades
 * @author Stanislav Vojíř
 */
class DatabasesFacade{
  /** @var  DatabaseFactory $databasesFactory */
  private $databaseFactory;
  /** @var  User $user */
  private $user;

  /**
   * @param DatabaseFactory $databaseFactory
   */
  public function __construct(DatabaseFactory $databaseFactory) {
    $this->databaseFactory=$databaseFactory;
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
   * Funkce vracející datové zdroje ve zvolené databázi
   * @param string $dbType
   * @return DbDatasource[]
   */
  public function getDbDatasources($dbType){
    //FIXME implement
    exit(var_dump($this->databaseFactory->getDbTypes()));

    $database=$this->getDatabase($dbType);
    return $database->getDbDatasources();
  }

}