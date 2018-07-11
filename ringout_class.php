<?php
require_once ("Classes/PHPMailer-master/class.phpmailer.php");
require_once ("report_class.php");

class Ringout{
    private $config=null;
    private $db=null;
    private $log=null;
    private $ami=null;
    private $socket=null;
    private $agi=null;

    public function __construct($config=""){
        $this->config = $config;
        $this->db = new db($config['mysql']);
        $this->log = new Log($config);
        $this->log->SetGlobalIndex("RINGOUT");
        $this->log->info( "Start Ringout service" );
        $this->ami = new Ami();
    }

    public function hangup(){
        $this->log->info("Start Hangup process");
        $this->agi = new AGI();
        $this->agi->conlog("Hangup process");
        $dialid=$this->agi->request['agi_arg_1'];
        $status=$this->agi->request['agi_arg_2'];
        $this->log->info("Call to ".$dialid." status ".$status);
        $setvalue=array();
        if($status == "ANSWERED"){
            $setvalue = array('action' => 0, 'status' => 1);
        }else{
            $setvalue = array('action' => 0, 'status' => 0);
        }
        $this->db->update( "dial", $setvalue, "dialid=".$dialid);
        $this->log->debug($this->db->query->last);
        return;
    }
    function checkNewTask(){
    // status 1 - new, 2 -  in progress, 3 - done
        $this->db->query('SET NAMES "utf8"');
        $newTasks = $this->db->select("* from `schedule` where `status`=1");
        $this->log->debug($this->db->query->last);
        $this->log->debug($newTasks);
        if($newTasks != null){
            $this->log->info("Adding new task '".count($newTasks)."'");
            $this->addNumberGroupByTask($newTasks);
        }else{
            $this->log->info("Have no new task");
        }

    }

    function sendemail($taskid , $filename){
//        $this->db->query('SET NAMES "utf8"');
        $this->log->info("Send email for task id ".$taskid);
        $email = new PHPMailer();
        $email->CharSet = 'UTF-8';
        $email->From      = $this->config['emailFrom'];
        $email->FromName  = $this->config['emailFrom'];
        $email->Subject   = $this->config['emailTheme'];
        $email->Body      = $this->config['emailBody'];
        $this->db->query('SET NAMES "utf8"');
        $emailAddresses = $this->db->select(" g.emails from groups as g , schedule as s  where s.groupid=g.groupid AND s.scheduleid=".$taskid,false);
        $this->log->debug($this->db->query->last);
        $groupName = $this->db->select(" g.name from groups as g , schedule as s  where s.groupid=g.groupid AND s.scheduleid=".$taskid,false);
        $this->log->debug($this->db->query->last);
        $this->log->debug("Encoding type = ".mb_detect_encoding($groupName, mb_detect_order(), true));

        $mails=explode(",",$emailAddresses);
        foreach($mails as $emailaddress) {
            $email->AddAddress($emailaddress);
        }
        $attachedFileName = $groupName.".xls";
        $this->log->debug("Attached file name : ".$attachedFileName);
        $email->AddAttachment( $filename , $attachedFileName);

        if(!$email->Send()){
            $this->log->error( "Message could not be sent.");
            $this->log->error( "Mailer Error: " . $email->ErrorInfo);
            $update = array(
                "sendEmail" => "9"
            );
            $this->db->update("schedule",$update, "`scheduleid` = '" .$taskid. "'");
            $this->log->debug($this->db->query->last);
            $this->log->error( "Message has not sent");
        }else {
            $update = array(
                "sendEmail" => "1"
            );
            $this->db->update("schedule",$update, "`scheduleid` = '" .$taskid. "'");
            $this->log->debug($this->db->query->last);
            $this->log->info( "Message has been sent to ".$emailAddresses);
        }

    }

