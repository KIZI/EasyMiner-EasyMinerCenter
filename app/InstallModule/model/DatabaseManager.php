<?php

namespace EasyMinerCenter\InstallModule\Model;

/**
 * Class DatabaseModel - model pro vytvoření struktury databáze
 * @package EasyMinerCenter\InstallModule\Model
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class DatabaseManager {
  /** @var  \DibiConnection $connection */
  private $connection;

  /**
   * DatabaseManager constructor, also connects us to the database
   * @param array $config
   * @throws \DibiException
   */
  public function __construct($config){
    $this->connection = new \DibiConnection($config);
  }

  /**
   * Method for preparation of a random password
   * @param int $length=6
   * @return string
   */
  public static function getRandPassword($length=6){
      $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      $size = strlen( $chars );
      $str='';
      for( $i = 0; $i < $length; $i++ ) {
        $str .= $chars[ rand( 0, $size - 1 ) ];
      }
      return $str;
  }

  /**
   * Method for check, if we are connected to database
   * @return bool
   */
  public function isConnected(){
    return $this->connection->isConnected();
  }

  /**
   * Method for creating of the database structure from mysql.sql
   * @throws \DibiException
   * @throws \RuntimeException
   */
  public function createDatabaseStructure(){
    $this->connection->loadFile(__DIR__.'/../data/mysql.sql');
  }

  /**
   * @param string $username
   * @param string $password
   * @param string $databaseName
   */
  public function createDatabase($username, $password, $databaseName){
    $this->connection->nativeQuery('GRANT ALL PRIVILEGES ON *.* TO "'.$username.'"@"%" IDENTIFIED BY "'.$password.'" WITH GRANT OPTION');
    $this->connection->nativeQuery('CREATE DATABASE '.$databaseName);
    $this->connection->nativeQuery("GRANT ALL PRIVILEGES ON ".$databaseName.".* TO '".$username."'@'%' IDENTIFIED BY '".$password."' WITH GRANT OPTION");
  }

}