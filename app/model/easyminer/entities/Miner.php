<?php

namespace App\Model\EasyMiner\Entities;

use LeanMapper\Entity;
use Nette;

/**
 * Class Miner
 * @package App\Model\EasyMiner\Entities
 *
 * @property int|null $minerId = null
 * @property User $user = null m:belongsToOne
 * @property string $name = ''
 * @property string $type m:Enum('lm','r')
 * @property DataSource|null $datasource m:belongsToOne - zdroj původních dat v DB
 * @property Nette\Utils\DateTime|null $created = null
 * @property Nette\Utils\DateTime|null $lastOpened = null
 */
class Miner extends Entity{
  const TYPE_LM='lm';
  const TYPE_R='r';
  const DEFAULT_TYPE='lm';

  /**
   * Funkce vracející přehled jednotlivých podporovaných typů minerů
   * @return array
   */
  public static function getTypes(){
    return array(
      self::TYPE_LM=>'LISp-Miner',
      self::TYPE_R=>'R',
    );
  }
}