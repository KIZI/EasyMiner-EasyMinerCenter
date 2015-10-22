<?php

namespace EasyMinerCenter\Model\EasyMiner\Authorizators;

use Nette\Security\IResource;

/**
 * Interface IOwnerResource
 * @package EasyMinerCenter\Model\EasyMiner\Authorizators
 */
interface IOwnerResource extends IResource{

  /**
   * Funkce vracející ID vlastníka (uživatele)
   * @return int
   */
  function getUserId();

}