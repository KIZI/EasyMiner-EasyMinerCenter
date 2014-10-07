<?php

namespace App\Model\Data\Databases;

use App\Model\Data\Entities\DbColumn;
use App\Model\EasyMiner\Entities\Datasource;
use Nette\Utils\Strings;
use \PDO;

/**
 * Class MySqlDatabase
 * @package App\Model\Data\Databases
 * @property \PDO $db
 * @property string $tableName
 */
class MySqlDatabase implements IDatabase{
  private $db;
  private $tableName;

  #region connection
  private function __construct(Datasource $dbConnection){
    $connectionString='mysql:host='.$dbConnection->dbServer.';'.(!empty($dbConnection->port)?'port='.$dbConnection->port.';':'').(!empty($dbConnection->database)?'dbname='.$dbConnection->database.';':'').'charset=utf8';
    $this->pdo=new PDO($connectionString,$dbConnection->dbUsername,$dbConnection->dbPassword);
  }
  /**
   * @param Datasource $datasource
   * @return MySqlDatabase
   */
  public static function getInstance(Datasource $datasource) {
    return new MySqlDatabase($datasource);
  }

  public function selectTable($tableName){
    $this->tableName=$tableName;
  }
  #endregion


  /**
   * @param string $tableName
   * @param DbColumn[] $columns - pole s definicemi sloupců (
   * @return bool
   */
  public function createTable($tableName, $columns) {
    // TODO: Implement createTable() method.
    $this->tableName=$tableName;
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

}