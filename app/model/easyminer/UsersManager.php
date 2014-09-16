<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 16.9.14
 * Time: 15:49
 */

namespace App\Model\EasyMiner;


use App\Model\EasyMiner\Repositories\UsersRepository;
use Nette;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;

class UsersManager extends UsersRepository implements IAuthenticator{

  /**
   * Performs an authentication against e.g. database.
   * and returns IIdentity on success or throws AuthenticationException
   * @return IIdentity
   * @throws AuthenticationException
   */
  function authenticate(array $credentials) {
    list($username, $password) = $credentials;

    $user = $this->findUserByEmail($username);

    if (!$row) {
      throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

    } elseif (!Passwords::verify($password, $user->password)) {
      throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

    } elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
      $row->update(array(
        self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
      ));
    }

    $arr = $row->toArray();
    unset($arr[self::COLUMN_PASSWORD_HASH]);
    return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
  }
}