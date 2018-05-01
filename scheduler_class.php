<?php
class Scheduler{
    private $agi;
    private $db;
    private $log;
    private $config;
    private $messageType = null;
    private $hour = null;
    private $minute = null;
    private $setTimeTry = 0;
    private $groupid = null;

    public function __construct($config=""){
        $this->config = $config;
        $this->db = new db($config['mysql']);
        $this->agi = new AGI();
        $this->log = new Log($config);
        $this->log->info( "Start" );

    }

    public function Process(){
        $this->log->info( "Incomming call from :" .$this->agi->request['agi_callerid']);

        $this->log->SetGlobalIndex($this->agi->request['agi_callerid']);
        $this->agi->Answer();
        $this->GetPinCode();
        $this->setMessageType();
        $this->SaveSchedule();
    }

    public function SaveSchedule(){
        $this->log->info("Save schedule function");
        $result = $this->agi->get_data('en/demo-congrats', $this->config['saveScheduleTimeOut'], 1);
        $saveAnswer = $result['result'];
        if($saveAnswer == 1) {
            $this->log->info("Saving schedule");
            $this->addSchedule();
            $this->Stop("The schedule was successful saved");
        }else{
            //$this->log->info("The schedule was canceled");
            $this->Stop("The schedule was canceled from main menu");
        }

    }

    private function addSchedule(){
        $this->db->insert("schedule", array(
          "groupid" => $this->groupid,
          "time" => $this->hour.":".$this->minute.":00",
          "voicefilename" => $this->config["messages"][$this->messageType],
          "status" => 1
        ));
        $this->log->debug($this->db->query->last);
    }

    public function setMessageType(){
        $this->SelectMessageType();
        $this->setTime();
    }

    public function setTime(){
        $this->setTimeTry++;
        if($this->setTimeTry == $this->config['setTimeRetryCount']){
            $this->Stop("Set time count limit");
        }

        $this->log->info("Please set time");

        if($this->SetHour()){

            if(!$this->setMinute()){
                $this->setTime();
            }
        }else{
            $this->setMessageType();
        }


    }
    private function setMinute(){
        $try=0;
        do{
            $this->log->info("Set value minute");
            $result = $this->agi->get_data('en/demo-congrats', 3000, 2);
            $minute = trim($result['result']);
            $this->log->debug($minute);
            if($minute >= 0 && $minute <= 59 ){
                $this->minute = $this->lessTen($minute);
                return true;
            }elseif($minute == "#"){
                $this->info("Set minute was canceled");
                return false;
            }
            $this->log->error("Wrong minute value, must be from 0 to 59");
            $this->agi->stream_file("en/beeperr","#");
            $try ++;
            if($try == $this->config['setMinuteRetryCount']){
                $this->Stop("Timeout while set value minute");
            }
        } while(true);
    }

    private function lessTen($digit){
        if($digit < 10) {
            $digit ="0".$digit;
        }
        return $digit;
    }

    private function setHour(){
        $try=0;
        do{
            $this->log->info("Set value hour");
            $result = $this->agi->get_data('en/demo-congrats', 3000, 2);
            $hour = trim($result['result']);
            $this->log->debug($hour);
            if($hour >= 0 && $hour <= 23 ){
                $this->hour = $this->lessTen($hour);
                return true;
            }elseif($hour == "#"){
                $this->info("Set hour was canceled");
                return false;
            }
            $this->log->error("Wrong hour value, must be from 0 to 23");
            $this->agi->stream_file("en/beeperr","#");
            $try ++;
            if($try == $this->config['setHourRetryCount']){
                $this->Stop("Timeout while set value hour");
            }
        } while(true);
    }

    public function GetPinCode(){

        $this->log->info("Please enter pincode");
        $this->agi->stream_file("en/demo-congrats","#");
        $try=0;
        do{
            $result = $this->agi->get_data('beep', $this->config['pincodeTimeOut'], 4);
            $pincode = trim($result['result']);
            //Проверка пинкод в базе
            $dbCheck = $this->db->select("`groupid` from `pincode` where `pincode`='".$pincode."'", false);
            $this->log->debug($this->db->query->last);
            $this->log->info("Group id is :".$dbCheck);
            if($dbCheck != null ){
                $this->groupid = $dbCheck;
                $this->log->info("Pincode successful");
                break;
            }
            //ввден не верный пинкод, повторить попытку
            $this->agi->stream_file("you-entered","#");
            $this->log->error("Wrong pincode");
            $try ++;
            if($try == $this->config['pincodeRetryCount']){
                $this->Stop("Incorrect pincode");
            }
        } while(true);

    }

    public function SelectMessageType(){
        $this->log->info("Select message type");
        $try=0;
        do{
            $try ++;
            $this->log->info("Try ".$try);
            foreach($this->config['messages'] as $key=>$messageVoiceFileName){
                $this->log->info("Menu item ".$messageVoiceFileName." key=".$key);
                $answer = $this->agi->get_data($messageVoiceFileName, 1000 , 1);
                $this->log->debug($answer);
                if( isset($this->config['messages'][$answer['result']] )){
                    $this->messageType = $answer['result'];
                    break;
                }

                $answer = $this->agi->say_digits($key);
                $this->log->debug($answer);
                if( isset($this->config['messages'][$answer['result']] )){
                    $this->messageType = $answer['result'];
                    break;
                }
            }
            if ($this->messageType != null ){
                break;
            }

            $answer = $this->agi->get_data('beep', 4000, 1);
            if( isset($this->config['messages'][$answer['result']] )){
                $this->messageType = $answer['result'];
                break;
            }

            if($try == $this->config['messageTypeRetryCount']){
                $this->Stop("Menu Message type timeout.");
            }

        }while(true);
    }


    public function Stop($message){
        $this->log->info("EXIT with :".$message);
        $this->agi->Hangup();
        die;
    }


}
?>