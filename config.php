<?php
include_once ('/var/www/html/dialmanager/internal_config.php');
$config['log_file']=  "/var/log/asterisk/ringing_system.log";
$config['log_write'] = "file"; //file
$config['log_level'] = "all";
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

$config = $_config;
?>