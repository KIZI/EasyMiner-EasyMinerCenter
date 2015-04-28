<?php

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

if (strpos($_SERVER['REQUEST_URI'],'/rest/') && !strpos($_SERVER['REQUEST_URI'],'/rest/auth')) {
  $container = require __DIR__ . '/../app/restBootstrap.php';
  $container->getService('application')->run();
}else{
  $container = require __DIR__ . '/../app/bootstrap.php';
  $container->getService('application')->run();
}