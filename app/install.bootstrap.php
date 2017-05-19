<?php

/**
 * Boostrap script of the installation of application EasyMinerCenter
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link http://easyminer.eu
 */

require __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT',__DIR__);
define('TEMP_DIRECTORY',APP_ROOT . '/../temp');
define('LOG_DIRECTORY',APP_ROOT.'/../log');
define('CACHE_DIRECTORY',TEMP_DIRECTORY.'/cache');
define('CREATE_TEMP_DIRECTORY_MESSAGE','Please create writable directory "temp" in the root folder! After this operation, reload this page...');
define('CREATE_CACHE_DIRECTORY_MESSAGE','Please create writable directory "temp/cache" in the root folder! After this operation, reload this page...');

#region temp and cache directory
  if (!is_dir(TEMP_DIRECTORY)){
    //try to create the directory TEMP
    if (!mkdir(TEMP_DIRECTORY,0777)){
      //TEMP directory creation failed
      exit(CREATE_TEMP_DIRECTORY_MESSAGE);
    }
  }
  if (!is_writable(TEMP_DIRECTORY) && !chmod(TEMP_DIRECTORY,0777)){
    //directory TEMP is not writtable and the change of permissions is not allowed
    exit(CREATE_TEMP_DIRECTORY_MESSAGE);
  }
  if (file_exists(CACHE_DIRECTORY) && !is_writable(CACHE_DIRECTORY) && !chmod(CACHE_DIRECTORY,0777)){
    //directory TEMP/CACHE is not writtable and the change of permissions is not allowed
    exit(CREATE_CACHE_DIRECTORY_MESSAGE);
  }
#endregion temp directory

$configurator = new Nette\Configurator();
$configurator->setTempDirectory(TEMP_DIRECTORY);

#region logging
$configurator->setDebugMode(false);
if (is_dir(LOG_DIRECTORY) && is_writable(LOG_DIRECTORY)){
  $configurator->enableDebugger(__DIR__ . '/../log');
}
#endregion logging

$robotLoader=$configurator->createRobotLoader();
$robotLoader->addDirectory(APP_ROOT.'/InstallModule');
$robotLoader->addDirectory(APP_ROOT.'/model');
$robotLoader->register();

$configurator->addConfig(APP_ROOT.'/config/install.config.neon');
if (file_exists(APP_ROOT.'/config/dev.local.neon')){
  //add configuration of the DEV submodule
  $configurator->addConfig(APP_ROOT.'/config/dev.local.neon');
}

$container = $configurator->createContainer();

return $container;
