<?php
namespace App\Model\Data\Facades;
use App\Model\Data\Databases\IDatabase;
use App\Model\Data\Databases\MySQLDatabase;
use App\Model\Data\Entities\DbColumn;
use App\Model\Data\Entities\DbConnection;
use Nette\Application\ApplicationException;
use Nette\Utils\Strings;

/**
 * Class DatabasesFacade - model zajišťující práci s databázemi pro uživatelská data
 * @package App\Model\Data\Facades
 */
class DatabasesFacade {
  /** @var  IDatabase $database */
  private $database;
  /** @var string $table - jméno tabulky, se kterou se aktuálně pracuje */
  private $table='';

  const MYSQL_COLUMNS_MAX_COUNT=50;
  const DB_TYPE_MYSQL='mysql';
  const DB_TYPE_CASSANDRA='cassandra';
  const DB_CLASS_MYSQL='\App\Model\Data\Databases\MySQLDatabase';
  const DB_CLASS_CASSANDRA='\App\Model\Data\Databases\CassandraDatabase';


  /**
   * @param DbConnection $dbConnection
   * @return IDatabase
   * @throws ApplicationException
   */
  public function openDatabase(DbConnection $dbConnection){
    if ($dbConnection->type==self::DB_TYPE_MYSQL){
      /** @var IDatabase|string $class */
      $class=self::DB_CLASS_MYSQL;
    }elseif($dbConnection->type==self::DB_TYPE_CASSANDRA){
      /** @var IDatabase|string $class */
      $class=self::DB_TYPE_CASSANDRA;
    }else{
      throw new ApplicationException('Unknown database type!');
    }
    return $class::getInstance($dbConnection);
  }

  /**
   * @param DbColumn[] $dbColumns
   * @return string
   */
  public function prefferedDatabaseType($dbColumns){
    if (count($dbColumns)>self::MYSQL_COLUMNS_MAX_COUNT){
      return self::DB_TYPE_CASSANDRA;
    }else{
      return self::DB_TYPE_MYSQL;
    }
  }

  /**
   * Funkce pro vytvoření databázové tabulky na základě zadaného jména a informace o sloupcích
   * @param string $table
   * @param DbColumn[] $columns
   * @return bool
   */
  public function createTable($table,$columns){
    $this->checkDatabase();
    return $this->database->createTable($table,$columns);
  }

  /**
   * Funkce pro vložení řádku do databáze
   * @param string $table
   * @param array $data
   * @return bool
   */
  public function insertRow($table, array $data){
    $this->checkDatabase();
    if ($this->table!=$table){
      if ($this->database->selectTable($table)){
        $this->table=$table;
      }
    }
    try{
      return $this->database->insertRow($data);
    }catch (\Exception $e){
      return false;
    }
  }

  /**
   * Funkce pro připravení nového jména tabulky, kterou je možné vytvořit...
   * @param $tableName
   * @return string
   */
  public function prepareNewTableName($tableName){
    $tableName=Strings::webalize($tableName,'_',true);
    $tableName=Strings::replace($tableName,'__','_');
    $firstLetter=Strings::substring($tableName,1,1);
    if (!Strings::match($firstLetter,'/\w/')){
      $tableName='tbl_'.$tableName;
    }
    $result=$tableName;
    $counter=1;
    while($this->database->tableExists($result)){
      $counter++;
      $result=$tableName.'_'.$counter;
    }
    return $result;
  }

  /**
   * Funkce pro kontrolu, jestli je dostupná databáze, se kterou máme pracovat...
   * @return bool
   */
  private function checkDatabase(){
    return ($this->database instanceof \PDO);
  }
} 