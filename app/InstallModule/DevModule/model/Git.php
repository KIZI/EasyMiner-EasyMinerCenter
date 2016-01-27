<?php

namespace EasyMinerCenter\InstallModule\DevModule\Model;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Class Git - wrapper pro možnost práce s GITem
 * @package EasyMinerCenter\InstallModule\DevModule\Model
 * @author Stanislav Vojíř
 */
class Git {
  /** @var string $repositoryPath */
  private $repositoryPath;
  /** @var  string $sudoCommand */
  private $sudoCommand;

  /**
   * @param string $repositoryPath
   * @param string $sudoUsername
   * @param string $sudoPassword
   */
  public function __construct($repositoryPath='',$sudoUsername="",$sudoPassword="") {
    $this->repositoryPath=realpath($repositoryPath);
    if (!empty($sudoUsername)){
      $this->sudoCommand='sudo -u '.escapeshellarg($sudoUsername);
      if (!empty($sudoPassword)){
        $this->sudoCommand.=' <<< '.escapeshellarg($sudoPassword);
      }
      $this->sudoCommand.=' ';
    }
  }

  /**
   * @param string $file
   * @throws \Exception
   */
  public function addFileToCommit($file) {
    $this->execute('git add '.escapeshellarg($file));
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

  public function pull() {
    $this->execute('git pull');
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
    exec($this->sudoCommand.$command, $output, $returnValue);
    Debugger::log($this->sudoCommand.$this->sudoCommand.$command,ILogger::DEBUG);
    chdir($cwd);
    if ($returnValue !== 0) {
      Debugger::log(' '.$returnValue.' '.$command,ILogger::ERROR);
      Debugger::log($output,ILogger::ERROR);
      //throw new \Exception(implode("\r\n", $output));
    }
    return $output;
  }

}