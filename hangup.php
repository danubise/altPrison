#!/usr/bin/php -q
<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include("config.php");
include("mysqli.php");
include("log.php");
include("ringout_class.php");
include("ami.php");
include("phpagi.php");

$config['log_write'] = "file"; //file

$ringout = new Ringout($config);
$ringout->hangup();

?>