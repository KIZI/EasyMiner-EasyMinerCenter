<?php

/**
 * Main script of the InstallModule of the application EasyMinerCenter - starts the script app/install.bootstrap.php
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link http://easyminer.eu
 */

define('WWW_ROOT',__DIR__);

$container = require __DIR__ . '/../app/install.bootstrap.php';
$container->getService('application')->run();