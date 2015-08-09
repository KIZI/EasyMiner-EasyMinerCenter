<?php

namespace EasyMinerCenter\InstallModule\Model;

/**
 * Class DatabaseModel - model pro vytvoření struktury databáze
 * @package EasyMinerCenter\InstallModule\Model
 */
class DatabaseManager {
  /** @var  \DibiConnection $connection */
  private $connection;

  /**
   * Konstruktur, který se zároveň připojuje k databázi
   * @param array $config
   * @throws \DibiException
   */
  public function __construct($config){
    $this->connection = new \DibiConnection($config);
  }

  /**
   * Funkce pro kontrolu, jestli jsme připojeni k databázi
   * @return bool
   */
  public function isConnected(){
    return $this->connection->isConnected();
  }

  /**
   * Funkce pro vytvoření struktury databáze z mysql.sql
   * @throws \DibiException
   * @throws \RuntimeException
   */
  public function createDatabase(){
    $this->connection->loadFile(__DIR__.'/../data/mysql.sql');
  }

}