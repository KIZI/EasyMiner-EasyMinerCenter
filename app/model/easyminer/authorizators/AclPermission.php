<?php

namespace EasyMinerCenter\Model\EasyMiner\Authorizators;

use Nette\Security\Permission;

/**
 * Class AclPermission
 * @package EasyMinerCenter\Model\EasyMiner\Authorizators
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class AclPermission extends Permission{

  /**
   * Allows one or more Roles access to [certain $privileges upon] the specified Resource(s).
   * If $assertion is provided, then it must return TRUE in order for rule to apply.
   *
   * @param  string|array|Permission::ALL $roles
   * @param  string|array|Permission::ALL $resources
   * @param  string|array|Permission::ALL $privileges
   * @param  callable|null $assertion
   * @return Permission
   */
  public function allow($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL, $assertion = NULL){
    if ($roles=="owner"){
      return parent::allow("owner",$resources,$privileges,function($permission, $role, $resource, $privilege){
        $queRole = $permission->getQueriedRole();
        $queResource = $permission->getQueriedResource();
        if ($queRole instanceof OwnerRole && $queResource instanceof IOwnerResource){
          return $queRole->getUserId() === $queResource->getUserId();
        }else{
          return false;
        }
      });
    }else{
      return parent::allow($roles,$resources,$privileges,$assertion);
    }
  }

  /**
   * Method for check of the user privileges and his authorization to work with the given resource
   * Returns TRUE if and only if the Role has access to [certain $privileges upon] the Resource.
   *
   * This method checks Role inheritance using a depth-first traversal of the Role list.
   * The highest priority parent (i.e., the parent most recently added) is checked first,
   * and its respective parents are checked similarly before the lower-priority parents of
   * the Role are checked.
   *
   * @param  string|Permission::ALL|IRole  role
   * @param  string|Permission::ALL|IResource  resource
   * @param  string|Permission::ALL  privilege
   * @throws \Nette\InvalidStateException
   * @return bool
   */
  public function isAllowed($role = self::ALL, $resource = self::ALL, $privilege = self::ALL){
    /*if ($resource instanceof IOwnerResource){
      if ($role instanceof OwnerRole){
        //TODO kontrola oprávnění...
        return ($role->getUserId()==$resource->getUserId());
      }else{
        return false;
      }
    }*/
    //vrácení standartních oprávnění...
    return parent::isAllowed($role,$resource,$privilege);
  }

}