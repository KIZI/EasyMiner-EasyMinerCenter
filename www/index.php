<?php

/**
 * Main script of the application EasyMinerCenter - starts the script app/bootstrap.php
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link http://easyminer.eu
 */

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

define('WWW_ROOT',__DIR__);

$container = require __DIR__ . '/../app/bootstrap.php';
$container->getService('application')->run();