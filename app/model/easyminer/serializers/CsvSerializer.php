<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\Data\Databases\IDatabase;
use EasyMinerCenter\Model\Data\Entities\DbDatasource;

/**
 * Class CsvSerializer - třída pro serializaci dat do CSV
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 */
class CsvSerializer{

  /**
   * Funkce pro in-memory sestavení CSV souboru z vybraných řádků v databázi
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
      //nechceme žádné řádky, ale pro validní výstup se zeptáme alespoň na jeden řádek kvůli info o názvech sloupcích
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
      $valuesRows=$dbRows->getValuesRows();
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