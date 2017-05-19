<?php

namespace EasyMinerCenter\InstallModule\DevModule\Model;

/**
 * Class MysqlDump
 * @package EasyMinerCenter\InstallModule\DevModule\Model
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MysqlDump {
  const MYSQL_FILE='/../../data/mysql.sql';

  /**
   * Method for export of database structure
   * @param string $host
   * @param null $port
   * @param string $username
   * @param string $password
   * @param string $database
   * @return string[]
   */
  public static function dumpStructureToFile($host='localhost',$port=null,$username='',$password='',$database) {
    $command='mysqldump --skip-comments --skip-extended-insert --no-data --opt --no-data';
    $command.=' --host='.escapeshellarg($host);
    if (!empty($port)){
      $command.=' --port='.escapeshellarg($port);
    }
    if ($username!=''){
      $command.=' --user='.escapeshellarg($username);
    }
    if ($password!=''){
      $command.=' --password='.escapeshellarg($password);
    }
    $command.=' '.escapeshellarg($database);
    exec($command,$output);
    return $output;
  }

  /**
   * Method for check, if it is possible to run mysqldump using exec function
   * @param string|null $message
   * @return bool
   */
  public static function checkRequirements(&$message=null) {
    try{
      exec('mysqldump --version',$result);
      if (!(strpos(@$result[0],'mysqldump')!==false)){
        $message='Error while execution of mysqldump!';
        return false;
      }
    }catch (\Exception $e){
      $message='Error while execution of mysqldump!';
      return false;
    }
    if (!is_writable(self::getSqlFilePath())){
      $message='File mysql.sql is not writable!';
      return false;
    }
    return true;
  }

  /**
   * Method returning content of actual SQL file
   * @return string
   */
  public static function getExistingSqlFileContent() {
    return file_get_contents(self::getSqlFilePath());
  }

  /**
   * Method for saving of updated SQL file
   * @param string $content
   */
  public static function saveSqlFileContent($content) {
    file_put_contents(self::getSqlFilePath(),$content);
  }

  /**
   * Method returning path to file with database config
   * @return string
   */
  public static function getSqlFilePath() {
    return __DIR__.self::MYSQL_FILE;
  }

}