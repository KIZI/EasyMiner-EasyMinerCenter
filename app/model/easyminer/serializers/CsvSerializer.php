<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\Data\Databases\IDatabase;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;

/**
 * Class CsvSerializer - class for serialization of data rows to CSV
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class CsvSerializer{

  /**
   * Method for in-memory building of CSV file from selected rows from database
   * @param IDatabase $database
   * @param DbDatasource $dbDatasource
   * @param int $offset=0
   * @param int|null $limit=null
   * @param string $delimiter=';'
   * @param string $enclosure='"'
   * @return string
   * @throws \Exception
   */
  public static function prepareCsvFromDatabase(IDatabase $database, DbDatasource $dbDatasource,$offset=0,$limit=null,$delimiter=';',$enclosure='"'){
    $fd = fopen('php://temp/maxmemory:10048576', 'w');
    if($fd === FALSE) {
      throw new \Exception('Failed to open temporary file');
    }

    if ($limit>0){
      $maxRows=min($dbDatasource->size,$offset+$limit);
    }else{
      $maxRows=$dbDatasource->size;
    }
    $firstRequest=true;
    if (!($offset<$maxRows)){
      //we do not want any rows, but for valid output, we have to ask at least for one row because of the info about data columns
      $dbRows=$database->getDbValuesRows($dbDatasource,0,1);
      fputcsv($fd, $dbRows->getFieldNames(), $delimiter, $enclosure);
      $firstRequest=false;
    }

    while ($offset<$maxRows){
      $dbRows=$database->getDbValuesRows($dbDatasource, $offset, min(1000,$maxRows-$offset));
      if ($firstRequest){
        fputcsv($fd, $dbRows->getFieldNames(), $delimiter, $enclosure);
        $firstRequest=false;
      }
      $valuesRows=$dbRows->getValuesRows(true);
      if (!empty($valuesRows)){
        foreach($valuesRows as $valuesRow){
          fputcsv($fd, $valuesRow, $delimiter, $enclosure);
          $offset++;
        }
      }
    }

    rewind($fd);
    $csv = stream_get_contents($fd);
    fclose($fd);
    return $csv;
  }

}