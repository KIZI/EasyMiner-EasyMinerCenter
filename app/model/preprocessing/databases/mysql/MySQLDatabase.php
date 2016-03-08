<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\MySQL;

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
 */
class MySQLDatabase implements IPreprocessing{

  const PP_TYPE=PpConnection::TYPE_MYSQL;
  const PP_TYPE_NAME=PpConnection::TYPE_MYSQL_NAME;

  /**
   * Funkce vracející seznam datových zdrojů v DB
   * @return PpDataset[]
   */
  public function getPpDatasets() {
    throw new NotSupportedException('MySQL does not support list of datasets!');
  }

  /**
   * Funkce vracející informace o konkrétním datovém zdroji
   * @param int|string $ppDatasetId
   * @return PpDataset
   */
  public function getPpDataset($ppDatasetId) {
    return new PpDataset($ppDatasetId, $ppDatasetId, null, PpConnection::TYPE_MYSQL, $this->getRowsCount($ppDatasetId));
  }

  /**
   * Funkce vracející počet řádků v tabulce
   * @param string $ppDatasetId
   * @return int
   */
  private function getRowsCount($ppDatasetId) {
    $query=$this->db->prepare('SELECT count(*) AS pocet FROM `'.$ppDatasetId.'`;');
    $query->execute();
    return $query->fetchColumn(0);
  }

  /**
   * Funkce vracející seznam sloupců v datovém zdroji
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
   * Konstruktor zajišťující připojení k databázi
   *
   * @param PpConnection $ppConnection
   * @param string $apiKey
   */
  public function __construct(PpConnection $ppConnection, $apiKey) {
    $this->db=new PDO($ppConnection->getPDOConnectionString(),$ppConnection->dbUsername,$ppConnection->dbPassword,array(PDO::MYSQL_ATTR_LOCAL_INFILE => true));
  }

  #region funkce vracející identifikační konstanty
  /**
   * Funkce vracející uživatelsky srozumitelný název databáze
   *
   * @return string
   */
  public static function getPpTypeName() {
    return self::PP_TYPE;
  }

  /**
   * Funkce vracející identifikaci daného typu databáze
   *
   * @return string
   */
  public static function getPpType() {
    return self::PP_TYPE_NAME;
  }
  #endregion
}