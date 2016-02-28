<?php
namespace EasyMinerCenter\Model\Data\Facades;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
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
  public function getDbTypes($assocWithNames=false) {//TODO remove?
    //TODO implementovat kontrolu dostupných (nakonfigurovaných) typů databází
    if ($assocWithNames){
      return [
        DbConnection::TYPE_MYSQL=>DbConnection::TYPE_MYSQL_NAME,
        DbConnection::TYPE_LIMITED=>DbConnection::TYPE_LIMITED_NAME,
        DbConnection::TYPE_UNLIMITED=>DbConnection::TYPE_UNLIMITED_NAME
      ];
    }else{
      return [DbConnection::TYPE_MYSQL,DbConnection::TYPE_LIMITED,DbConnection::TYPE_UNLIMITED];
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


}