<?php
class Ringout{
    private $config=null;
    private $db=null;
    private $log=null;
    private $ami=null;

    public function __construct($config=""){
        $this->config = $config;
        $this->db = new db($config['mysql']);
        $this->log = new Log($config);
        $this->log->SetGlobalIndex("RINGOUT");
        $this->log->info( "Start Ringout service" );
        $this->ami = new Ami();
    }
    public function process(){
        $newNumbers = $this->checkNumbers();

    }
    function getAvailableTask(){
        //$tasks = $this->db->
    }

    function checkNumbers(){
        $inDialCall = $this->db->select ("count(*) from `dial` where `status`= 1", false );
        $this->log->info("Concurrent calls = ".$inDialCall);
        $limit = $this->config['maxConcurrentCalls'] - $inDialCall;
        $this->log->info("Select new number = ".$limit);
        $newNumbers = $this->db->select("* FROM `dial` WHERE `dialcount` < 3 ORDER BY `dialcount` ASC LIMIT ".$limit);
        $this->log->debug($this->db->query->last);
        $this->log->debug($newNumbers);
        if($newNumbers == null){
            $this->log->info("Have no any number for dial. Exit");
            die;
        }
        return $mewNumbers;
    }
}
?>