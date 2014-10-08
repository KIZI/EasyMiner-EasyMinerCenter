<?php

namespace App\Model\Data\Databases;

use App\Model\Data\Entities\DbColumn;
use App\Model\Data\Entities\DbColumnValuesStatistic;
use App\Model\Data\Entities\DbConnection;
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
    $connectionString='mysql:host='.$dbConnection->dbServer.';'.(!empty($dbConnection->port)?'port='.$dbConnection->port.';':'').(!empty($dbConnection->database)?'dbname='.$dbConnection->database.';':'').'charset=utf8';
    $this->pdo=new PDO($connectionString,$dbConnection->dbUsername,$dbConnection->dbPassword);
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
      throw new \Exception('No columns specified!');
    }
    $sql.=' PRIMARY KEY (`id`)
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
    return ($this->db->query("SHOW TABLES LIKE '" . $tableName . "'")->rowCount() > 0);
  }

  /**
   * @param string $column
   * @param int $limitStart
   * @param int $limitCount
   * @return array[]
   */
  public function getColumnValues($column = '', $limitStart = 0, $limitCount = 0) {
    $query='SELECT `'.$column.'` FROM `'.$this->tableName.'`';
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
   * @return DbColumnValuesStatistic
   */
  public function getColumnValuesStatistic($name){
    $result=new DbColumnValuesStatistic($this->getColumn($name));
    if ($result->dataType=DbColumn::TYPE_STRING){
      //u řetězce vracíme jen info o počtu řádků
      $select=$this->db->query('SELECT count('.$name.') as rowsCount from `'.$this->tableName.'`);');
      $selectResult=$select->fetch(PDO::FETCH_ASSOC);
      $result->rowsCount=$selectResult['rowsCount'];
    }else{
      //u čísel vracíme info o min, max a avg
      $select=$this->db->query('SELECT count('.$name.') as rowsCount,min('.$name.') as minValue,max('.$name.') as maxValue, avg('.$name.') as avgValue from `'.$this->tableName.'`);');
      $selectResult=$select->fetch(PDO::FETCH_ASSOC);
      $result->rowsCount=$selectResult['rowsCount'];
      $result->minValue=$selectResult['minValue'];
      $result->maxValue=$selectResult['maxValue'];
      $result->avgValue=$selectResult['avgValue'];
    }
    return $result;
  }
}