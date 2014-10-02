<?php

namespace App\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use Nette\Utils\DateTime;

/**
 * Class User
 * @package App\Model\EasyMiner\Entities
 * @property int|null $idUser
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $facebookId
 * @property string|null $googleId
 * @property DateTime $lastLogin
 * @property bool $active = true
 */
class User extends Entity{

}