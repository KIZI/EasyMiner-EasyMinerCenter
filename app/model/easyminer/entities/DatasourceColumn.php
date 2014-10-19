<?php
namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;


/**
 * Class DatasourceColumn - entita zachycující mapování konkrétního datového sloupce...
 * @package App\Model\EasyMiner\Entities
 * @property int|null $datasourceColumnId=null
 * @property Datasource $datasource m:hasOne
 * @property string $name
 * @property int $formatId
 */
class DatasourceColumn extends Entity{

} 