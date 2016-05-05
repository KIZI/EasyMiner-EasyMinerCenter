<?php
namespace EasyMinerCenter\Model\Data\Databases\MySQL;

use EasyMinerCenter\Model\Data\Entities\DbConnection;

/**
 * Class MySQLDatabaseConstructor
 * @package EasyMinerCenter\Model\Data\Databases\MySQL
 * @author Stanislav Vojíř
 */
class MySQLDatabaseConstructor{
  /** @var \PDO $db */
  private $db;

  /**
   * Funkce pro kontrolu, jestli se dá k dané DB připojit pomocí PDO
   * @param DbConnection $dbConnection
   * @return bool
   */
  public static function isDatabaseAvailable(DbConnection $dbConnection){
    $testDbConnection=clone $dbConnection;
    if ($testDbConnection->type==DbConnection::TYPE_LIMITED){
      $testDbConnection->type=DbConnection::TYPE_MYSQL;
    }
    try{
      new \PDO($testDbConnection->getPDOConnectionString(),$testDbConnection->dbUsername,$testDbConnection->dbPassword);
      return true;
    }catch(\PDOException $e){
      return false;
    }
  }

  /**
   * MySQLDatabaseConstructor constructor.
   * @param \PDO|DbConnection $adminDbConnection
   */
  public function __construct($adminDbConnection){
    $dbConnection=clone $adminDbConnection;
    if ($dbConnection->type==DbConnection::TYPE_LIMITED){
      $dbConnection->type=DbConnection::TYPE_MYSQL;
    }
    if ($dbConnection instanceof DbConnection){
      $this->db=new \PDO($dbConnection->getPDOConnectionString(),$dbConnection->dbUsername,$dbConnection->dbPassword,array(\PDO::MYSQL_ATTR_LOCAL_INFILE => true));
    }elseif($adminDbConnection instanceof \PDO){
      $this->db=$adminDbConnection;
    }else{
      throw new \InvalidArgumentException();
    }
  }

  /**
   * Funkce pro vytvoření uživatele a databáze na základě zadaných údajů
   * @param DbConnection $dbConnection
   * @return bool
   */
  public function createUserDatabase(DbConnection $dbConnection) {
    $query1=$this->db->prepare('CREATE USER :username@"%" IDENTIFIED BY :password;');
    $result1=$query1->execute([':username'=>$dbConnection->dbUsername,':password'=>$dbConnection->dbPassword]);
    $query2=$this->db->prepare('GRANT USAGE ON *.* TO :username@\'%\' IDENTIFIED BY :password WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;');
    $result2=$query2->execute([':username'=>$dbConnection->dbUsername,':password'=>$dbConnection->dbPassword]);
    $query3=$this->db->prepare('CREATE DATABASE `'.$dbConnection->dbName.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_czech_ci;');
    $result3=$query3->execute();
    $query4=$this->db->prepare('GRANT ALL PRIVILEGES ON `'.$dbConnection->dbName.'`.* TO :username@"%";');
    $result4=$query4->execute([':username'=>$dbConnection->dbUsername]);
    //TODO tady by měla být kontrola, jestli máme povolený file přístup...
    $query5=$this->db->prepare('GRANT FILE ON *.* TO :username@\'%\' IDENTIFIED BY :password;');
    $result5=$query5->execute([':username'=>$dbConnection->dbUsername,':password'=>$dbConnection->dbPassword]);

    return ($result1 && $result3 && $result4);
  }

}