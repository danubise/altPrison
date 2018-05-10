#!/usr/bin/php -q
<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include("config.php");
include("phpagi.php");
include("mysqli.php");
include("log.php");
include("scheduler_class.php");

$scheduler = new Scheduler($config);
$scheduler->Process();


?>