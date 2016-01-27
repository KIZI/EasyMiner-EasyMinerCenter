<?php

namespace EasyMinerCenter\InstallModule\DevModule\Model;


use EasyMinerCenter\InstallModule\Model\ConfigManager;

class MysqlDump {
  const MYSQL_FILE='/../../data/mysql.sql';

  /**
   * Funkce pro export struktury databáze
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
   * Funkce pro kontrolu možnosti spustit mysqldump pomocí funkce exec
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
   * Funkce vracející obsah aktuálního SQL souboru
   * @return string
   */
  public static function getExistingSqlFileContent() {
    return file_get_contents(self::getSqlFilePath());
  }

  /**
   * Funkce pro uložení aktualizovaného obsahu souboru
   * @param string $content
   */
  public static function saveSqlFileContent($content) {
    file_put_contents(self::getSqlFilePath(),$content);
  }

  /**
   * Funkce vracející cestu k souboru s konfigurací databáze
   * @return string
   */
  public static function getSqlFilePath() {
    return __DIR__.self::MYSQL_FILE;
  }

}