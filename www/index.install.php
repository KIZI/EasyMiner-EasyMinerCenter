<?php

/**
 * This file is used only for InstallModule
 */
define('WWW_ROOT',__DIR__);

$container = require __DIR__ . '/../app/install.bootstrap.php';
$container->getService('application')->run();