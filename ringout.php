#!/usr/bin/php -q
<?php
/*
 * danubise@gmail.com
 * skype:danubise
 */
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include("config.php");
include("mysqli.php");
include("log.php");
include("ringout_class.php");
include("ami.php");
print_r($config);
$ringout = new Ringout($config);
$ringout->process();

?>