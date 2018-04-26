#!/bin/php -q
<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include("config.php");
include("phpagi.php");
include("mysqli.php");
include("log.php");

//$db = new db($config['mysql']);
$log = new Log($config);
$log->info("Start");

$agi = new AGI();
$log->info("Incomming call from :");
$log->info($agi->request['agi_callerid']);
$agi->answer();

$agi->stream_file("en/demo-congrats","#");
do{
    $agi->stream_file("enter-some-digits","#");
    $result = $agi->get_data('beep', 3000, 20);
    $keys = $result['result'];
    $agi->stream_file("you-entered","#");
    $agi->say_digits($keys);
} while($keys != '111');
$log->info("End");
?>