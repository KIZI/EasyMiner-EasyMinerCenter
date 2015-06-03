<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

$configurator->setDebugMode("89.102.188.104");
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
  ->addDirectory(__DIR__ . '/../vendor/others')
  ->addDirectory(__DIR__ . '/../submodules')
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$configurator->addConfig(__DIR__ . '/config/izi-ui.config.neon');

$container = $configurator->createContainer();

return $container;
