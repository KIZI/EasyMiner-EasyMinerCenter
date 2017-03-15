<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Libs\StringsHelper;
use LeanMapper\Entity;
use Nette\Utils\Strings;

/**
 * Class Interval
 * @package EasyMinerCenter\Model\EasyMiner\Entities
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
   * @param string $closure
   */
  public function setClosure($closure){
    $closure=Strings::lower($closure);
    if (Strings::startsWith($closure,self::CLOSURE_CLOSED)){
      $this->leftClosure=self::CLOSURE_CLOSED;
    }else{
      $this->leftClosure=self::CLOSURE_OPEN;
    }
    if (Strings::endsWith($closure,self::CLOSURE_CLOSED)){
      $this->rightClosure=self::CLOSURE_CLOSED;
    }else{
      $this->rightClosure=self::CLOSURE_OPEN;
    }
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
   * Funkce pro kontrolu, zda zadaná hodnota patří do tohoto intervalu
   * @param float|Value $value
   * @return bool
   */
  public function containsValue($value){
    if ($value instanceof Value){
      $value=floatval($value->value);
    }
    if ($value<$this->leftMargin || ($this->leftMargin==$value && $this->leftClosure==self::CLOSURE_OPEN)){return false;}
    if ($value>$this->rightMargin || ($this->rightMargin==$value && $this->rightClosure==self::CLOSURE_OPEN)){return false;}
    return true;
  }

  /**
   * Funkce pro kontrolu, zda je daný interval podmnožinou tohoto intervalu
   * @param Interval $interval
   * @return bool
   */
  public function containsInterval(Interval $interval){
    if (!(($this->leftMargin<$interval->leftMargin)||($this->leftMargin==$interval->leftMargin && ($this->leftClosure==self::CLOSURE_CLOSED || $interval->leftClosure==self::CLOSURE_OPEN)))){return false;}
    return (($this->rightMargin>$interval->rightMargin)||($this->rightMargin==$interval->rightMargin && ($this->rightClosure==self::CLOSURE_CLOSED || $interval->rightClosure==self::CLOSURE_OPEN)));
  }

  /**
   * @return string
   */
  public function __toString(){
    return StringsHelper::formatIntervalString($this->leftClosure,$this->leftMargin,$this->rightMargin,$this->rightClosure);
  }

} 