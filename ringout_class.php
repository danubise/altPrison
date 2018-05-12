<?php
class Ringout{
    private $config=null;
    private $db=null;
    private $log=null;
    private $ami=null;
    private $socket=null;

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
        $this->dial($newNumbers);
    }

    function dial($numbers){
        $this->getConnection();
        foreach ($number as $id=>$numberArray){


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
        $inDialCall = $this->db->select ("count(*) from `dial` where `status`= 1", false );
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