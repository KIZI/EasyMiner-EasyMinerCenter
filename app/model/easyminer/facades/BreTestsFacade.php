<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;
use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\BreTest;
use EasyMinerCenter\Model\EasyMiner\Entities\BreTestUser;
use EasyMinerCenter\Model\EasyMiner\Repositories\BreTestsRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\BreTestUsersRepository;

/**
 * Class BreTestsFacade
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class BreTestsFacade{
  /** @var BreTestsRepository $breTestsRepository */
  private $breTestsRepository;
  /** @var BreTestUsersRepository $breTestUsersRepository */
  private $breTestUsersRepository;

  /**
   * @param $id
   * @return BreTest
   * @throws \Exception
   */
  public function findBreTest($id){
    return $this->breTestsRepository->find($id);
  }

  /**
   * @param string $testKey
   * @return BreTest
   * @throws \Exception
   */
  public function findBreTestByKey($testKey){
    return $this->breTestsRepository->findBy(['test_key'=>$testKey]);
  }

  /**
   * @param int $id
   * @return BreTestUser
   * @throws \Exception
   */
  public function findBreTestUser($id){
    return $this->breTestUsersRepository->find($id);
  }

  /**
   * @param string $testUserKey
   * @return BreTestUser
   * @throws \Exception
   */
  public function findBreTestUserByKey($testUserKey){
    return $this->breTestUsersRepository->findBy(['test_key'=>$testUserKey]);
  }

  /**
   * @param BreTest $breTest
   * @return BreTestUser
   */
  public function createNewBreTestUser(BreTest $breTest){
    //TODO
  }

  /**
   * @param BreTestUser $breTestUser
   * @return bool
   */
  public function saveBreTestUser(BreTestUser &$breTestUser){
    $result=$this->breTestUsersRepository->persist($breTestUser);
    if (empty($breTestUser->test_key)){
      $breTestUser->test_key=StringsHelper::randString(10).(103910+$breTestUser->breTestId).StringsHelper::randString(3);
      $this->breTestUsersRepository->persist($breTestUser);
    }
    return $result;
  }

  /**
   * @param BreTest $breTest
   * @return bool
   */
  public function saveBreTest(BreTest &$breTest){
    $result=$this->breTestsRepository->persist($breTest);
    if (empty($breTest->test_key)){
      $breTest->test_key=StringsHelper::randString(10).(11310+$breTest->breTestId).StringsHelper::randString(3);
      $this->breTestsRepository->persist($breTest);
    }
    return $result;
  }

  /**
   * BreTestsFacade constructor.
   * @param BreTestsRepository $breTestsRepository
   * @param BreTestUsersRepository $breTestUsersRepository
   */
  public function __construct(BreTestsRepository $breTestsRepository, BreTestUsersRepository $breTestUsersRepository){
    $this->breTestsRepository=$breTestsRepository;
    $this->breTestUsersRepository=$breTestUsersRepository;
  }

}