<?php

namespace App\Model\EasyMiner\Repositories;

use Nette;

class HelperRepository extends Nette\Object{
  /** @var \Nette\Database\Context */
  private $database;
  const TABLE = 'helper_data';

  public function __construct(Nette\Database\Context $database){
    $this->database = $database;
  }

  /**
   * @param string $miner
   * @param string $type
   * @param string $data
   */
  public function saveData($miner,$type,$data){
    $this->database->table(self::TABLE)->insert(array('miner'=>$miner,'type'=>$type,'data'=>$data));
  }

  /**
   * @param string $miner
   * @param string $type
   * @return FALSE|string
   */
  public function loadData($miner,$type){
    $result=$this->database->query('SELECT `data` FROM `'.self::TABLE.'` WHERE miner=? AND `type`=?',$miner,$type);
    return $result->fetchField();
  }

  /**
   * @param string $miner
   * @param string $type
   */
  public function deleteData($miner,$type){
    $this->database->table(self::TABLE)->where('miner=? AND `type`=?',array($miner,$type))->delete();
  }

} 