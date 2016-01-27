<?php

namespace EasyMinerCenter\InstallModule\DevModule\Model;

/**
 * Class Git - wrapper pro možnost práce s GITem
 * @package EasyMinerCenter\InstallModule\DevModule\Model
 * @author Stanislav Vojíř
 */
class Git {
  private $repositoryPath;

  /**
   * @param string $repositoryPath
   */
  public function __construct($repositoryPath='') {
    $this->repositoryPath=realpath($repositoryPath);
  }

  /**
   * @param string $file
   * @throws \Exception
   */
  public function addFileToCommit($file) {
    $this->execute('git add '.$file);
  }

  /**
   * @throws \Exception
   */
  public function reset() {
    $this->execute('git reset --hard');
  }

  /**
   * @throws \Exception
   */
  public function clean() {
    $this->execute('git clean -f -d');
  }

  /**
   * @param string $message
   * @throws \Exception
   */
  public function commitAndPush($message) {
    if (empty($message)){
      $message='server commit '.date('u');
    }
    $message=escapeshellarg($message);
    $this->execute('git commit -q -m '.$message);
    $this->execute('git push');
  }


  /**
   * Funkce pro spuštění konzolového příkazu
   * @param string $command
   * @return string
   * @throws \Exception
   */
  private function execute($command) {
    $cwd = getcwd();
    chdir($this->repositoryPath);
    exec($command, $output, $returnValue);
    chdir($cwd);
    if ($returnValue !== 0) {
      throw new \Exception(implode("\r\n", $output));
    }
    return $output;
  }

}