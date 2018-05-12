<?php
include("internal_config.php");

$config['log_file']=  "/var/log/asterisk/ringing_system.log";
$config['log_write'] = "file";
$config['log_level'] = "all";
$config['message_type_menu_timeout'] = 10000;
$config['pincodeRetryCount'] = 3;
$config['pincodeTimeOut'] = 10000;
$config['messageTypeRetryCount'] = 3;
$config['setHourRetryCount'] = 3;
$config['setMinuteRetryCount'] = 3;
$config['setTimeRetryCount'] = 3;
$config['saveScheduleTimeOut'] = 5000;
$config['maxConcurrentCalls'] = 100;
$config['messages'] = array(
                1 => "hello-world",
                2 => "hello-world",
                3 => "hello-world",
                4 => "hello-world"
            );


?>