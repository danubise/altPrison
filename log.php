<?php
    /**
     * User: slava
     * Date: 12.05.2018
     * Time: 13:56
     * Phone: 89878130785
     * Email: danubise@gmail.com
     * Skype: danubise
     */
    /*
     * config
     *  log_write   file/both/null (file) запись в файл или выводи на монитор
     *  log_file    имя файла в который будет вестись запись данных прим.test.log
     *  log_differentfiles true/false (false) возможность записывать в разный файлы, в зависимости от Level прим. info.test.log
     *  log_timetype unix/normal (normal) формат времени в лог файле
     *  log_active  true/false (true) включить\выключить логирование
     *  log_level   all,info,warning,error,debug перечисленные уровни логирования
     *  log_color   true/false (false) вывод логив в цветах
     *
     * пример использования
     * 1)   $log->info("information") //простой вывод информации
     * результат
     *      2015-10-09 15:18:16 info information
     * 2)   $log->info("text","123"); //запись с информации с определенным ключем для фильтрации в лог файле
     *      2015-10-09 15:31:13 info[123] txt
     */
    class Log {

        private $LogMessage= "";
        private $FileName="file.log";
        private $Write="file";
        private $TimeType="normal";
        private $Active=true;
        private $Level="all";
        private $DifferentFile=false;
        private $Color=false;
        private $GlobalIndex=""; // указывает что лог одного процесса
        //private $LevelTypes = array("info","warning","error","debug");

        public function __construct($config=""){
            if(is_array($config)){
                if(isset($config['log_file'])){
                    if(trim($config['log_file']) !="") {
                        $this->FileName = $config['log_file'];
                    }
                }
                if(isset($config['log_write'])){
                    $this->Write=$config['log_write'];
                }
                if(isset($config['log_differentfiles'])){
                    $this->DifferentFile=$config['log_differentfiles'];
                }
                if(isset($config['log_timetype'])){
                    $this->TimeType=$config['log_timetype'];
                }
                if(isset($config['log_active'])){
                    $this->Active=$config['log_active'];
                }
                if(isset($config['log_level'])){
                    $this->Level=$config['log_level'];
                }
                if(isset($config['log_color'])){
                    $this->Color=$config['log_color'];
                }
            }
            $this->LogMessage= new LogMessage();
        }
        public function SetGlobalIndex($txt){
            if(isset($txt)){
                $txt=trim($txt);
                if($txt!=""){
                    $this->GlobalIndex=$txt." ";
                }
            }
        }

        public function info($txt,$key=""){
            if($this->Level=="all" || strstr($this->Level,"info")) {
                if($this->Color) {
                    $this->LogMessage->type = "\e[32minfo\e[0m";
                }else{
                    $this->LogMessage->type = "info";
                }
                $this->Format($txt, $key);
            }
        }
        public function warning($txt,$key=""){
            if($this->Level=="all" || strstr($this->Level,"warning")) {
                if($this->Color) {
                    $this->LogMessage->type = "\e[33mwarning\e[0m";
                }else{
                    $this->LogMessage->type = "warning";
                }
                $this->Format($txt, $key);
            }
        }
        public function error($txt,$key=""){
            if($this->Level=="all" || strstr($this->Level,"error")) {
                if($this->Color) {
                    $this->LogMessage->type = "\e[31merror\e[0m";
                }else{
                    $this->LogMessage->type = "error";
                }
                $this->Format($txt, $key);
            }
        }
        public function debug($txt,$key=""){
            if($this->Level=="all" || strstr($this->Level,"debug")) {
                if($this->Color) {
                    $this->LogMessage->type = "\e[4mdebug\e[24m";
                }else{
                    $this->LogMessage->type = "debug";
                }
                $this->Format($txt, $key);
            }
        }
        private function Format($txt,$key){
            if(!$this->Active){
                return;
            }
            $this->LogMessage->txt=$this->CheckObject($txt);
            $this->LogMessage->key=$key;
            $this->Write();
        }
        private function CheckObject($txt){
            if(!isset($txt) || $txt==null){
                return "Null";
            }
            if(is_string($txt)){
                return trim($txt);
            }
            return print_r($txt, true);

        }
        private function LogShow(){
            if($this->TimeType=="unix"){
                $timetype= microtime(true);
            }else {
                $timetype = date("Y-m-d H:i:s");
            }
            $txtarr=explode("\n",$this->LogMessage->txt);
            $log="";
            $key="";
            if($this->LogMessage->key!=""){
                $key="[".$this->LogMessage->key."]";
            }
            foreach($txtarr as $line){
                $log.=$timetype." ".$this->GlobalIndex.$this->LogMessage->type.$key." ".$line."\n";
            }
            return $log;
        }
        private function Write(){
            $FileName=$this->FileName;
            if($this->DifferentFile){
                $FileName=$this->LogMessage->type."_".$FileName;
            }
            $logLine = $this->LogShow();

            if ($this->Write == "file") {
                file_put_contents($FileName, $logLine, FILE_APPEND);
            } else if ($this->Write == "both"){
                file_put_contents($FileName, $logLine, FILE_APPEND);
                echo $logLine;
            }else {
                echo $logLine;
            }
        }
    }
    class LogMessage{
        public $type;
        public $txt;
        public $key;
        public $line;
        public function __construct($type="Info",$txt="",$key=""){
            $this->type=$type;
            $this->txt=$txt;
            $this->key=$key;
        }
    }