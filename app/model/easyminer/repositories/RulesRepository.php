<?php

namespace App\Model\EasyMiner\Repositories;

class RulesRepository extends BaseRepository{

  public function calculateMissingInterestMeasures(){
    $this->connection->query('UPDATE ['.$this->getTable().'] SET confidence=(a/(a+b)) WHERE confidence IS NULL;');
    $this->connection->query('UPDATE ['.$this->getTable().'] SET support=(a/(a+b+c+d)) WHERE support IS NULL;');
    //TODO zkontrolovat vzorec liftu
    $this->connection->query('UPDATE ['.$this->getTable().'] SET lift=((a*(a+b+c+d))/((a+b)*(a+c))) WHERE lift IS NULL;');

  }
  
  
}