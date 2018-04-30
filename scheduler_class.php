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

    public function __construct($config=""){
        $this->config = $config;
        //$this->db = new DB($config);
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
        $this->agi->stream_file("en/demo-congrats","#");

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
            $this->agi->stream_file("en/demo-congrats","#");
            $result = $this->agi->get_data('beep', 3000, 4);
            $minute = $result['result'];
            $this->log->debug($hour);
            if($minute >= 0 && $minute <= 59 ){
                $this->minute = $minute;
                return true;
            }elseif($minute == "#"){
                $this->info("Set minute was canceled");
                return false;
            }
            $this->log->error("Wrong minute value, must be from 0 to 59");
            $try ++;
            if($try == $this->config['setMinuteRetryCount']){
                $this->Stop("Timeout while set value minute");
            }
        } while(true);
    }

    private function setHour(){
        $try=0;
        do{
            $this->log->info("Set value hour");
            $this->agi->stream_file("en/demo-congrats","#");
            $result = $this->agi->get_data('beep', 3000, 4);
            $hour = $result['result'];
            $this->log->debug($hour);
            if($hour >= 0 && $hour <= 23 ){
                $this->hour = $hour;
                return true;
            }elseif($hour == "#"){
                $this->info("Set hour was canceled");
                return false;
            }
            $this->log->error("Wrong hour value, must be from 0 to 23");
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
            $result = $this->agi->get_data('beep', 3000, 4);
            $keys = $result['result'];
            //Проверка пинкод в базе
            if($keys == "111" ){
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