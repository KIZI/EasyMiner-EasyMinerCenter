<?php

namespace App\Model\EasyMiner\Repositories;

use App\Model\EasyMiner\Entities\Datasource;
use App\Model\EasyMiner\Entities\User;
use Nette;

class DatasourcesRepository extends Nette\Object{
  /** @var \Nette\Database\Context */
  protected $database;
  const TABLE='datasources';

  public function __construct(Nette\Database\Context $database){
    $this->database = $database;
  }

  /**
   * @param int $id
   * @return Datasource|null
   */
  public function findDatasource($id){
    if ($data=$this->database->table(self::TABLE)->select('*')->where(array('id_datasource',$id))->limit(1)->fetch()){
      return new Datasource($data,$this->database);
    }
    return null;
  }

  /**
   * @param int|User $user
   * @return Datasource[]|null
   */
  public function findMinersByUser($user){
    if ($user instanceof User){
      $user=$user->idUser;
    }
    $result=array();
    if ($dataRows=$this->database->table(self::TABLE)->select('*')->where('id_user=?',$user)->fetchAll()){
      if (count($dataRows)){
        foreach ($dataRows as $data){
          $result[]=new Datasource($data,$this->database);
        }
        return $result;
      }
    }
    return null;
  }



  /**
   * @param Datasource $datasource
   * @return bool|int|Nette\Database\Table\IRow
   */
  public function saveMiner(Datasource $datasource){
    if ($datasource->idDatasource){
      //update
      return $this->database->table(self::TABLE)->where(array('id_datasource'=>$datasource->idDatasource))->update($datasource->getDataArr());
    }else{
      //insert
      return $this->database->table(self::TABLE)->insert($datasource->getDataArr());
    }
  }

  /**
   * @param Datasource|int $datasource
   * @return int
   */
  public function deleteDatasource($datasource){
    if ($datasource instanceof Datasource){
      $datasource=$datasource->idDatasource;
    }
    return $this->database->table(self::TABLE)->where(array('id_datasource'=>$datasource))->delete();
  }



} 