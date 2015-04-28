<?php
namespace App\Model\Data\Databases;

use App\Model\Data\Entities\DbColumn;
use App\Model\Data\Entities\DbColumnValuesStatistic;
use App\Model\Data\Entities\DbConnection;

/**
 * Class CassandraDatabase - třída pro práci s DB cassandra
 * @package App\Model\Data\Databases
 * @property \PDO $db
 * @property string $tableName
 */
class CassandraDatabase implements IDatabase{
  private $db;
  private $tableName;

  /**
   * @param DbConnection $dbConnection
   * @return IDatabase
   */
  public static function getInstance(DbConnection $dbConnection) {
    // TODO: Implement getInstance() method.
  }

  /**
   * Funkce pro vytvoření uživatele a databáze na základě zadaných údajů
   * @param DbConnection $dbConnection
   * @return bool
   */
  public function createUserDatabase(DbConnection $dbConnection) {
    $queryUser=$this->db->prepare('CREATE USER IF NOT EXISTS :username WITH PASSWORD :password NOSUPERUSER;');
    $queryUser->execute(array(':username'=>$dbConnection->dbUsername,':password'=>$dbConnection->dbPassword));
    $queryDatabase=$this->db->prepare('CREATE KEYSPACE :database {\'class\':\'SimpleStrategy\',\'replication_factor\':1};');
    if ($queryDatabase->execute(array(':database'=>$dbConnection->dbName))){
      $queryPermissions=$this->db->prepare('GRANT ALL PERMISSIONS ON KEYSPACE '.$dbConnection->dbName.' TO '.$dbConnection->dbUsername.';');
      return $queryPermissions->execute();
    }
  }

  /**
   * @param string $tableName
   * @param DbColumn[] $columns - pole s definicemi sloupců (
   * @return bool
   */
  public function createTable($tableName, $columns) {
    // TODO: Implement createTable() method.
  }

  /**
   * Funkce pro kontrolu, zda existuje tabulka se zadaným názvem
   * @param string $tableName
   * @return bool
   */
  public function tableExists($tableName) {
    // TODO: Implement tableExists() method.
  }

  /**
   * @param string $tableName
   * @return bool
   */
  public function selectTable($tableName) {
    // TODO: Implement selectTable() method.
  }

  /**
   * @return bool
   */
  public function dropTable() {
    // TODO: Implement dropTable() method.
  }

  /**
   * @return bool
   */
  public function truncateTable() {
    // TODO: Implement truncateTable() method.
  }

  /**
   * @param int $id
   * @return array
   */
  public function getRow($id) {
    // TODO: Implement getRow() method.
  }

  /**
   * @param string $where
   * @param array|null $whereParams
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getRows($where = '', $whereParams = null, $limitStart = 0, $limitCount = 0) {
    // TODO: Implement getRows() method.
  }

  /**
   * @param string $column
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getColumnValues($column = '', $limitStart = 0, $limitCount = 0) {
    // TODO: Implement getColumnValues() method.
  }

  /**
   * @param array $data
   * @return bool
   */
  public function insertRow(array $data) {
    // TODO: Implement insertRow() method.
  }

  /**
   * @param array $data
   * @param $id
   * @return bool
   */
  public function updateRow(array $data, $id) {
    // TODO: Implement updateRow() method.
  }

  /**
   * @param $id
   * @return bool
   */
  public function deleteRow($id) {
    // TODO: Implement deleteRow() method.
  }

  /**
   * @param string $name
   * @return DbColumn
   */
  public function getColumn($name) {
    // TODO: Implement getColumn() method.
  }

  /**
   * @param string $name
   * @return DbColumnValuesStatistic
   */
  public function getColumnValuesStatistic($name, $includeValues=true) {
    // TODO: Implement getColumnValuesStatistic() method.
  }

  /**
   * @param string $column
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getColumnValuesWithId($column, $limitStart = 0, $limitCount = 0) {
    // TODO: Implement getColumnValuesWithId() method.
  }

  /**
   * @return DbColumn[]
   */
  public function getColumns() {
    // TODO: Implement getColumns() method.
  }

  /**
   * @param string $name
   * @return bool
   */
  public function deleteColumn($name) {
    // TODO: Implement deleteColumn() method.
  }

  /**
   * @param string $oldName
   * @param string $newName
   * @return bool
   */
  public function renameColumn($oldName, $newName) {
    // TODO: Implement renameColumn() method.
  }

  /**
   * @param DbColumn $dbColumn
   * @return bool
   */
  public function createColumn(DbColumn $dbColumn) {
    // TODO: Implement createColumn() method.
  }

  /**
   * Funkce vracející počet řádků v tabulce
   * @return int
   */
  public function getRowsCount() {
    // TODO: Implement getRowsCount() method.
  }
}