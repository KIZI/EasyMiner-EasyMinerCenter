<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class DatasourceColumn - entita zachycující mapování konkrétního datového sloupce...
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $datasourceColumnId=null
 * @property Datasource $datasource m:hasOne
 * @property int|null $dbDatasourceColumnId = null
 * @property string $name
 * @property string $type m:Enum(self::TYPE_*)
 * @property int|null $strLen = null
 * @property bool $active
 * @property Format|null $format m:hasOne
 */
class DatasourceColumn extends Entity{
  const TYPE_STRING='string';
  const TYPE_INTEGER='int';
  const TYPE_FLOAT='float';
  //TODO pročistit seznam datových typů
  const TYPE_NOMINAL='nominal';
  const TYPE_NUMERICAL='numerical';

  /**
   * Funkce vracející základní přehled dat
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