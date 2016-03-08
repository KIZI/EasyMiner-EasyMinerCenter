<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Facades\DatabasesFacade;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\AttributesRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\MetasourcesRepository;
use Nette\Utils\Strings;

class OLD_MetasourcesFacade {//XXX dodelat!!!
  /** @var MetasourcesRepository $metasourcesRepository */
  private $metasourcesRepository;
  /** @var  AttributesRepository $attributesRepository */
  private $attributesRepository;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;
  /** @var array $databasesConfig - konfigurace jednotlivých připojení k DB */
  private $databasesConfig;

  /**
   * @param array $databasesConfig
   * @param MetasourcesRepository $metasourcesRepository
   * @param AttributesRepository $attributesRepository
   * @param DatabasesFacade $databasesFacade
   */
  public function __construct($databasesConfig, MetasourcesRepository $metasourcesRepository, AttributesRepository $attributesRepository, DatabasesFacade $databasesFacade) {
    $this->metasourcesRepository = $metasourcesRepository;
    $this->attributesRepository = $attributesRepository;
    $this->databasesConfig = $databasesConfig;
    $this->databasesFacade = $databasesFacade;
  }

  /**
   * @param int $id
   * @return Metasource
   */
  public function findMetasource($id) {
    return $this->metasourcesRepository->find($id);
  }

  /**
   * @param Metasource $metasource
   * @return bool
   */
  public function saveMetasource(Metasource &$metasource) {
    $result = $this->metasourcesRepository->persist($metasource);
    return $result;
  }


  /**
   * @param Metasource|int $metasource
   * @return int
   */
  public function deleteMetasource($metasource){
    if (!($metasource instanceof Metasource)){
      $metasource=$this->findMetasource($metasource);
    }
    return $this->metasourcesRepository->delete($metasource);
  }

  /**
   * Funkce vracející heslo k DB na základě údajů klienta
   * @param User $user
   * @return string
   */
  private function getUserDbPassword(User $user){
    return Strings::substring($user->getDbPassword(),2,3).Strings::substring(sha1($user->userId.$user->getDbPassword()),4,5);
  }

  /**
   * Funkce vracející admin přístupy k DB daného typu
   * @param string $dbType
   * @return DbConnection
   */
  private function getAdminDbConnection($dbType){
    $dbConnection=new DbConnection();
    $databaseConfig=$this->databasesConfig[$dbType];
    $dbConnection->type=$dbType;
    $dbConnection->dbUsername=$databaseConfig['username'];
    $dbConnection->dbPassword=$databaseConfig['password'];
    $dbConnection->dbServer=$databaseConfig['server'];
    if (!empty($databaseConfig['port'])){
      $dbConnection->dbPort=$databaseConfig['port'];
    }
    return $dbConnection;
  }


  /**
   * @param Attribute $attribute
   */
  public function saveAttribute(Attribute $attribute){
    $this->attributesRepository->persist($attribute);
  }

  /**
   * @param $id
   * @return Attribute
   * @throws \Exception
   */
  public function findAttribute($id){
    return $this->attributesRepository->find($id);
  }

  public function createMetasourcesTables(Metasource $metasource){
    $datasource=$metasource->miner->datasource;

    $this->databasesFacade->openDatabase($datasource->getDbConnection(),$metasource->getDbConnection());
    $attributesTable=$metasource->attributesTable;
    $i=1;
    while($this->databasesFacade->checkTableExists($attributesTable.DatabasesFacade::SECOND_DB)){
      $attributesTable=$metasource->attributesTable.$i;
      $i++;
    }
    $this->databasesFacade->createTable($attributesTable,array(),DatabasesFacade::SECOND_DB);
    //nakopírování hodnot ID
    $values=$this->databasesFacade->getColumnValuesWithId($metasource->attributesTable,'id',DatabasesFacade::SECOND_DB);
    if (!empty($values)){
      foreach ($values as $value){
        $this->databasesFacade->insertRow($metasource->attributesTable,array('id'=>$value));
      }
    }
  }
  //TODO skutečné založení dalších příslušných tabulek! (pro pravidla atd.)
}