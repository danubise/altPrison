<?php

/** Error reporting */
//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
//date_default_timezone_set('Europe/Samara');

require_once 'Classes/PHPExcel.php';

//include("config.php");
//include("mysqli.php");
//include("log.php");
//$report = new Report($config, 17);
//$report->makeReport();

class Report{

    private $config=null;
    private $db=null;
    private $log=null;
    private $objPHPExcel=null;
    private $scheduleid=0;

    public function __construct($config="", $scheduleid){
        $this->config = $config;
        $this->db = new db($config['mysql']);
        $this->log = new Log($config);

        $this->log->SetGlobalIndex("REPORT");
        $this->log->info( "Start" );
        $this->objPHPExcel = new PHPExcel();
        $this->scheduleid = $scheduleid;

    }

    public function makeReport(){
        $this->createFirstSheet();
        $this->createSecondSheet();
        return $this->saveReport();
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
        $firstTotal = $this->db->select("count(*) from `dial` where scheduleid=".$this->scheduleid, false);
        $this->log->debug($this->db->query->last);
        //$firstTotal = 100;
        $firstNotAnswered = $this->db->select("count(*) from `dial` where dialcount>1 AND scheduleid=".$this->scheduleid, false);
        $this->log->debug($this->db->query->last);
        //$firstNotAnswered = 20;
        $line="";
        if($firstNotAnswered > 0){
            $notDialedNumbers = $this->db->select("phonenumber from `dial` where dialcount>1 AND scheduleid=".$this->scheduleid);

            foreach($notDialedNumbers as $key => $phoneNumber){
                $line.=$phoneNumber.",";
            }
        }
        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B2', 'Круг 1')
                    ->setCellValue('B3', 'Количество номеров')
                    ->setCellValue('C3', $firstTotal)
                    ->setCellValue('B4', 'Количество неоповещенных')
                    ->setCellValue('C4', $firstNotAnswered)
                    ->setCellValue('B5', 'Не оповещенные номера')
                    ->setCellValue('C5', $line);

        $secondTotal = $firstNotAnswered;
        $secondNotAnswered = $this->db->select("count(*) from `dial` where dialcount=3 AND scheduleid=".$this->scheduleid, false);
        $this->log->debug($this->db->query->last);
        //$secondNotAnswered = 15;
        $line="";
        if($secondNotAnswered > 0){
            $notDialedNumbers = $this->db->select("phonenumber from `dial` where dialcount=3 AND scheduleid=".$this->scheduleid);
            foreach($notDialedNumbers as $key => $phoneNumber){
                $line.=$phoneNumber.",";
            }
        }
        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B7', 'Круг 2')
                    ->setCellValue('B8', 'Количество номеров')
                    ->setCellValue('C8', $secondTotal)
                    ->setCellValue('B9', 'Количество неоповещенных')
                    ->setCellValue('C9', $secondNotAnswered)
                    ->setCellValue('B10', 'Не оповещенные номера')
                    ->setCellValue('C10', $line);

        $thirdTotal = $secondNotAnswered;
        $thirdNotAnswered= $this->db->select("count(*) from `dial` where  status=0 AND dialcount=3 AND scheduleid=".$this->scheduleid, false);
        $this->log->debug($this->db->query->last);
        //$thirdNotAnswered = 15;
        $line="";
        if($thirdNotAnswered > 0){
            $notDialedNumbers = $this->db->select("phonenumber from `dial` where status=0 AND dialcount=3 AND scheduleid=".$this->scheduleid);
            foreach($notDialedNumbers as $key => $phoneNumber){
                $line.=$phoneNumber.",";
            }
        }
        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B12', 'Круг 3')
                    ->setCellValue('B13', 'Количество номеров')
                    ->setCellValue('C13', $thirdTotal)
                    ->setCellValue('B14', 'Количество неоповещенных')
                    ->setCellValue('C14', $thirdNotAnswered)
                    ->setCellValue('B15', 'Не оповещенные номера')
                    ->setCellValue('C15', $line);

        $this->objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $groupdata = $this->db->select(" name, voicefilename
            from groups as g, schedule as s
            where  s.groupid=g.groupid AND s.scheduleid=".$this->scheduleid, false);
        $this->log->debug($this->db->query->last);
        $this->log->debug($groupdata);
        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'Общий отчет');
        $this->objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B1',  iconv(mb_detect_encoding($groupdata['name'], mb_detect_order(), true), "UTF-8", $groupdata['name']));
        $this->objPHPExcel->getActiveSheet()->setTitle('Общий отчет');
    }

    function createSecondSheet(){

        $numbersForReport = $this->db->select("phonenumber , status , dialcount from dial where scheduleid=".$this->scheduleid);
        //$numbersForReport = array(
        //    0 => array("phonenumber" => 6667,"status" => 1,"dialcount" => 1),
        //    1 => array("phonenumber" => 6661,"status" => 0,"dialcount" => 3),
        //    2 => array("phonenumber" => 6662,"status" => 1,"dialcount" => 2),
        //    3 => array("phonenumber" => 6663,"status" => 1,"dialcount" => 3),
        //    4 => array("phonenumber" => 6664,"status" => 1,"dialcount" => 3),
        //    5 => array("phonenumber" => 6665,"status" => 0,"dialcount" => 3)
        //);

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
        foreach($numbersForReport as $id=>$numberData){
            $cellNumber = $id+2;
            $k1="--";
            $k2="--";
            $k3="--";
            if ($numberData['status'] == 1){
                if($numberData['dialcount'] == 1){
                    $k1="OK";
                }else if($numberData['dialcount'] == 2){
                    $k2="OK";
                }else{
                    $k3="OK";
                }
            }
            $this->objPHPExcel->setActiveSheetIndex(1)
                            ->setCellValue('A'.$cellNumber, $id+1 )
                            ->setCellValue('B'.$cellNumber, $numberData['phonenumber'])
                            ->setCellValue('C'.$cellNumber, $k1)
                            ->setCellValue('D'.$cellNumber, $k2)
                            ->setCellValue('E'.$cellNumber, $k3);
        }
        $this->objPHPExcel->setActiveSheetIndex(0);
    }

    function saveReport(){
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
        $filename = "notificationreport".$this->scheduleid.".xls";
        $this->log->info("report file name :".$filename);
        $objWriter->save($filename);
        return $filename;
    }
}
?>