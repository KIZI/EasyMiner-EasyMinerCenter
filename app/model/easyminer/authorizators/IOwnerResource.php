<?php

namespace EasyMinerCenter\Model\EasyMiner\Authorizators;

use Nette\Security\IResource;

/**
 * Interface IOwnerResource
 * @package EasyMinerCenter\Model\EasyMiner\Authorizators
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
interface IOwnerResource extends IResource{

  /**
   * Method returning ID of the owner (User)
   * @return int
   */
  function getUserId();

}