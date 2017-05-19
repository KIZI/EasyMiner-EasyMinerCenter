<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\MySQL;

use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\Preprocessing\Entities\PpTask;
use EasyMinerCenter\Model\Preprocessing\Entities\PpValue;
use Nette\NotImplementedException;
use Nette\NotSupportedException;
use Nette\Utils\Strings;
use \PDO;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Entities\PpAttribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpDataset;

/**
 * Class MySQLDatabase - třída zajišťující preprocessing v rámci MySQL databáze
 * @package EasyMinerCenter\Model\Preprocessing\Databases\MySQL
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MySQLDatabase implements IPreprocessing{

  const PP_TYPE=PpConnection::TYPE_MYSQL;
  const PP_TYPE_NAME=PpConnection::TYPE_MYSQL_NAME;

  /**
   * Method returning list of available datasets
   * @return PpDataset[]
   */
  public function getPpDatasets() {
    throw new NotSupportedException('MySQL does not support list of datasets!');
  }

  /**
   * Method returning info about one selected dataset
   * @param int|string $ppDatasetId
   * @return PpDataset
   */
  public function getPpDataset($ppDatasetId) {
    return new PpDataset($ppDatasetId, $ppDatasetId, null, PpConnection::TYPE_MYSQL, $this->getRowsCount($ppDatasetId));
  }

  /**
   * Method returning count of rows in a DB table
   * @param string $ppDatasetId
   * @return int
   */
  private function getRowsCount($ppDatasetId) {
    $query=$this->db->prepare('SELECT count(*) AS pocet FROM `'.$ppDatasetId.'`;');
    $query->execute();
    return $query->fetchColumn(0);
  }

  /**
   * Method returning list of attributes (data columns) in selected dataset
   * @param PpDataset $ppDataset
   * @return PpAttribute[]
   */
  public function getPpAttributes(PpDataset $ppDataset) {
    $query=$this->db->prepare('SHOW COLUMNS IN `'.$ppDataset->id.'`;');
    $query->execute();
    $columns=$query->fetchAll(PDO::FETCH_CLASS);
    $result=[];
    foreach ($columns as $column){
      $result[]=new PpAttribute($column->Field, $ppDataset->id, null, $column->Field, self::encodeDbDataType($column->Type), null);
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
      return PpAttribute::TYPE_NUMERIC;
    }elseif(Strings::contains($dataType,'float')||Strings::contains($dataType,'double')||Strings::contains($dataType,'real')){
      return PpAttribute::TYPE_NUMERIC;
    }else{
      return PpAttribute::TYPE_NOMINAL;
    }
  }

  /**
   * MySQLDatabase constructor, providing connection to remote database
   * @param PpConnection $ppConnection
   * @param string $apiKey
   */
  public function __construct(PpConnection $ppConnection, $apiKey) {
    $this->db=new PDO($ppConnection->getPDOConnectionString(),$ppConnection->dbUsername,$ppConnection->dbPassword,array(PDO::MYSQL_ATTR_LOCAL_INFILE => true));
  }

  /**
   * Method returning list of available preprocessing types
   * @return string[]
   */
  public static function getSupportedPreprocessingTypes() {
    return [Preprocessing::TYPE_EACHONE, Preprocessing::TYPE_EQUIDISTANT_INTERVALS, Preprocessing::TYPE_INTERVAL_ENUMERATION, Preprocessing::TYPE_NOMINAL_ENUMERATION];
  }

  #region methods returning identification constants
  /**
   * Method returning user understandable name of database
   * @return string
   */
  public static function getPpTypeName() {
    return self::PP_TYPE;
  }

  /**
   * Method returning identification of the database type
   * @return string
   */
  public static function getPpType() {
    return self::PP_TYPE_NAME;
  }
  #endregion methods returning identification constants

  /**
   * Method for creating (initializating) a dataset
   * @param PpDataset|null $ppDataset = null
   * @param PpTask|null $ppTask = null
   * @return PpTask|PpDataset - when the operation is finished, it returns PpDataset, others it returns PpTask
   */
  public function createPpDataset(PpDataset $ppDataset=null, PpTask $ppTask=null) {
    throw new NotImplementedException();
    // TODO: Implement createPpDataset() method.
  }

  /**
   * Method for deleting a dataset
   * @param PpDataset $ppDataset
   */
  public function deletePpDataset(PpDataset $ppDataset) {
    throw new NotImplementedException();
    // TODO: Implement deletePpDataset() method.
  }

  /**
   * Method for initialization of preprocessing of an attribute
   * @param Attribute[] $attributes
   * @param PpTask $ppTask = null
   * @return PpTask|PpAttribute[]
   */
  public function createAttributes(array $attributes=null, PpTask $ppTask=null){
    // TODO: Implement createAttributes() method.
    throw new NotImplementedException();
  }

  /**
   * Method returning values of one selected attribute
   * @param PpDataset $ppDataset
   * @param int $ppAttributeId
   * @param int $offset
   * @param int $limit
   * @return PpValue[]
   */
  public function getPpValues(PpDataset $ppDataset, $ppAttributeId, $offset=0, $limit=1000){
    // TODO: Implement getPpValues() method.
    throw new NotImplementedException();
  }

  /**
   * Method returning details of one attribute
   * @param PpDataset $ppDataset
   * @param string $ppAttributeId
   * @return PpAttribute
   */
  public function getPpAttribute(PpDataset $ppDataset, $ppAttributeId){
    // TODO: Implement getPpAttribute() method.
    throw new NotImplementedException();
  }
}