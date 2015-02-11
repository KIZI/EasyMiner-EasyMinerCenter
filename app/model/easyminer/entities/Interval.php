<?php

namespace App\Model\EasyMiner\Entities;
use App\Libs\StringsHelper;
use LeanMapper\Entity;

/**
 * Class Interval
 * @package App\Model\EasyMiner\Entities
 *
 * @property int $intervalId
 * @property Format|null $format m:hasOne
 * @property float $leftMargin
 * @property float $rightMargin
 * @property string $leftClosure  m:Enum(self::CLOSURE_*)
 * @property string $rightClosure m:Enum(self::CLOSURE_*)
 */
class Interval extends Entity{
  const CLOSURE_OPEN='open';
  const CLOSURE_CLOSED='closed';

  /**
   * @param string $leftClosure
   * @param float $leftMargin
   * @param float $rightMargin
   * @param string $rightClosure
   * @return Interval
   */
  public static function create($leftClosure,$leftMargin,$rightMargin,$rightClosure){
    $interval=new Interval();
    $interval->leftClosure=$leftClosure;
    $interval->rightClosure=$rightClosure;
    $interval->leftMargin=$leftMargin;
    $interval->rightMargin=$rightMargin;
    return $interval;
  }

  /**
   * Funkce pro kontrolu, jestli se interval překrývá s jiným intervalem
   * @param Interval $interval
   * @return bool
   */
  public function isInOverlapWithInterval(Interval $interval){
    if (($this->leftMargin<$interval->leftMargin)||($this->leftMargin==$interval->leftMargin && $this->rightMargin<$interval->rightMargin)){
      $intervalLower=$this;
      $intervalBigger=$interval;
    }else{
      $intervalLower=$interval;
      $intervalBigger=$this;
    }
    if ($intervalLower->rightMargin<$intervalBigger->leftMargin){
      return false;
    }
    if ($intervalLower->rightMargin==$intervalBigger->leftMargin){
      return (!($intervalLower->rightClosure==self::CLOSURE_OPEN || $intervalBigger->leftClosure==self::CLOSURE_OPEN));
    }
    return true;
  }

  /**
   * @return string
   */
  public function __toString(){
    return StringsHelper::formatIntervalString($this->leftClosure,$this->leftMargin,$this->rightMargin,$this->rightClosure);
  }

} 