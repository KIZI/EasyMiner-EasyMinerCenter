<?php

namespace App\Model;

use Nette;

class HelperRepository extends Nette\Object{
  /** @var \Nette\Database\Context */
  private $database;

  public function __construct(Nette\Database\Context $database){
    $this->database = $database;
  }

  /**
   * @param string $miner
   * @param string $type
   * @param string $data
   */
  public function saveData($miner,$type,$data){
    $this->database->query('INSERT INTO helper_data',array('miner'=>$miner,'type'=>$type,'data'=>$data));
  }

  /**
   * @param string $miner
   * @param string $type
   * @return FALSE|string
   */
  public function loadData($miner,$type){
    $result=$this->database->query('SELECT `data` FROM miner=? AND `type`=?',$miner,$type);
    return $result->fetchField();
  }

  /**
   * @param string $miner
   * @param string $type
   */
  public function deleteData($miner,$type){
    $this->database->query('DELETE FROM helper_data WHERE miner=? AND `type`=?',$miner,$type);
  }

} 