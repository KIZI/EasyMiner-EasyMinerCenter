<?php

define('APP_ROOT',__DIR__);

require APP_ROOT . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

$configurator->setDebugMode(true);

$configurator->enableDebugger(APP_ROOT . '/../log');
$configurator->setTempDirectory(APP_ROOT . '/../temp');

$configurator->createRobotLoader()
  ->addDirectory(APP_ROOT)
  //->addDirectory(__DIR__ . '/../vendor/others')
  ->addDirectory(APP_ROOT . '/../submodules')
  ->register();

$configurator->addConfig(APP_ROOT . '/config/config.neon');
$configurator->addConfig(APP_ROOT . '/config/config.local.neon');

$configurator->addConfig(APP_ROOT . '/config/izi-ui.config.neon');

$container = $configurator->createContainer();


return $container;
