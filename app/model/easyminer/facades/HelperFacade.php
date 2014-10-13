<?php
namespace App\Model\EasyMiner\Facades;

use app\model\easyminer\entities\HelperData;
use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Repositories\HelperDataRepository;
use Nette\Utils\Json;

class HelperFacade {
  /** @var  HelperDataRepository $helperDataRepository */
  private $helperDataRepository;

  public function __construct(HelperDataRepository $helperDataRepository){
    $this->helperDataRepository=$helperDataRepository;
  }

  /**
   * Funkce pro načtení entity HelperData
   * @param int|Miner $miner
   * @param string $type
   * @return HelperData
   * @throws \Exception
   */
  private function findHelperData($miner,$type){
    if ($miner instanceof Miner){
      $miner=$miner->minerId;
    }
    /** @var HelperData $helperData */
    return $this->helperDataRepository->findBy(array('miner_id'=>$miner,'type'=>$type));
  }

  /**
   * @param int|Miner $miner
   * @param string $type
   * @return string
   */
  public function loadHelperData($miner,$type){
    if ($miner instanceof Miner){
      $miner=$miner->minerId;
    }
    $helperData=$this->findHelperData($miner,$type);
    return $helperData->data;
  }

  /**
   * @param int|Miner $miner
   * @param string $type
   * @param string $data
   */
  public function saveHelperData($miner,$type,$data){
    try{
      $helperData=$this->findHelperData($miner,$type);
    }catch (\Exception $e){
      /*chybu ignorujeme (prostě jen nebyla daná data nalezena...)*/
      $helperData=new HelperData();
      $helperData->miner=($miner instanceof Miner?$miner->minerId:$miner);
      $helperData->type=$type;
    }
    if (!is_string($data)){
      $data=Json::encode($data);
    }
    $helperData->data=$data;
    $this->helperDataRepository->persist($helperData);
  }

  /**
   * @param $miner
   * @param $type
   * @throws \LeanMapper\Exception\InvalidStateException
   */
  public function deleteHelperData($miner,$type){
    $helperData=$this->findHelperData($miner,$type);
    $this->helperDataRepository->delete($helperData);
  }

} 