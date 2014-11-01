<?php

namespace App\Model\Data\Databases;

use App\Model\Data\Entities\DbColumn;
use App\Model\Data\Entities\DbColumnValuesStatistic;
use App\Model\Data\Entities\DbConnection;
use Nette\Application\ApplicationException;
use Nette\Utils\Strings;
use \PDO;

/**
 * Class MySqlDatabase - třída pro práci s MySQL
 * @package App\Model\Data\Databases
 * @property \PDO $db
 * @property string $tableName
 */
class MySQLDatabase implements IDatabase{
  private $db;
  private $tableName;

  #region connection
  private function __construct(DbConnection $dbConnection){
    $connectionString='mysql:host='.$dbConnection->dbServer.';'.(!empty($dbConnection->port)?'port='.$dbConnection->port.';':'').(!empty($dbConnection->dbName)?'dbname='.$dbConnection->dbName.';':'').'charset=utf8';
    $this->db=new PDO($connectionString,$dbConnection->dbUsername,$dbConnection->dbPassword);
  }
  /**
   * @param DbConnection $dbConnection
   * @return MySqlDatabase
   */
  public static function getInstance(DbConnection $dbConnection) {
    return new MySqlDatabase($dbConnection);
  }

  public function selectTable($tableName){
    $this->tableName=$tableName;
  }
  #endregion


  /**
   * @param string $tableName
   * @param DbColumn[] $columns - pole s definicemi sloupců (
   * @throws \Exception
   * @return bool
   */
  public function createTable($tableName, $columns) {
    $this->tableName=$tableName;

    $sql='CREATE TABLE `'.$tableName.'` (`id` int(11) NOT NULL AUTO_INCREMENT ';
    if (count($columns)){
      foreach ($columns as $column){
        if ($column->dataType==DbColumn::TYPE_STRING){
          $sql.=', `'.$column->name.'` varchar('.$column->strLength.') NOT NULL';
        }elseif($column->dataType==DbColumn::TYPE_INTEGER){
          $sql.=', `'.$column->name.'` int(11) NOT NULL';
        }elseif($column->dataType==DbColumn::TYPE_FLOAT){
          $sql.=', `'.$column->name.'` float NOT NULL';
        }
      }
    }else{
      throw new ApplicationException('No columns specified!');
    }
    $sql.=', PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
    $query=$this->db->prepare($sql);
    return $query->execute();
  }

  /**
   * @return bool
   */
  public function dropTable() {
    $query=$this->db->prepare('DROP TABLE `'.$this->tableName.'`;');
    $this->tableName='';
    return $query->execute();
  }

  /**
   * @return bool
   */
  public function truncateTable() {
    $query=$this->db->prepare('DROP TABLE `'.$this->tableName.'`;');
    return $query->execute();
  }

