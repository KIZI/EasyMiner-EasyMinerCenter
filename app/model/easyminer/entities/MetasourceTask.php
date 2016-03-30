<?php

namespace EasyMinerCenter\Model\EasyMiner\Entities;
use EasyMinerCenter\Model\Preprocessing\Entities\PpTask;
use LeanMapper\Entity;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * Class MetasourceTask
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $metasourceTaskId = null
 * @property Metasource $metasource m:hasOne
 * @property Attribute[] $attributes m:hasMany
 * @property string $state = m:enum(self::STATE_*)
 * @property string $type = m:enum(self::TYPE_*)
 * @property string $params = ''
 * @property-read PpTask $ppTask
 */
class MetasourceTask extends Entity{

  const STATE_NEW='new';
  const STATE_IN_PROGRESS='in_progress';
  const STATE_DONE='done';

  const TYPE_INITIALIZATION='initialization';
  const TYPE_PREPROCESSING='preprocessing';

  private $params='';

  /**
   * @return array
   */
  public function getParams(){
    try{
      $arr=Json::decode($this->row->params,Json::FORCE_ARRAY);
    }catch (\Exception $e){
      $arr=[];
    }
    return $arr;
  }

  /**
   * @param array|object|string $params
   * @throws JsonException
   */
  public function setParams($params){
    if (is_array($params)||is_object($params)){
      $this->row->params=Json::encode($params);
    }elseif(is_string($params)){
      $this->row->params=$params;
    }else{
      $this->row->params='';
    }
  }
  
  /**
   * @return PpTask
   */
  public function getPpTask(){
    $params=$this->getParams();
    $ppTask = new PpTask();
    if (!empty($params['ppTask'])){
      foreach($params['ppTask'] as $id=>$value){
        if (property_exists($ppTask, $id)){
          $ppTask->{$id}=$value;
        }
      }
    }
    return $ppTask;
  }

  /**
   * @param PpTask $ppTask
   */
  public function setPpTask(PpTask $ppTask) {
    $params=$this->getParams();
    $params['ppTask']=$ppTask;
    $this->setParams($params);
  }

} 