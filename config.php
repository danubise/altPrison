<?php
include("internal_config.php");

$config['log_file']=  "/var/log/asterisk/ringing_system.log";
$config['log_write'] = "file"; //file
$config['log_level'] = "all";
$config['message_type_menu_timeout'] = 10000;
$config['pincodeRetryCount'] = 3;
$config['pincodeTimeOut'] = 3000;
$config['pincodeMaxDigit'] = 7;
$config['messageTypeRetryCount'] = 3;
$config['setHourRetryCount'] = 3;
$config['setMinuteRetryCount'] = 3;
$config['setTimeRetryCount'] = 3;
$config['saveScheduleTimeOut'] = 5000;
$config['maxConcurrentCalls'] = 100;
$config['manager_host']="localhost";
$config['manager_port']=5038;
//$config['manager_login']="";
//$config['manager_password']="";
$config['emailFrom']="sols@sarfsin.ru";
$config['emailBody']="Отчет об оповещении прикреплен к письму";
$config['emailTheme']="Отчет об оповещении";

$config['messages'] = array(
                1 => "upravliniemenu",
                2 => "vtoroemenu"
            );
$config['notification'] = array(
                1 => array(
                        1 => "sbor",
                        2 => "vulkan2",
                        3 => "vulkan5",
                        4 => "krepost",
                        5 => "uragan3",
                        6 => "edelveys"

                    ),
                2 => array (
                        1 => "sbor",
                        2 => "shum",
                        3 => "vulkan5",
                        4 => "krepost",
                        5 => "uragan3"
                    )
            );
?>