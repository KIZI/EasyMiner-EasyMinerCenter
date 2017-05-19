<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpAttribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpDataset;
use EasyMinerCenter\Model\Preprocessing\Entities\PpTask;
use EasyMinerCenter\Model\Preprocessing\Entities\PpValue;

/**
 * Interface IDatabase - unified interface for work with different datasources with preprocessed data
 * @package EasyMinerCenter\Model\Data\Databases
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
interface IPreprocessing {

  /**
   * Method returning list of available datasets
   * @return PpDataset[]
   */
  public function getPpDatasets();

  /**
   * Method returning info about one selected dataset
   * @param int|string $ppDatasetId
   * @return PpDataset
   */
  public function getPpDataset($ppDatasetId);

  /**
   * Method for creating (initializating) a dataset
   * @param PpDataset|null $ppDataset = null
   * @param PpTask|null $ppTask = null
   * @return PpTask|PpDataset - when the operation is finished, it returns PpDataset, others it returns PpTask
   */
  public function createPpDataset(PpDataset $ppDataset=null, PpTask $ppTask=null);

  /**
   * Method for deleting a dataset
   * @param PpDataset $ppDataset
   */
  public function deletePpDataset(PpDataset $ppDataset);

  /**
   * Method returning list of attributes (data columns) in selected dataset
   * @param PpDataset $ppDataset
   * @return PpAttribute[]
   */
  public function getPpAttributes(PpDataset $ppDataset);

  /**
   * Method returning details of one attribute
   * @param PpDataset $ppDataset
   * @param string $ppAttributeId
   * @return PpAttribute
   */
  public function getPpAttribute(PpDataset $ppDataset, $ppAttributeId);

  /**
   * Method for initialization of preprocessing of an attribute
   * @param Attribute[] $attributes
   * @param PpTask $ppTask = null
   * @return PpTask|PpAttribute[]
   */
  public function createAttributes(array $attributes = null, PpTask $ppTask = null);

  /**
   * Method returning values of one selected attribute
   * @param PpDataset $ppDataset
   * @param int $ppAttributeId
   * @param int $offset
   * @param int $limit
   * @return PpValue[]
   */
  public function getPpValues(PpDataset $ppDataset, $ppAttributeId, $offset=0, $limit=1000);

  /**
   * IPreprocessing constructor, providing connection to remote database
   * @param PpConnection $ppConnection
   * @param $apiKey
   * @return IPreprocessing
   */
  public function __construct(PpConnection $ppConnection, $apiKey);


  /**
   * Method returning user understandable name of database
   * @return string
   */
  public static function getPpTypeName();

  /**
   * Method returning identification of the database type
   * @return string
   */
  public static function getPpType();

  /**
   * Method returning list of available preprocessing types
   * @return string[]
   */
  public static function getSupportedPreprocessingTypes();

} 