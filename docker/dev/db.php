<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$databasePrefix = $argv[1];
$database = $databasePrefix . '000';

$mysqli = new mysqli("easyminer-mysql", "root", "root");
$mysqli->query('DROP DATABASE IF EXISTS '.$database);
$mysqli->query('GRANT ALL PRIVILEGES ON *.* TO "'.$database.'"@"%" IDENTIFIED BY "'.$database.'" WITH GRANT OPTION');
$mysqli->query('CREATE DATABASE '.$database);
$mysqli->query("GRANT ALL PRIVILEGES ON ".$database.".* TO '".$database."'@'%' IDENTIFIED BY '".$database."' WITH GRANT OPTION");
$mysqli->select_db($database);

$sql = file_get_contents('/var/www/html/easyminercenter/app/InstallModule/data/mysql.sql');
$mysqli->multi_query($sql);

$mysqli->close();