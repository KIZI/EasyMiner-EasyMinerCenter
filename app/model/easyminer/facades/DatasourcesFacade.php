<?php

namespace App\Model\EasyMiner\Facades;


use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Repositories\DatasourcesRepository;

class DatasourcesFacade {
  /** @var DatasourcesRepository $datasourcesRepository */
  private $datasourcesRepository;

  public function __construct(DatasourcesRepository $datasourcesRepository){
    $this->datasourcesRepository=$datasourcesRepository;
  }

  /**
   * @param int $id
   * @return Datasource
   */
  public function findDatasource($id){
    return $this->datasourcesRepository->find($id);
  }

  /**
   * @param int|User $user
   * @return Datasource[]|null
   */
  public function findDatasourcesByUser($user){
    if ($user instanceof User){
      $user=$user->userId;
    }
    return $this->datasourcesRepository->findAllBy(array('user_id'=>$user));
  }



  /**
   * @param Datasource $datasource
   * @return bool
   */
  public function saveDatasource(Datasource &$datasource){
    $this->datasourcesRepository->persist($datasource);
  }

  /**
   * @param Datasource|int $datasource
   * @return int
   */
  public function deleteDatasource($datasource){
    if (!($datasource instanceof Datasource)){
      $datasource=$this->datasourcesRepository->find($datasource);
    }
    return $this->datasourcesRepository->delete($datasource);
  }

  /**
   * @param User|int $user
   * @param string $type
   * @param string $tableName
   * @return bool
   */
  public function checkTableNameExists($user, $type, $tableName) {
    //TODO check table name!!!
    return false;
  }
} 