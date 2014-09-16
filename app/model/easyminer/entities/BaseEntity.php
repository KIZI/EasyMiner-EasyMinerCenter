<?php

namespace App\Model\EasyMiner\Entities;


abstract class BaseEntity {

  /**
   * Funkce pro vygenerování pole s daty pro uložení do DB
   * @param bool $includeId = false
   * @return array
   */
  public abstract function getDataArr($includeId=false);

  /**
   * Funkce pro naplnění objektu daty z DB či z pole
   * @param $data
   */
  public abstract function loadDataArr($data);

} 