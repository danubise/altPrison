<?php

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Europe/Samara');
//if (PHP_SAPI == 'cli')
//	die('This example should only be run from a Web Browser');
/** Include PHPExcel */
require_once 'Classes/PHPExcel.php';
include("config.php");
include("mysqli.php");
include("log.php");
$report = new Report($config);
$report->makeReport();

class Report{

    private $config=null;
    private $db=null;
    private $log=null;
    private $objPHPExcel=null;

    public function __construct($config=""){
        $this->config = $config;
        $this->db = new db($config['mysql']);
        $this->log = new Log($config);

        $this->log->SetGlobalIndex("REPORT");
        $this->log->info( "Start" );
        $this->objPHPExcel = new PHPExcel();

    }
    public function makeReport(){
        $this->createFirstSheet();
        $this->createSecondSheet();
        $this->saveReport();
    }
    function createFirstSheet(){
        $this->objPHPExcel->getProperties()->setCreator("СОЛС")
                                     ->setLastModifiedBy("СОЛС")
                                     ->setTitle("Отчет об оповещении")
                                     ->setSubject("отчет")
                                     ->setDescription("отчет")
                                     ->setKeywords("отчет об оповещении")
                                     ->setCategory("Отчет");
        // Add some data
        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B2', 'Круг 1')
                    ->setCellValue('B3', 'Количество номеров')
                    ->setCellValue('B4', 'Количество неоповещенных')
                    ->setCellValue('B5', 'Не оповещенные номера');

        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B7', 'Круг 2')
                    ->setCellValue('B8', 'Количество номеров')
                    ->setCellValue('B9', 'Количество неоповещенных')
                    ->setCellValue('B10', 'Не оповещенные номера');

        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B12', 'Круг 3')
                    ->setCellValue('B13', 'Количество номеров')
                    ->setCellValue('B14', 'Количество неоповещенных')
                    ->setCellValue('B15', 'Не оповещенные номера');

        $this->objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        // Miscellaneous glyphs, UTF-8

        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'Общий отчет');

        $this->objPHPExcel->getActiveSheet()->setTitle('Общий отчет');
    }

    function createSecondSheet(){
        $objWorkSheet = $this->objPHPExcel->createSheet(1);
        $this->objPHPExcel->setActiveSheetIndex(1)
                    ->setCellValue('A1', 'Детальный отчет');
        $this->objPHPExcel->getActiveSheet()->setTitle('Детальный отчет');
        $this->objPHPExcel->setActiveSheetIndex(1)
                    ->setCellValue('A1', '#')
                    ->setCellValue('B1', 'Номер')
                    ->setCellValue('C1', 'Круг 1')
                    ->setCellValue('D1', 'Круг 2')
                    ->setCellValue('E1', 'Круг 3');



        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $this->objPHPExcel->setActiveSheetIndex(0);
        // Redirect output to a client’s web browser (Excel5)
    }

    function saveReport(){
       // header('Content-Type: application/vnd.ms-excel');
       // header('Content-Disposition: attachment;filename="01simple.xls"');
       // header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
      //  header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
       // header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
       // header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
       // header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        //header ('Pragma: public'); // HTTP/1.0
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
        $objWriter->save('test.xls');
    }
}
exit;
?>