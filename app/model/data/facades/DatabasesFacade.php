<?php
namespace EasyMinerCenter\Model\Data\Facades;
use EasyMinerCenter\Model\Data\Databases\DatabaseFactory;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

/**
 * Class DatabasesFacade - nová fasáda pro práci s databázemi
 * @package EasyMinerCenter\Model\Data\Facades
 * @author Stanislav Vojíř
 */
class DatabasesFacade{
  /** @var  DatabaseFactory $databasesFactory */
  private $databaseFactory;
  /** @var  User $user */
  private $user;

  /**
   * @param DatabaseFactory $databaseFactory
   */
  public function __construct(DatabaseFactory $databaseFactory) {
    $this->databaseFactory=$databaseFactory;
  }

  /**
   * Metoda pro vybrání konkrétního uživatele
   * @param User $user
   */
  public function setUser(User $user) {
    $this->user=$user;
  }

}