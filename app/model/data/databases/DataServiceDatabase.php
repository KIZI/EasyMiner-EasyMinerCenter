<?php

namespace EasyMinerCenter\Model\Data\Databases;

use EasyMinerCenter\Model\Data\Entities\DbColumn;
use EasyMinerCenter\Model\Data\Entities\DbColumnValuesStatistic;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;

/**
 * Class DataServiceDatabase - třída zajišťující přístup k databázím dostupným prostřednictvím služby EasyMiner-Data
 *
*@package EasyMinerCenter\Model\Data\Databases
 */
/*abstract*/ class DataServiceDatabase implements IDatabase {

  /**
   * Funkce vracející seznam datových zdrojů v DB
   *
   * @return DbDatasource[]
   */
  public function getDbDatasources() {
    // TODO: Implement getDbDatasources() method.
  }

  //FIXME.............................

  /**
   * Funkce pro vytvoření instance připojení k DB
   * @param DbConnection $dbConnection
   * @param string $apiKey
   * @return IDatabase
   */
  public static function getInstance(DbConnection $dbConnection, $apiKey) {
    // TODO: Implement getInstance() method.
  }

  /**
   * Funkce pro vytvoření uživatele a databáze na základě zadaných údajů
   *
   * @param DbConnection $dbConnection
   * @return bool
   */
  public function createUserDatabase(DbConnection $dbConnection) {
    // TODO: Implement createUserDatabase() method.
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
   *
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
  public function getRows($where='', $whereParams=null, $limitStart=0, $limitCount=0) {
    // TODO: Implement getRows() method.
  }

  /**
   * @param string $column
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getColumnValues($column, $limitStart=0, $limitCount=0) {
    // TODO: Implement getColumnValues() method.
  }

  /**
   * @param string $column
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getColumnValuesWithId($column, $limitStart=0, $limitCount=0) {
    // TODO: Implement getColumnValuesWithId() method.
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
   * @param bool $includeValues = true
   * @return DbColumnValuesStatistic
   */
  public function getColumnValuesStatistic($name, $includeValues=true) {
    // TODO: Implement getColumnValuesStatistic() method.
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
   *
   * @return int
   */
  public function getRowsCount() {
    // TODO: Implement getRowsCount() method.
  }

  /**
   * Funkce pro přímý import dat z CSV souboru
   *
   * @param string $csvFileName
   * @param string[] $columnsNames
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string|null $nullValue
   * @param int $offsetRows =0
   * @return bool
   */
  public function importCsvFile($csvFileName, $columnsNames, $delimiter, $enclosure, $escapeCharacter, $nullValue=null, $offsetRows=0) {
    // TODO: Implement importCsvFile() method.
}}