  /**
   * @param int $id
   * @return array
   */
  public function getRow($id) {
    $query=$this->db->prepare('SELECT * FROM `'.$this->tableName.'` WHERE id=:id LIMIT 1;');
    $query->execute(array(':id'=>$id));
    return $query->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * @param string $where
   * @param array|null $whereParams
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getRows($where = '',$whereParams=null, $limitStart = 0, $limitCount = 0) {
    $sql='SELECT * FROM `'.$this->tableName.'`';
    if ($where!=''){
      $sql.=' WHERE '.$where;
    }
    if ($limitStart!=0){
      $sql.=' LIMIT '.$limitStart;
    }
    if ($limitCount!=0){
      $sql.=' OFFSET '.$limitCount;
    }
    $query=$this->db->prepare($sql);
    $query->execute($whereParams);
    return $query->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * @param array $data
   * @return bool
   */
  public function insertRow(array $data) {
    $paramsArr=array();
    $sqlColumns='';
    $sqlParams='';
    $columnId=0;
    foreach ($data as $column=>$value){
      $sqlColumns.=',`'.$column.'`';
      $sqlParams.=',:c'.$columnId;
      $paramsArr[':c'.$columnId]=$value;
      $columnId++;
    }
    $sqlColumns=Strings::substring($sqlColumns,1);
    $sqlParams=Strings::substring($sqlParams,1);
    $query=$this->db->prepare('INSERT INTO `'.$this->tableName.'` ('.$sqlColumns.')VALUES('.$sqlParams.')');
    return $query->execute($paramsArr);
  }

  /**
   * @param array $data
   * @param $id
   * @return bool
   */
  public function updateRow(array $data, $id) {
    $paramsArr=array(':id'=>$id);
    $sql='';
    $columnId=0;
    foreach ($data as $column=>$value){
      $sql.=',`'.$column.'`=:c'.$columnId;
      $paramsArr[':c'.$columnId]=$value;
      $columnId++;
    }
    $sql=Strings::substring($sql,1);
    $query=$this->db->prepare('UPDATE `'.$this->tableName.'` SET '.$sql.' WHERE id=:id LIMIT 1;');
    return $query->execute($paramsArr);
  }

  /**
   * @param $id
   * @return bool
   */
  public function deleteRow($id) {
    $query=$this->db->prepare('DELETE FROM `'.$this->tableName.'` WHERE id=:id LIMIT 1;');
    return $query->execute(array(':id'=>$id));
  }

  /**
   * Funkce pro kontrolu, zda existuje tabulka se zadaným názvem
   * @param string $tableName
   * @return bool
   */
  public function tableExists($tableName) {
    $result=$this->db->query("SHOW TABLES LIKE '" . $tableName . "'");
    if (!$result){return false;}
    return ($result->rowCount() > 0);
  }

  /**
   * @param string $column
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getColumnValues($column = '', $limitStart = 0, $limitCount = 0) {
    $query='SELECT `'.$column.'` FROM `'.$this->tableName.'` ORDER BY `'.$column.'`';
    if ($limitCount>0){
      $query.=' LIMIT '.intval($limitCount);
    }
    if ($limitStart>0){
      $query.=' OFFSET '.intval($limitStart);
    }
    $query=$this->db->prepare($query);
    $query->execute();
    $query->fetchAll(PDO::FETCH_COLUMN,0);
  }

  /**
   * @param string $name
   * @return DbColumn
   */
  public function getColumn($name) {
    $select=$this->db->query('SELECT `'.$name.'` FROM `'.$this->tableName.'`;');
    $pdoColumnMeta=$select->getColumnMeta(0);
    $dbColumn=new DbColumn();
    $dbColumn->name=$pdoColumnMeta['name'];
    //TODO typy
    //$dbColumn->dataType='';
    //$dbColumn->strLen='';
  }

  /**
   * @param string $name
   * @param bool $includeValues=true
   * @return DbColumnValuesStatistic
   */
  public function getColumnValuesStatistic($name,$includeValues=true){
    $result=new DbColumnValuesStatistic($this->getColumn($name));
    if ($result->dataType=DbColumn::TYPE_STRING){
      //u řetězce vracíme jen info o počtu řádků
      $select=$this->db->prepare('SELECT count('.$name.') as rowsCount from `'.$this->tableName.'`);');
      $select->execute();
      $selectResult=$select->fetch(PDO::FETCH_ASSOC);
      $result->rowsCount=$selectResult['rowsCount'];
    }else{
      //u čísel vracíme info o min, max a avg
      $select=$this->db->prepare('SELECT count('.$name.') as rowsCount,min('.$name.') as minValue,max('.$name.') as maxValue, avg('.$name.') as avgValue from `'.$this->tableName.'`);');
      $select->execute();
      $selectResult=$select->fetch(PDO::FETCH_ASSOC);
      $result->rowsCount=$selectResult['rowsCount'];
      $result->minValue=$selectResult['minValue'];
      $result->maxValue=$selectResult['maxValue'];
      $result->avgValue=$selectResult['avgValue'];
    }

    $selectValuesCount=$this->db->prepare('SELECT count(DISTINCT `'.$name.'`) as distinctValuesCount FROM `'.$this->tableName.'`;');
    $selectValuesCount->execute();
    $result->valuesCount=$selectValuesCount->fetchColumn(0);

    if ($includeValues){
      //načtení hodnot s četnostmi
      $query=$this->db->prepare('SELECT `'.$name.'` as hodnota,count(`'.$name.'`) as pocet FROM `'.$this->tableName.'` GROUP BY `'.$name.'` ORDER BY `'.$name.'` LIMIT 10000;');
      $query->execute();
      $valuesArr=array();
      while ($row=$query->fetchObject()){
        $hodnota=$row->hodnota;
        $valuesArr[$hodnota]=$row->pocet;
      }
      $result->valuesArr=$valuesArr;
    }

    return $result;
  }

  /**
   * Funkce pro vytvoření uživatele a databáze na základě zadaných údajů
   * @param DbConnection $dbConnection
   * @return bool
   */
  public function createUserDatabase(DbConnection $dbConnection) {
    $query1=$this->db->prepare('CREATE USER :username@"%" IDENTIFIED BY :password;');
    $result1=$query1->execute(array(':username'=>$dbConnection->dbUsername,':password'=>$dbConnection->dbPassword));
    $query2=$this->db->prepare("GRANT USAGE ON * . * TO :username@'%' IDENTIFIED BY :password WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;");
    $result2=$query2->execute(array(':username'=>$dbConnection->dbUsername,':password'=>$dbConnection->dbPassword));
    $query3=$this->db->prepare('CREATE DATABASE `'.$dbConnection->dbName.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_czech_ci;');
    $result3=$query3->execute();
    $query4=$this->db->prepare('GRANT ALL PRIVILEGES ON `'.$dbConnection->dbName.'`.* TO "'.$dbConnection->dbUsername.'"@"%";');
    $result4=$query4->execute();
    return ($result2 && $result3 && $result4);
  }

  /**
   * Funkce vracející přehled datových sloupců v DB tabulce
   * @return DbColumn[]
   */
  public function getColumns() {
    $query=$this->db->prepare('SHOW COLUMNS IN `'.$this->tableName.'`;');
    $query->execute();
    $columns=$query->fetchAll(PDO::FETCH_CLASS);
    $result=array();
    foreach ($columns as $column){
      $dbColumn=new DbColumn();
      $dbColumn->name=$column->Field;
      $queryStrLen=$this->db->prepare('SELECT MAX(CHAR_LENGTH(`'.$column->Field.'`)) AS strLen FROM `'.$this->tableName.'`;');
      $queryStrLen->execute();
      $dbColumn->strLength=$queryStrLen->fetchColumn(0);
      //TODO datový typ!!!
      $result[]=$dbColumn;
    }
    return $result;
  }

  /**
   * @param string $name
   * @return bool
   */
  public function deleteColumn($name){
    $query=$this->db->prepare('ALTER TABLE `'.$this->tableName.'` DROP `'.$name.'`;');
    return $query->execute();
  }

  /**
   * @param string $oldName
   * @param string $newName
   * @return bool
   */
  public function renameColumn($oldName, $newName) {
    $columnInfoQuery=$this->db->prepare('SHOW COLUMNS FROM `'.$this->tableName.'` LIKE :name ;');
    $columnInfoQuery->execute(array(':name'=>$oldName));
    $columnInfo=$columnInfoQuery->fetchObject();

    if (!$columnInfo){
      return false;
    }

    $sql= 'ALTER TABLE `'.$this->tableName.'` CHANGE `'.$oldName.'` `'.$newName.'` '.$columnInfo->Type;
    $params=array();
    if (@$columnInfo->Collation!=''){
      $sql.=' COLLATE '.$columnInfo->Collation;
    }
    if (Strings::upper(@$columnInfo->Null)=='YES'){
      $sql.=' NULL ';
    }else{
      $sql.=' NOT NULL ';
    }
    if (@$columnInfo->Default!=''){
      $sql.=' DEFAULT :default ';
      $params[':default']=$columnInfo->Default;
    }
    $sql.=' '.@$columnInfo->Extra;
    if (@$columnInfo->Comment!=''){
      $sql.=' COMMENT :comment ';
      $params[':comment']=$columnInfo->Comment;
    }
    $sql.=';';
    $renameQuery=$this->db->prepare($sql);
    return $renameQuery->execute($params);
  }

  /**
   * Funkce vracející počet řádků v tabulce
   * @return int
   */
  public function getRowsCount() {
    $query=$this->db->prepare('SELECT count(*) AS pocet FROM `'.$this->tableName.'`;');
    $query->execute();
    return $query->fetchColumn(0);
  }

  /**
   * @param string $column
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getColumnValuesWithId($column, $limitStart = 0, $limitCount = 0) {
    $query='SELECT id,`'.$column.'` AS rowData FROM `'.$this->tableName.'` ORDER BY id ';
    if ($limitCount>0){
      $query.=' LIMIT '.intval($limitCount);
    }
    if ($limitStart>0){
      $query.=' OFFSET '.intval($limitStart);
    }
    $query=$this->db->prepare($query);
    $query->execute();
    $result=array();
    while ($row=$query->fetch(PDO::FETCH_CLASS)){
      $result[$row->id]=$row->rowData;
    }
    return $result;
  }

  /**
   * @param DbColumn $dbColumn
   * @return bool
   */
  public function createColumn(DbColumn $dbColumn) {
    $sql= 'ALTER TABLE `'.$this->tableName.'` ADD `'.$dbColumn->name.' VARCHAR('.$dbColumn->strLength.')';//TODO další datové typy...
    $query=$this->db->prepare($sql);
    return $query->execute();
  }
}