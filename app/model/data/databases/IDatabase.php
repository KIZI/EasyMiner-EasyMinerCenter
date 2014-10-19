<?php

namespace App\Model\Data\Databases;
use App\Model\Data\Entities\DbColumn;
use App\Model\Data\Entities\DbColumnValuesStatistic;
use App\Model\Data\Entities\DbConnection;

/**
 * Interface IDatabase - rozhraní definující funkce pro práci s různými datovými zdroji (pro zajištění nezávislosti na jedné DB
 * @package App\Model\Data\Databases
 */
interface IDatabase {

  /**
   * @param DbConnection $dbConnection
   * @return IDatabase
   */
  public static function getInstance(DbConnection $dbConnection);

  /**
   * Funkce pro vytvoření uživatele a databáze na základě zadaných údajů
   * @param DbConnection $dbConnection
   * @return bool
   */
  public function createUserDatabase(DbConnection $dbConnection);

  #region funkce pro práci s tabulkami
  /**
   * @param string $tableName
   * @param DbColumn[] $columns - pole s definicemi sloupců (
   * @return bool
   */
  public function createTable($tableName,$columns);

  /**
   * Funkce pro kontrolu, zda existuje tabulka se zadaným názvem
   * @param string $tableName
   * @return bool
   */
  public function tableExists($tableName);

  /**
   * @param string $tableName
   * @return bool
   */
  public function selectTable($tableName);

  /**
   * @return bool
   */
  public function dropTable();

  /**
   * @return bool
   */
  public function truncateTable();
  #endregion

  #region funkce pro práci s daty v DB
  /**
   * @param int $id
   * @return array
   */
  public function getRow($id);

  /**
   * @param string $where
   * @param array|null $whereParams
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getRows($where='',$whereParams=null,$limitStart=0,$limitCount=0);

  /**
   * @param string $column
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getColumnValues($column='',$limitStart=0,$limitCount=0);

  /**
   * @param array $data
   * @return bool
   */
  public function insertRow(array $data);

  /**
   * @param array $data
   * @param $id
   * @return bool
   */
  public function updateRow(array $data,$id);

  /**
   * @param $id
   * @return bool
   */
  public function deleteRow($id);
  #endregion


  /**
   * @param string $name
   * @return DbColumn
   */
  public function getColumn($name);

  /**
   * @param string $name
   * @return DbColumnValuesStatistic
   */
  public function getColumnValuesStatistic($name);

  /**
   * @return DbColumn[]
   */
  public function getColumns();
} 