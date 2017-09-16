<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class DatasourceColumn - entity representing the mapping of the concrete data column
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property int|null $datasourceColumnId=null
 * @property Datasource $datasource m:hasOne
 * @property int|null $dbDatasourceFieldId = null
 * @property string $name
 * @property string $type m:Enum(self::TYPE_*)
 * @property int|null $strLen = null
 * @property bool $active
 * @property Format|null $format m:hasOne
 * @property int $uniqueValuesCount = 0
 */
class DatasourceColumn extends Entity{
  const TYPE_STRING='string';
  const TYPE_INTEGER='int';
  const TYPE_FLOAT='float';
  //TODO pročistit seznam datových typů
  const TYPE_NOMINAL='nominal';
  const TYPE_NUMERIC='numeric';

  /**
   * Method for checking if the given DatasourceColumn is numeric
   * @return bool
   */
  public function isNumericType(){
    return ($this->type==self::TYPE_FLOAT || $this->type==self::TYPE_INTEGER || $this->type==self::TYPE_NUMERIC);
  }

  /**
   * Method returning an array with basic data properties
   * @return array
   */
  public function getDataArr() {
    return [
      'id'=>$this->datasourceColumnId,
      'name'=>$this->name,
      'type'=>$this->type,
      'active'=>$this->active?1:0,
      'format'=>$this->getRowData()['format_id']
    ];
  }
} 