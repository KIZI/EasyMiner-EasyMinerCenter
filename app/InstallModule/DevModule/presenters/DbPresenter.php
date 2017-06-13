<?php

namespace EasyMinerCenter\InstallModule\DevModule\Presenters;
use EasyMinerCenter\InstallModule\DevModule\Model\Git;
use EasyMinerCenter\InstallModule\DevModule\Model\MysqlDump;
use EasyMinerCenter\InstallModule\Model\ConfigManager;
use EasyMinerCenter\InstallModule\Model\FilesManager;
use Nette\Application\Responses\TextResponse;

/**
 * Class DbDumpPresenter - presenter pro možnost exportu struktury databáze z vývojového serveru na GITHUB
 * @package EasyMinerCenter\DevModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class DbPresenter extends BasePresenter{
  /** @var  ConfigManager $configManager */
  private $configManager;

  /**
   * Action returning the DUMP of database structure
   */
  public function actionReadStructure() {
    $mainDatabaseConfig=$this->configManager->data['parameters']['mainDatabase'];
    $dumpContent=MysqlDump::dumpStructureToFile($mainDatabaseConfig['host'],!empty($mainDatabaseConfig['port'])?$mainDatabaseConfig['port']:null,$mainDatabaseConfig['username'],$mainDatabaseConfig['password'],$mainDatabaseConfig['database']);
    $dump='';
    if (!empty($dumpContent)){
      foreach ($dumpContent as $row){
        $dump.=$row."\r\n";
      }
    }
    $this->sendResponse(new TextResponse($dump));
  }

  /**
   * Action for update of the file with database dump
   */
  public function actionUpdateDumpFile() {
    #region export DB structure
    $mainDatabaseConfig=$this->configManager->data['parameters']['mainDatabase'];
    $dumpContent=MysqlDump::dumpStructureToFile($mainDatabaseConfig['host'],!empty($mainDatabaseConfig['port'])?$mainDatabaseConfig['port']:null,$mainDatabaseConfig['username'],$mainDatabaseConfig['password'],$mainDatabaseConfig['database']);
    $dump='';
    if (!empty($dumpContent)){
      foreach ($dumpContent as $row){
        $dump.=$row."\r\n";
      }
    }
    //save content
    MysqlDump::saveSqlFileContent($dump);
    #endregion export DB structure
    $this->sendJson(['state'=>'OK']);
  }

  /**
   * Action for export of DB structure and comparation of it to the saved file
   * @throws \Nette\Application\AbortException
   */
  public function actionUpdateOnGit() {
    $message='';
    if (!MysqlDump::checkRequirements($message)){
      $this->sendJson(['state'=>'error','message'=>$message]);
      return;
    }

    #region reset git repository
    $sudoCredentials=$this->devConfigManager->getSudoCredentials();
    $git = new Git(FilesManager::getRootDirectory(),@$sudoCredentials['username'],@$sudoCredentials['password']);
    $git->reset();
    $git->clean();
    $git->pull();
    #endregion reset git repository

    #region export database structure
    $mainDatabaseConfig=$this->configManager->data['parameters']['mainDatabase'];
    $dumpContent=MysqlDump::dumpStructureToFile($mainDatabaseConfig['host'],!empty($mainDatabaseConfig['port'])?$mainDatabaseConfig['port']:null,$mainDatabaseConfig['username'],$mainDatabaseConfig['password'],$mainDatabaseConfig['database']);
    $dump='';
    if (!empty($dumpContent)){
      foreach ($dumpContent as $row){
        $dump.=$row."\r\n";
      }
    }
    if ($dump==MysqlDump::getExistingSqlFileContent()){
      $this->sendJson(['state'=>'OK','message'=>'Files are identical.']);
      return;
    }
    //save content
    MysqlDump::saveSqlFileContent($dump);
    #endregion export database structure
    #region commit
    $git->addFileToCommit(realpath(__DIR__.'/../../data/mysql.sql'));
    $git->commitAndPush('DB structure updated');
    #endregion commit

    $this->sendJson(['state'=>'OK','message'=>'Updated.']);
  }

  /**
   * DbPresenter constructor, gets also the access to local config file
   */
  public function __construct() {
    parent::__construct();
    $this->configManager=new ConfigManager(FilesManager::getRootDirectory().'/app/config/config.local.neon');
  }

}