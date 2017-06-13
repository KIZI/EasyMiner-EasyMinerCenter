<?php

namespace EasyMinerCenter\Model\EasyMiner\Repositories;

use EasyMinerCenter\Exceptions\EntityNotFoundException;

/**
 * Class BaseRepository - abstract class representing a generic repository
 * @package EasyMinerCenter\Model\EasyMiner\Repositories
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
abstract class BaseRepository extends \LeanMapper\Repository {
  /**
   * @param int $id
   * @return mixed
   * @throws \Exception
   */
  public function find($id) {
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $row = $this->connection->select('*')
      ->from($this->getTable())
      ->where($this->mapper->getPrimaryKey($this->getTable()) . '= %i', $id)
      ->fetch();

    if ($row === false) {
      throw new EntityNotFoundException('Entity was not found.');
    }
    return $this->createEntity($row);
  }

  /**
   * @return array
   */
  public function findAll() {
    return $this->createEntities(
      $this->connection->select('*')
        ->from($this->getTable())
        ->fetchAll()
    );
  }

  /**
   * @param null $whereArr
   * @return mixed
   * @throws \Exception
   */
  public function findBy($whereArr = null) {
    $query = $this->connection->select('*')->from($this->getTable());
    if ($whereArr != null) {
      $query = $query->where($whereArr);
    }
    $row = $query->fetch();
    if ($row === false) {
      throw new EntityNotFoundException('Entity was not found.');
    }
    return $this->createEntity($row);
  }

  /**
   * @param null|array $whereArr
   * @param null|int $offset
   * @param null|int $limit
   * @return array
   */
  public function findAllBy($whereArr = null, $offset = null, $limit = null) {
    $query = $this->connection->select('*')->from($this->getTable());
    if (isset($whereArr['order'])) {
      $query->orderBy($whereArr['order']);
      unset($whereArr['order']);
    }
    if ($whereArr != null && count($whereArr) > 0) {
      $query = $query->where($whereArr);
    }
    return $this->createEntities($query->fetchAll($offset, $limit));
  }

  public function findCountBy($whereArr = null) {
    $query = $this->connection->select('count(*) as pocet')->from($this->getTable());
    if ($whereArr != null) {
      $query = $query->where($whereArr);
    }
    return $query->fetchSingle();
  }

}


