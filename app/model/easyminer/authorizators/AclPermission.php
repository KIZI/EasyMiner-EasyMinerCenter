<?php

namespace EasyMinerCenter\Model\EasyMiner\Authorizators;

use Nette\Security\Permission;

class AclPermission extends Permission{

  public function allow($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL, $assertion = NULL){
    if ($roles=="owner"){
      parent::allow("owner",$resources,$privileges,function($permission, $role, $resource, $privilege){
        $queRole = $permission->getQueriedRole();
        $queResource = $permission->getQueriedResource();
        if ($queRole instanceof OwnerRole && $queResource instanceof IOwnerResource){
          return $queRole->getUserId() === $queResource->getUserId();
        }else{
          return false;
        }
      });
    }else{
      parent::allow($roles,$resources,$privileges,$assertion);
    }
  }

  /**
   * Funkce pro kontrolu oprávnění přístupu ke zvolenému zdroji
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