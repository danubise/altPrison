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
$log->info( "Incomming call from :" );
$log->info( $agi->request['agi_callerid'] );
//Добавить проверку номера звонящего абонента
$agi->answer();
//Введите пинкод
$log->info("Please enter pincode");
$agi->stream_file("en/demo-congrats","#");
$try=0;
do{
    $result = $agi->get_data('beep', 3000, 4);
    $keys = $result['result'];
    //Проверка пинкод в базе
    if($keys == "111" ){
        $log->info("Pincode successful");
        break;
    }
    $exists = false;
    //ввден не верный пинкод, повторить попытку
    $agi->stream_file("you-entered","#");
    $log->error("Wrong pincode");

} while(true);

$log->info("Enter type message");
//$agi->stream_file("en/demo-congrats","#");
$try=0;
do{
    $messageType = menuSelectMessageType($messages, $agi, $log);

    if($messageType == -1) {
        $log->error("Wrong message type");
        $agi->Hangup();
    }

    $try ++;
    if($try == 3){
        $agi->Hangup();
        die;
    }
} while(true);

$log->info("End");

function menuSelectMessageType($messages, $agi, $log){
    foreach ($messages as $key => $messageTypeVoiceFileName){
        $answer = $agi->get_data($messageTypeVoiceFileName, 0 , 1);
        $log->info($answer);
    }
}
?>