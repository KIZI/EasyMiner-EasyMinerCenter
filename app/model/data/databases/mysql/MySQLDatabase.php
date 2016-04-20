<?php

namespace EasyMinerCenter\Model\Data\Databases\MySQL;

use EasyMinerCenter\Model\Data\Databases\IDatabase;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\Data\Entities\DbField;
use Nette\NotImplementedException;
use Nette\NotSupportedException;
use Nette\Utils\Strings;
use \PDO;

/**
 * Class MySqlDatabase - třída pro práci s MySQL
 * @package EasyMinerCenter\Model\Data\Databases
 * @property \PDO $db
 * @property string $tableName
 */
class MySQLDatabase implements IDatabase{

  const DB_TYPE=DbConnection::TYPE_MYSQL;
  const DB_TYPE_NAME=DbConnection::TYPE_MYSQL_NAME;

  private $pdo;
  private $tableName;

  #region původní funkce //TODO předělat...

  public function selectTable($tableName){
    $this->tableName=$tableName;
  }


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
          $sql.=', `'.$column->name.'` varchar('.$column->strLength.') NULL';
        }elseif($column->dataType==DbColumn::TYPE_INTEGER){
          $sql.=', `'.$column->name.'` int(11) NULL';
        }elseif($column->dataType==DbColumn::TYPE_FLOAT){
          $sql.=', `'.$column->name.'` float NULL';
        }
      }
    }
    $sql.=', PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
    $query=$this->db->prepare($sql);
    $result=$query->execute();
    return $result;
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
    $query=$this->db->prepare('TRUNCATE TABLE `'.$this->tableName.'`;');
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
    if ($limitCount!=0){
      $sql.=' LIMIT '.$limitCount;
    }
    if ($limitStart!=0){
      $sql.=' OFFSET '.$limitStart;
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
      $paramsArr[':c'.$columnId]=($value!=''?$value:null);
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
   * @param bool $insertNotExisting = true
   * @return bool
   */
  public function updateRow(array $data, $id, $insertNotExisting=true) {
    $paramsArr=[':id'=>$id];
    $sql='';
    $columnsSql='';
    $paramsSql='';
    $i=0;
    foreach ($data as $columnName=>$value){
      $sql.=',`'.$columnName.'`=:c'.$i;
      $paramsArr[':c'.$i]=($value!=''?$value:null);
      $columnsSql.=', `'.$columnName.'`';
      $columnsUpdateSql=', `'.$columnName.'`=VALUES(`'.$columnName.'`)';
      $paramsSql.=', :c'.$i;
      $i++;
    }
    $sql=Strings::substring($sql,1);
    if ($insertNotExisting){
      $query=$this->db->prepare('INSERT INTO `'.$this->tableName.'` (`id`'.$columnsSql.')VALUES(:id'.$paramsSql.') ON DUPLICATE KEY UPDATE '.trim($columnsUpdateSql,','));
      return $query->execute($paramsArr);
    }else{
      $query=$this->db->prepare('UPDATE `'.$this->tableName.'` SET '.$sql.' WHERE id=:id LIMIT 1;');
      return $query->execute($paramsArr) && ($query->rowCount()>0);
    }
  }


  /**
   * @param array $dataArr
   * @param $insertNotExisting=true
   * @return bool
   */
  public function updateMultiRows(array $dataArr, $insertNotExisting=true) {
    $columnsArr=[];
    $columnsSql='';
    $paramsSql='';
    $updateSql='';
    $i=0;
    foreach($dataArr as $id=>$data){
      foreach($data as $columnName=>$value){
        $columnsArr[]=$columnName;
        $columnsSql.=', `'.$columnName.'`';
        $columnsUpdateSql=', `'.$columnName.'`=VALUES(`'.$columnName.'`)';
        $paramsSql.=', :c'.$i;
        $updateSql.=', `'.$columnName.'`=:c'.$i;
        $i++;
      }
      break;
    }

    if ($insertNotExisting){
      $this->db->beginTransaction();
      $valuesSql='';
      $rowsCount=0;
      foreach($dataArr as $id=>$data){
        $columnsDataSql='';
        $rowsCount++;
        foreach($columnsArr as $columnName){
          $columnsDataSql.=', '.($data[$columnName]!=''?$this->db->quote($data[$columnName]):'NULL');
        }
        $valuesSql.=', ('.$this->db->quote($id).$columnsDataSql.')';
        if ($rowsCount>1000){
          $sql='INSERT INTO `'.$this->tableName.'` (`id`'.$columnsSql.') VALUES '.ltrim($valuesSql,',').' ON DUPLICATE KEY UPDATE '.trim($columnsUpdateSql,',');
          $this->db->query($sql);
          $rowsCount=0;
          $valuesSql='';
        }
      }
      if (!empty($valuesSql)){
        $sql='INSERT INTO `'.$this->tableName.'` (`id`'.$columnsSql.') VALUES '.ltrim($valuesSql,',').' ON DUPLICATE KEY UPDATE '.trim($columnsUpdateSql,',');
        $this->db->query($sql);
      }
      $result=$this->db->commit();
      return $result;
    }else {
      $sql = 'UPDATE `' . $this->tableName . '` SET ' . trim($updateSql, ',') . ' WHERE id=:id';
      $this->db->beginTransaction();
      $query = $this->db->prepare($sql);

      foreach ($dataArr as $id => $data) {
        $insertArr = [':id' => $id];
        foreach ($columnsArr as $columnId => $columnName) {
          $insertArr[':c' . $columnId] = (@$data[$columnName]!=''?$data[$columnName]:null);
        }
        $query->execute($insertArr);
      }
      return $this->db->commit();
    }
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
   * @throws \Exception
   */
  public function getColumn($name) {
    $columns=$this->getColumns();
    if (!empty($columns)){
      foreach($columns as $column){
        if ($column->name==$name){
          return $column;
        }
      }
    }
    throw new \Exception('Requested column not found! ('.$name.')');
  }

  /**
   * @param string $name
   * @param bool $includeValues=true
   * @return DbColumnValuesStatistic
   */
  public function getColumnValuesStatistic($name,$includeValues=true){
    $result=new DbColumnValuesStatistic($this->getColumn($name));
    if ($result->dataType==DbColumn::TYPE_STRING){
      //u řetězce vracíme jen info o počtu řádků
      $select=$this->db->prepare('SELECT count('.$name.') as `rowsCount` from `'.$this->tableName.'`);');
      $select->execute();
      $selectResult=$select->fetch(PDO::FETCH_ASSOC);
      $result->rowsCount=$selectResult['rowsCount'];
    }else{
      //u čísel vracíme info o min, max a avg
      $select=$this->db->prepare('SELECT count('.$name.') as `rowsCount`,min('.$name.') as `minValue`,max('.$name.') as `maxValue`, avg('.$name.') as `avgValue` from `'.$this->tableName.'`;');
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
      $query=$this->db->prepare('SELECT `'.$name.'` as hodnota,count(`'.$name.'`) as pocet FROM `'.$this->tableName.'` WHERE `'.$name.'` IS NOT NULL GROUP BY `'.$name.'` ORDER BY `'.$name.'` LIMIT 10000;');//TODO check this limit...
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
    $query5=$this->db->prepare("GRANT FILE ON * . * TO :username@'%' IDENTIFIED BY :password;");
    $result5=$query5->execute(array(':username'=>$dbConnection->dbUsername,':password'=>$dbConnection->dbPassword));
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
      $dbColumn->dataType=self::encodeDbDataType($column->Type);
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
    while ($row=$query->fetch(PDO::FETCH_OBJ)){
      $result[$row->id]=$row->rowData;
    }
    return $result;
  }

  /**
   * @param DbColumn $dbColumn
   * @return bool
   */
  public function createColumn(DbColumn $dbColumn) {
    $sql= 'ALTER TABLE `'.$this->tableName.'` ADD `'.$dbColumn->name.'` VARCHAR('.$dbColumn->strLength.')';//TODO další datové typy...
    $query=$this->db->prepare($sql);
    return $query->execute();
  }


  /**
   * Funkce pro přímý import dat z CSV souboru
   * @param string $csvFileName
   * @param string[] $columnsNames
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string|null $nullValue
   * @param int $offsetRows =0
   * @return bool
   */
  public function importCsvFile($csvFileName, $columnsNames, $delimiter, $enclosure, $escapeCharacter, $nullValue=null, $offsetRows = 0) {
    $sql='LOAD DATA LOCAL INFILE :fileName INTO TABLE '.$this->tableName;

    $sql.=' FIELDS TERMINATED BY :delimiter OPTIONALLY ENCLOSED BY :enclosure ESCAPED BY :escapeChar';

    if ($offsetRows>0){
      $sql.=' IGNORE '.$offsetRows.' LINES ';
    }

    #region prostý import
    /*
    $sql.=' (';
    foreach($columnsNames as $columnName){
      $sql.='`'.$columnName.'`,';
    }
    $sql=trim($sql,',').') ';
    */
    #endregion prostý import

    #region import s náhradou prázdných hodnot na null
    $sql.=' (';
    $sqlSets='';
    $counter=0;
    foreach($columnsNames as $columnName){
      $sql.='@v'.$counter.',';
      $sqlSets.='`'.$columnName.'`='.($nullValue!==null?'nullif(@v'.$counter.',\''.$nullValue.'\')':'@v'.$counter).',';
      $counter++;
    }
    $sql=trim($sql,',').') SET '.trim($sqlSets,',');
    #endregion import s náhradou prázdných hodnot na null

    $query=$this->db->prepare($sql);
    $result=$query->execute([':fileName'=>$csvFileName,':delimiter'=>$delimiter,':enclosure'=>$enclosure,':escapeChar'=>$escapeCharacter]);;

    return $result;
  }


  #endregion původní funkce

  /**
   * @param DbConnection $dbConnection
   * @param string|null $apiKey=null - aktuálně nepoužívaný atribut
   */
  public function __construct(DbConnection $dbConnection, $apiKey=null){
    $this->db=new PDO($dbConnection->getPDOConnectionString(),$dbConnection->dbUsername,$dbConnection->dbPassword,array(PDO::MYSQL_ATTR_LOCAL_INFILE => true));
  }

  /**
   * Funkce vracející seznam datových zdrojů v DB
   *
   * @return DbDatasource[]
   */
  public function getDbDatasources() {
    throw new NotSupportedException('MySQL does not support list of datasources!');
  }

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
   *
   * @param DbDatasource|string $dbDatasource
   * @return DbField[]
   */
  public function getDbFields(DbDatasource $dbDatasource) {
    $query=$this->db->prepare('SHOW COLUMNS IN `'.$dbDatasource->id.'`;');
    $query->execute();
    $columns=$query->fetchAll(PDO::FETCH_CLASS);
    $result=[];
    foreach ($columns as $column){
      $result[]=new DbField($column->Field, $dbDatasource->id, $column->Field, self::encodeDbDataType($column->Type), null);
    }
    return $result;
  }

  /**
   * @param string $dataType
   * @return string
   */
  private static function encodeDbDataType($dataType){
    $dataType=Strings::lower($dataType);
    if (Strings::contains($dataType,'int(')){
      return DbField::TYPE_NUMERIC;
    }elseif(Strings::contains($dataType,'float')||Strings::contains($dataType,'double')||Strings::contains($dataType,'real')){
      return DbField::TYPE_NUMERIC;
    }else{
      return DbField::TYPE_NOMINAL;
    }
  }

  /**
   * Funkce vracející informace o konkrétním datovém zdroji
   *
   * @param string $dbDatasourceId
   * @return DbDatasource
   */
  public function getDbDatasource($dbDatasourceId) {
    return new DbDatasource($dbDatasourceId, $dbDatasourceId, DbConnection::TYPE_MYSQL, $this->getRowsCount($dbDatasourceId));
  }

  /**
   * Funkce vracející počet řádků v tabulce
   * @param string $dbDatasourceId
   * @return int
   */
  private function getRowsCount($dbDatasourceId) {
    $query=$this->db->prepare('SELECT count(*) AS pocet FROM `'.$dbDatasourceId.'`;');
    $query->execute();
    return $query->fetchColumn(0);
  }

  /**
   * Funkce vracející uživatelsky srozumitelný název databáze
   *
   * @return string
   */
  public static function getDbTypeName() {
    return self::DB_TYPE_NAME;
  }

  /**
   * Funkce vracející identifikaci daného typu databáze
   *
   * @return string
   */
  public static function getDbType() {
    return self::DB_TYPE;
  }

  /**
   * Funkce pro přejmenování datového sloupce
   * @param DbField $dbField
   * @param string $newName ='' (pokud není název vyplněn, je převzat název z DbField
   * @return bool
   */
  public function renameDbField(DbField $dbField, $newName=''){
    // TODO: Implement renameDbField() method.
  }

  /**
   * Funkce pro rozbalení komprimovaných dat není podporována
   * @throws NotImplementedException
   */
  public function unzipData($data, $compression){
    throw  new NotImplementedException();
  }
}