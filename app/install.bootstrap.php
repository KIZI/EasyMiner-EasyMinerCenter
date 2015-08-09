<?php

require __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT',__DIR__);
define('TEMP_DIRECTORY',APP_ROOT . '/../temp');
define('LOG_DIRECTORY',APP_ROOT.'/../log');
define('CACHE_DIRECTORY',TEMP_DIRECTORY.'/cache');
define('CREATE_TEMP_DIRECTORY_MESSAGE','Please create writable directory "temp" in the root folder! After this operation, reload this page...');
define('CREATE_CACHE_DIRECTORY_MESSAGE','Please create writable directory "temp/cache" in the root folder! After this operation, reload this page...');


#region temp and cache directory
  if (!is_dir(TEMP_DIRECTORY)){
    //pokus vytvoøit TEMP
    if (!mkdir(TEMP_DIRECTORY,0777)){
      //nelze vytvoøit pøíslušnı adresáø TEMP
      exit(CREATE_TEMP_DIRECTORY_MESSAGE);
    }
  }
  if (!is_writable(TEMP_DIRECTORY) && !chmod(TEMP_DIRECTORY,0777)){
    //adresáø TEMP není zapisovatelnı a nelze zmìnit pøístupová práva
    exit(CREATE_TEMP_DIRECTORY_MESSAGE);
  }
  if (file_exists(CACHE_DIRECTORY) && !is_writable(CACHE_DIRECTORY) && !chmod(CACHE_DIRECTORY,0777)){
    //adresáø TEMP/CACHE není zapisovatelnı a nelze zmìnit pøístupová práva
    exit(CREATE_CACHE_DIRECTORY_MESSAGE);
  }
#endregion temp directory

$configurator = new Nette\Configurator();
$configurator->setTempDirectory(TEMP_DIRECTORY);

#region logging
if (is_dir(LOG_DIRECTORY) && is_writable(LOG_DIRECTORY)){
  $configurator->enableDebugger(__DIR__ . '/../log');
}
$configurator->setDebugMode(false);
#endregion logging

$configurator->createRobotLoader()
	->addDirectory(APP_ROOT.'/InstallModule')
	->register();

$configurator->addConfig(APP_ROOT.'/config/install.config.neon');

$container = $configurator->createContainer();

return $container;
