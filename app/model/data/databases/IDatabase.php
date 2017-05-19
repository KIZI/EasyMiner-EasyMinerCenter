<?php

namespace EasyMinerCenter\Model\Data\Databases;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;
use EasyMinerCenter\Model\Data\Entities\DbConnection;
use EasyMinerCenter\Model\Data\Entities\DbField;
use EasyMinerCenter\Model\Data\Entities\DbValue;
use EasyMinerCenter\Model\Data\Entities\DbValuesRows;

/**
 * Interface IDatabase - unified interface for work with different datasources (database drivers)
 * @package EasyMinerCenter\Model\Data\Databases
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
interface IDatabase {

  /**
   * Method returning list of remote datasources available
   * @return DbDatasource[]
   */
  public function getDbDatasources();

  /**
   * Method returning info about selected remote datasource
   * @param int|string $datasourceId
   * @return DbDatasource
   */
  public function getDbDatasource($datasourceId);

  /**
   * Method returning list of fields (columns) in remote datasource
   * @param DbDatasource $dbDatasource
   * @return DbField[]
   */
  public function getDbFields(DbDatasource $dbDatasource);

  /**
   * Method for deleting selected remote datasource
   * @param DbDatasource $dbDatasource
   */
  public function deleteDbDatasource(DbDatasource $dbDatasource);

  /**
   * IDatabase constructor, also providing connection to remote database/service
   * @param DbConnection $dbConnection
   * @param $apiKey
   * @return IDatabase
   */
  public function __construct(DbConnection $dbConnection, $apiKey);

  /**
   * Method returning user understandable name of this database
   * @return string
   */
  public static function getDbTypeName();

  /**
   * Method returning identification of this database type
   * @return string
   */
  public static function getDbType();

  /**
   * Method for renaming of a field
   * @param DbField $dbField
   * @param string $newName='' (if empty, if will be read from DbField)
   * @return bool
   */
  public function renameDbField(DbField $dbField, $newName='');

  /**
   * Method for unzipping of compressed data
   * @param string $data
   * @param string $compression
   * @return string
   */
  public function unzipData($data, $compression);

  /**
   * Method returning values from selected DbField
   * @param DbField $dbField
   * @param int $offset
   * @param int $limit
   * @return DbValue[]
   */
  public function getDbValues(DbField $dbField, $offset=0, $limit=1000);

  /**
   * Method returning rows from Datasource
   * @param DbDatasource $dbDatasource
   * @param int $offset=0
   * @param int $limit=1000
   * @param DbField[]|null $preloadedDbFields
   * @return DbValuesRows
   */
  public function getDbValuesRows(DbDatasource $dbDatasource, $offset=0, $limit=1000, &$preloadedDbFields=null);

  /**
   * Method for importing of existing CSV file to database
   * @param string $filename
   * @param string $name
   * @param string $encoding
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escapeCharacter
   * @param string $nullValue
   * @param string[] $dataTypes
   * @return DbDatasource
   */
  public function importCsvFile($filename, $name, $encoding='utf-8', $delimiter=',', $enclosure='"', $escapeCharacter='\\', $nullValue='', $dataTypes);
  
} 