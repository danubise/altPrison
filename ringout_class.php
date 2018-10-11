<?php
/*
 * danubise@gmail.com
 * skype:danubise
 */
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
            $setvalue = array('Status' => 1);
        }else{
            $setvalue = array('Status' => 2);
        }
        $this->db->update( "t_BroadsCastSession", $setvalue, "Id=".$dialid);
        $this->log->debug($this->db->query->last);
        $this->sendSMS($dialid);
        return;
    }

    function sendSMS($sessionID){
        $smsText = $this->db->select("bcsc.SMSText as SMSText  
        FROM `t_BroadsCastSession` AS bcs, `t_BroadCastSchedule` AS bcsc 
        WHERE bcs.Id = '".$sessionID."' AND bcs.BroadCastScheduleId = bcsc.Id AND bcs.SMSSend = 0", false);
        $this->log->debug("Try to send sms for sessionID " . $sessionID." smsText = '".$smsText."'");
        if(trim($smsText)  != ""){
            echo "SEND SMS FUNCTION";
            $this->db->update("t_BroadsCastSession", array("SMSSend" => 1),"Id='".$sessionID."'");
        }else{
            //an error
            $this->log->error("Please check sms text for sessionID :" . $sessionID);
            $this->db->update("t_BroadsCastSession", array("SMSSend" => 3),"Id='".$sessionID."'");
        }
    }

    function checkNewTask(){
    // status 1 - new, 2 -  in progress, 3 - done
        $this->db->query('SET NAMES "utf8"');
        $currentDateTime = date("Y-m-d H:i:s");
        $newTasks = $this->db->select("* FROM `t_BroadCastSchedule` 
                                            WHERE `StartDateTime` < '".$currentDateTime."' AND
                                                    `EndDateTime` > '".$currentDateTime."'");
        $this->log->debug($this->db->query->last);
        $this->log->debug($newTasks);
        if($newTasks != null){
            $this->log->info("Adding new task '".count($newTasks)."'");
            $this->addNumberGroupByTask($newTasks);
        }else{
            $this->log->info("Have no new task");
        }
    }

    function addNumberGroupByTask($tasks){
        foreach ($tasks as $key=>$taskArray){
            $newNumbers = $this->db->select("* from `t_ContactList`");
            $this->log->info("Adding '".count($newNumbers)."' new numbers");
            $this->log->debug($newNumbers);

            if($newNumbers != null){
                foreach($newNumbers as $numberid => $numberData){
                    $this->db->insert("t_BroadsCastSession", array(
                        "ContactId" => $numberData['Id'],
                        "BroadCastScheduleId" => $taskArray['Id'],
                        "status" => 0,
                        "SMSSend" => 0
                    ));
                    $this->log->debug($this->db->query->last);
                }
            }else{
                $this->log->error("We have no any numbers for this task");
                die;
            }
        }
    }

    public function process(){
        $this->checkNewTask();
        $newNumbers = $this->checkNumbers();
        $this->dial($newNumbers);
    }

    function dial($numbers){
        $this->getConnection();
        $amiOriginateConfig= array(
            "Exten" => "s",
            "Context" => "ringout_play",
            "CallerID" => 0
        );
        foreach ($numbers as $id=>$numberArray){
            $amiOriginateConfig['Exten'] = $numberArray['Phone'];
            $amiOriginateConfig['Channel'] = "local/".$numberArray['Phone']."@ringout";
            $amiOriginateConfig['CallerID'] = $this->config['CallerID'];
            $amiOriginateConfig['Variable'] = array(
                "__dialid" => $numberArray['Id'],
                "__voicerecord" => $numberArray['RecordingFile']
            );

            $originate = $this->ami->Originate($amiOriginateConfig);
            $this->log->debug($originate);
            fputs($this->socket, $originate);
        }
    }

    public function getConnection()
        {
            $this->socket = fsockopen($this->config['manager']['host'], $this->config['manager']['port'], $errno, $errstr, 10);
            $this->log->info($this->socket, "socket");

            if (!$this->socket) {
                echo "$errstr ($errno)\n";
                $this->log->error("$errstr ($errno)");
                die;
            } else {
                $this->log->info("start main module");

                $login_data = array(
                    "UserName" => $this->config['manager']['login'],
                    "Secret" => $this->config['manager']['password']
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
        $inDialCall = $this->db->select ("count(*) FROM `t_BroadsCastSession` WHERE `Status` = 0", false );
        $this->log->info("Concurrent calls = ".$inDialCall);
        $newNumbers = $this->db->select("bcs.Id AS Id, bcs.Status AS Status, cl.Phone AS Phone, bcsc.SMSText as SMSText , 
        bcsc.RecordingFile as RecordingFile 
        FROM `t_BroadsCastSession` AS bcs, `t_ContactList` AS cl, `t_BroadCastSchedule` AS bcsc 
        WHERE bcs.Status = 0 AND bcs.ContactId = cl.Id AND bcs.BroadCastScheduleId = bcsc.Id AND bcs.SMSSend = 0");
        $this->log->debug($this->db->query->last);
        $this->log->debug($newNumbers);
        if($newNumbers == null){
            $this->log->info("Have no any number for dial. Exit");
            die;
        }
        return $newNumbers;
    }
}
?>