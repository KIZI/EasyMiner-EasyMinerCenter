<?php
namespace App\Model\EasyMiner\Facades;

use App\Model\EasyMiner\Repositories\HelperDataRepository;

class HelperFacade {
  /** @var  HelperDataRepository $helperDataRepository */
  private $helperDataRepository;

  public function __construct(HelperDataRepository $helperDataRepository){
    $this->helperDataRepository=$helperDataRepository;
  }

  //TODO
  /*
  public function saveData($miner,$type,$data){
    $this->helperDataRepository->pe

    database->table(self::TABLE)->insert(array('miner'=>$miner,'type'=>$type,'data'=>$data));
  }

  public function loadData($miner,$type){
    $result=$this->database->query('SELECT `data` FROM `'.self::TABLE.'` WHERE miner=? AND `type`=?',$miner,$type);
    return $result->fetchField();
  }

  public function deleteData($miner,$type){
    $this->database->table(self::TABLE)->where('miner=? AND `type`=?',array($miner,$type))->delete();
  }
  */
} 