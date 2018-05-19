<?php

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Europe/Samara');

require_once 'Classes/PHPExcel.php';

include("config.php");
include("mysqli.php");
include("log.php");
include("report_class.php");
echo "Start test report";

$config['log_write'] = "null"; //file
$report = new Report($config, 17);
$report->makeReport();