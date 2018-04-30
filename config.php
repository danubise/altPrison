<?php
include("internal_config.php");

$config['log_file']=  "/var/log/asterisk/ringing_system.log";
$config['log_write'] = "file";
$config['log_level'] = "all";
$config['message_type_menu_timeout'] = 10000;

$messages = array(
    1 => "1",
    2 => "2",
    3 => "3",
    4 => "4"
);

?>