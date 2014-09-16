<?php

namespace App\Model\EasyMiner\Entities;

/**
 * Class User
 * @package App\Model\EasyMiner\Entities
 * @property int $idUser
 * @property string $name
 * @property string $email
 * @property string $password
 */
class User extends BaseEntity{

  /**
   * Funkce pro vygenerování pole s daty pro uložení do DB
   * @param bool $includeId = false
   * @return array
   */
  public function getDataArr($includeId = false) {
    $arr = array(
      'name'=>$this->name,
      'email'=>$this->email,
      'password'=>$this->password
    );
    if ($includeId){
      $arr['id_user']=$this->idUser;
    }
    return $arr;
  }

  /**
   * Funkce pro naplnění objektu daty z DB či z pole
   * @param $data
   */
  public function loadDataArr($data) {
    $this->idUser=$data['id_user'];

    $this->name=$data['name'];
    $this->email=$data['email'];
    $this->password=$data['password'];
  }
}