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
    private $groupType = null;

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
        $this->SelectMessageType();
        $this->SaveSchedule();
    }

    public function SaveSchedule(){
        $this->log->info("Saving task");
        $this->db->insert("schedule", array(
          "groupid" => $this->groupid,
          "voicefilename" => $this->config["notification"][$this->groupType][$this->messageType],
          "status" => 1
        ));
        $this->log->debug($this->db->query->last);

        $ringout = new Ringout($this->config);
        $ringout->process();
    }

    public function GetPinCode(){

        $this->log->info("Please enter pincode");
        $this->agi->stream_file("en/privetstvie","#");
        $try=0;
        do{
            $result = $this->agi->get_data('vveditepinkod', $this->config['pincodeTimeOut'], 4);
            $pincode = trim($result['result']);
            //Проверка пинкод в базе
            $dbCheck = $this->db->select("`groupid` from `pincode` where `pincode`='".$pincode."'", false);
            $this->log->debug($this->db->query->last);
            $this->log->info("Group id is '".$dbCheck."'");
            if($dbCheck != null ){
                $this->groupType = $this->db->select("`grouptype` from `pincode` where `pincode`='".$pincode."'", false);
                $this->log->info("Group type is '".$this->groupType."'");
                $this->groupid = $dbCheck;
                $this->log->info("Pincode successful");
                break;
            }
            //ввден не верный пинкод, повторить попытку
            $this->agi->stream_file("neverniypinkod","#");
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
            $this->log->debug("Play menu record : '".$this->config['messages'][$this->groupType]."'");
            $answer = $this->agi->get_data($this->config['messages'][$this->groupType], 5000 , 1);
            $this->log->debug($answer);
            $this->log->info("GroupType = '".$this->groupType." Answer = '".$answer['result']."'");
            $this->log->debug($this->config["notification"][$this->groupType][$answer]);

            if( isset($this->config["notification"][$this->groupType][$answer['result']])){
                $this->messageType = $answer['result'];
                $this->log->info("Selected notification number '".$this->messageType."'");
                break;
            }

            if ($this->messageType != null ){
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