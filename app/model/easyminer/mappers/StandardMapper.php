<?php

namespace App\Model\EasyMiner\Mappers;
use Nette\Utils\Strings;


/**
   * Standard mapper for conventions:
   * - underdash separated names of tables and cols
   * - PK and FK is in [table]_id format
   * - entity repository is named [Entity]Repository
   * - M:N relations are stored in [table1]_[table2] tables
   *
   * @author Jan Nedbal, Stanislav Vojíř
   */
class StandardMapper extends \LeanMapper\DefaultMapper {

  /** @var string */
  protected $defaultEntityNamespace = 'App\Model\EasyMiner\Entities';

  /**
   * PK format [table]_id
   * @param string $table
   * @return string
   */
  public function getPrimaryKey($table) {
    if (Strings::endsWith($table,'ies')){
      return substr($table,0,strlen($table)-3)."y_id";
    }else{
      return substr($table,0,strlen($table)-1)."_id";
    }
  }

  /**
   * @param string $sourceTable
   * @param string $targetTable
   * @return string
   */
  public function getRelationshipColumn($sourceTable, $targetTable) {
    return $this->getPrimaryKey($targetTable);
  }

  /**
   * some_entity -> Model\Entity\SomeEntity
   * @param string $table
   * @param \LeanMapper\Row $row
   * @return string
   */
  public function getEntityClass($table, \LeanMapper\Row $row = null) {
    if (Strings::endsWith($table,'ies')){
      return $this->defaultEntityNamespace . '\\' . ucfirst($this->underdashToCamel(substr($table,0,strlen($table)-3).'y'));
    }else{
      return $this->defaultEntityNamespace . '\\' . ucfirst($this->underdashToCamel(substr($table,0,strlen($table)-1)));
    }
  }

  /**
   * Model\Entity\SomeEntity -> some_entity
   * @param string $entityClass
   * @return string
   */
  public function getTable($entityClass){
    if (Strings::endsWith($entityClass,'y')){
      return $this->camelToUnderdash(Strings::substring($this->trimNamespace($entityClass),0,Strings::length($entityClass))) .'ies';
    }else{
      return $this->camelToUnderdash($this->trimNamespace($entityClass)).'s';
    }
  }

  /**
   * someField -> some_field
   * @param string $entityClass
   * @param string $field
   * @return string
   */
  public function getColumn($entityClass, $field) {
    return $this->camelToUnderdash($field);
  }

  /**
   * some_field -> someField
   * @param string $table
   * @param string $column
   * @return string
   */
  public function getEntityField($table, $column) {
    return $this->underdashToCamel($column);
  }

  /**
   * Model\Repository\SomeEntityRepository -> some_entity
   * @param string $repositoryClass
   * @return string
   */
  public function getTableByRepositoryClass($repositoryClass) {
    $class = preg_replace('#([a-z0-9]+)iesRepository$#', '$1y', $repositoryClass);
    $class = preg_replace('#([a-z0-9]+)sRepository$#', '$1', $class);
    return $this->camelToUnderdash($this->trimNamespace($class).'s');
  }

  /**
   * camelCase -> underdash_separated.
   * @param  string
   * @return string
   */
  protected function camelToUnderdash($s) {
    $s = preg_replace('#(.)(?=[A-Z])#', '$1_', $s);
    $s = strtolower($s);
    $s = rawurlencode($s);
    return $s;
  }

  /**
   * underdash_separated -> camelCase
   * @param  string
   * @return string
   */
  protected function underdashToCamel($s) {
    $s = strtolower($s);
    $s = preg_replace('#_(?=[a-z])#', ' ', $s);
    $s = substr(ucwords('x' . $s), 1);
    $s = str_replace(' ', '', $s);
    return $s;
  }

}