    function checkForCompleteTask(){
        $this->log->info("Checking for complete task");
        //  проверить наличие номеров до которых еще нужно дозвонится, если таковых нет то отключить таску и отправить отчет.
        $activeTask = $this->db->select ("scheduleid from `schedule` where `status`=2");
        $this->log->debug($activeTask);
        if($activeTask != null){
            foreach ($activeTask as $key=>$scheduleid){
                $this->log->info("Task with id ".$scheduleid." in progress");
                $countInprogressNumbers = $this->db->select(" count(*) from schedule as s,dial as d where
                    s.scheduleid = d.scheduleid AND s.status=2 AND d.status=0 AND (d.dialcount < 3 OR d.action=1)
                    AND s.scheduleid=".$scheduleid, false);

                $this->log->debug($this->db->query->last);
                $this->log->debug($countInprogressNumbers);
                if($countInprogressNumbers == 0 ){
                    $this->db->update("schedule", array("status" => 3), "scheduleid=".$scheduleid);
                    $this->log->debug($this->db->query->last);
                    $this->createReport($scheduleid);
                }
            }
        }
    }

    function createReport($taskid){
        $this->log->info("Creating report for task id ".$taskid);
        $report = new Report($this->config, $taskid);
        $filename = $report->makeReport();
        //$this->log->info("Sending report file :'".$filename."'");
        $this->sendemail($taskid, $filename);
    }

    function addNumberGroupByTask($tasks){
        foreach ($tasks as $key=>$taskArray){
            $edlvWhere="";
            $this->log->info("Adding taskid = '".$taskArray['scheduleid']."'");
            if($taskArray['voicefilename']=="edelveys") {
                $edlvWhere=" AND `edelveys`=1";
                $this->log->info("Selecting special list for edelveys , taskid='".$taskArray['scheduleid']."'");
            }
            $newNumbers = $this->db->select("* from `phonenumbers` where `groupid` = ".$taskArray['groupid'].$edlvWhere);
            $this->log->info("Adding '".count($newNumbers)."' new numbers");
            $this->log->debug($newNumbers);
            if($newNumbers != null){
                $this->db->update("schedule", array("status" => 2), "scheduleid=".$taskArray['scheduleid']);
                $this->log->debug($this->db->query->last);
                foreach($newNumbers as $numberid => $numberData){
                    $this->db->insert("dial", array(
                        "groupid" => $taskArray['groupid'],
                        "phonenumber" => $numberData['phone'],
                        "status" => 0,
                        "scheduleid" => $taskArray['scheduleid'],
                        "dialcount" => 0,
                        "action" => 0,
                        "voicerecord" => $taskArray['voicefilename']
                    ));
                    $this->log->debug($this->db->query->last);
                }
            }else{
                $this->db->update("schedule", array("status" => 3), "scheduleid=".$taskArray['scheduleid']);
                $this->log->debug($this->db->query->last);

                $this->log->error("Have no any numbers for this task");
            }
        }
    }

    public function process(){
        $this->checkForCompleteTask();
        $this->checkNewTask();
        //die;
        $newNumbers = $this->checkNumbers();
        $this->dial($newNumbers);
    }

    function dial($numbers){
        $this->getConnection();
        $amiOriginateConfig= array(
            //'Channel' => 'local/12345678@from-trunk',
            "Exten" => "s",
            "Context" => "ringout_play",
            "CallerID" => 0
        );
        $callsCount=0;
        foreach ($numbers as $id=>$numberArray){
            $amiOriginateConfig['Exten'] = $numberArray['phonenumber'];
            $amiOriginateConfig['Channel'] = "local/".$numberArray['phonenumber']."@ringout";
            $amiOriginateConfig['CallerID'] = $numberArray['groupid'];
            $amiOriginateConfig['Variable'] = array(
                "__dialid" => $numberArray['dialid'],
                "__voicerecord" => $numberArray['voicerecord']
            );

            $originate = $this->ami->Originate($amiOriginateConfig);
            $this->log->debug($originate);
            fputs($this->socket, $originate);
            $this->db->update("dial",array('action' => 1, 'dialcount' => $numberArray['dialcount'] + 1), "dialid=".$numberArray['dialid']);
            $this->log->debug($this->db->query->last);
            $callsCount++;
            if($callsCount == $this->config['maxMakeCallsInOneStep']){
                $this->log->info("Max calls in the step is ".$this->config['maxMakeCallsInOneStep'].", breaking proccess. Sleeping for a ".$this->config['sleepTimeCallOriginate']." second");
                sleep($this->config['sleepTimeCallOriginate']);
                $this->log->info("Sleep done...");
                $callsCount=0;
            }

        }
    }

    public function getConnection()
        {
            $this->socket = fsockopen($this->config['manager_host'], $this->config['manager_port'], $errno, $errstr, 10);
            $this->log->info($this->socket, "socket");

            if (!$this->socket) {
                echo "$errstr ($errno)\n";
                $this->log->error("$errstr ($errno)");
                die;
            } else {
                $this->log->info("start main module");
                date_default_timezone_set('Europe/Moscow');

                $login_data = array(
                    "UserName" => $this->config['manager_login'],
                    "Secret" => $this->config['manager_password']
                );
                $login = $this->ami->Login($login_data);
                $this->log->info($login, "Authentication");

                fputs($this->socket, $login);
                $access = true;
                $event ="";
                while ($access) {
                    $data1 = fgets($this->socket);
                    if ($data1 == "\r\n") {
                        $evar = $this->ami->AmiToArray($event);
                        if (isset($evar['Response'])) {
                            switch ($evar['Response']) {
                                case "Success":
                                    $this->log->debug(print_r($evar, true), "ResponseAuthenticationSuccess");
                                    $access = false;
                                    break;
                                case "Error":
                                    $this->log->debug(print_r($evar, true), "ResponseAuthenticationError");
                                    if ($evar['Message'] == "Authentication failed") {
                                        $this->log->error("Authentication failed", "ResponseAuthentication");
                                        die;
                                    }
                                    break;
                            }
                        }
                    }
                    $event .= $data1;
                    $last = $data1;
                }
                $event = "";
            }
        }


    function checkNumbers(){
        $inDialCall = $this->db->select ("count(*) from `dial` where `action` = 1", false );
        $this->log->info("Concurrent calls = ".$inDialCall);
        $limit = $this->config['maxConcurrentCalls'] - $inDialCall;
        $this->log->info("Select new number = ".$limit);
        $newNumbers = $this->db->select("* FROM `dial` WHERE `status` = 0 AND `action` = 0 AND `dialcount` < 3 ORDER BY `dialcount` ASC LIMIT ".$limit);
        $this->log->debug($this->db->query->last);
        //$this->log->debug($newNumbers);
        if($newNumbers == null){
            $this->log->info("Have no any number for dial. Exit");
            die;
        }
        return $newNumbers;
    }
}
?>