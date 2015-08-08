<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;

use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Repositories\PreprocessingsRepository;

class PreprocessingsFacade {
  /** @var PreprocessingsRepository $preprocessingsRepository */
  private $preprocessingsRepository;

  /**
   * @param PreprocessingsRepository $preprocessingsRepository
   */
  public function __construct(PreprocessingsRepository $preprocessingsRepository){
    $this->preprocessingsRepository=$preprocessingsRepository;
  }

  /**
   * @param int $id
   * @return Preprocessing
   */
  public function findPreprocessing($id) {
    return $this->preprocessingsRepository->find($id);
  }

  /**
   * @param Preprocessing $preprocessing
   * @return bool
   */
  public function savePreprocessing(Preprocessing &$preprocessing){
    $result = $this->preprocessingsRepository->persist($preprocessing);
    return $result;
  }


  /**
   * @param Preprocessing|int $preprocessing
   * @return int
   */
  public function deletePreprocessing($preprocessing){
    if (!($preprocessing instanceof Preprocessing)){
      $preprocessing=$this->findPreprocessing($preprocessing);
    }
    return $this->preprocessingsRepository->delete($preprocessing);
  }

  /**
   * @param array $params = array()
   * @param int $offset = null
   * @param int $limit = null
   * @return Preprocessing[]|null
   */
  public function findPreprocessings($params=array(),$offset=null,$limit=null){
    $paramsArr=array();
    return $this->preprocessingsRepository->findAllBy($paramsArr,$offset,$limit);
  }

}