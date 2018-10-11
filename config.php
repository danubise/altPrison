<?php
/*
 * danubise@gmail.com
 * skype:danubise
 */
include_once ('/var/www/html/dialmanager/internal_config.php'); //REMARK IT
;
$config['manager']=array(
    'login'=>"login",
    'password'=>"password",
    'host'=>"127.0.0.1",
    'port'=>"5038");

$config['mysql'] = array(
    'login' => 'login',
    'password' => 'password',
    'database' => 'database',
    'host' => '127.0.0.1'
);

$config = $_config; //REMARK IT
$config['log_file']=  "/var/log/asterisk/ringing_system.log";
$config['log_write'] = ""; //file
$config['log_level'] = "all";
$config['CallerID']= "123456789012";
?>