<?php

namespace App\Model\EasyMiner\Entities;


use LeanMapper\Entity;

/**
 * Class Rule
 * @package App\Model\EasyMiner\Entities
 * @property int $ruleId
 * @property Task $task m:hasOne
 * @property string $text
 * @property Cedent|null $antecedent m:hasOne(antecedent)
 * @property Cedent $consequent m:hasOne(consequent)
 * @property int|null $a
 * @property int|null $b
 * @property int|null $c
 * @property int|null $d
 * @property float|null $confidence = null
 * @property float|null $support = null
 * @property float|null $lift = null
 * @property bool $inRuleClipboard
 * @property-read array $basicDataArr
 */
class Rule extends Entity{

  public function getBasicDataArr() {
    return array('text'=>$this->text,'a'=>$this->a,'b'=>$this->b,'c'=>$this->c,'d'=>$this->d,'selected'=>($this->inRuleClipboard?'1':'0'));
  }
}