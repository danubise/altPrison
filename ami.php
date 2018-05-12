<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 22.05.15
 * Time: 16:26
 */
class Ami
{
    public $default =array(
        "Timeout"=>30000,
        "Priority"=>1
    );
    public function setVar($data){
        $action="Action: Setvar\r\n";
        $action.="Channel: ".$data['Channel']."\r\n";
        /*
         * Variable: AtestVariable
Value: This is now set
         */
        $action.="Variable: ".$data['Variable']."\r\n";
        $action.="Value: ".$data['Value']."\r\n";
        $action.="\r\n";
        return $action;

    }

    public function Ping(){
        $action="Action: Ping\r\n\r\n";
        return $action;

    }

    public function Login($data){
        $action="Action: Login\r\n";
        if(isset($data['UserName']) && strlen(trim($data['UserName']))>0) {
            $action .= "UserName: " . $data['UserName'] . "\r\n";
        }else{
            return "Login error data \n".print_r($data,true);
        }
        $action.="Secret: ".$data['Secret']."\r\n\r\n";
        return $action;

    }
    public function Originate($data){

        $action="Action: Originate\r\n";
        $action.="Channel: ".$data['Channel']."\r\n";
        $action.="Exten: ".$data['Exten']."\r\n";
        $action.="Context: ".$data['Context']."\r\n";
        if(isset($data['Priority']) && intval($data['Priority'])>0) {
            $priority=$data['Priority'];
        }else
        {
            $priority=$this->default["Priority"];
        }
        $action .= "Priority: " .$priority. "\r\n";
        if(isset($data['CallerID'])){
            $action.="CallerID: ".$data['CallerID']."\r\n";
        }


        if(isset($data['Timeout']) && intval($data['Timeout'])>1000) {
            $timeout=$data['Timeout'];
        }else{
            $timeout=$this->default["Timeout"];
        }
        $action .= "Timeout: " . $timeout . "\r\n";
        if(isset($data['ActionId'])) {
            $action .= "ActionId:  " . $data['ActionId'] . "\r\n";
        }

        if(isset($data['Variable'])){
            if(is_array($data['Variable'])){
            foreach($data['Variable'] as $key=>$value){
                $action.="Variable: ".$key."=".$value."\r\n";
            }
            }
        }
        $action.="Async: true\r\n\r\n";
        return $action;
    }
    public function AmiToArray($data)
    {
//принимает ответ от ами антерфейса
//позрващает асоциативный массив
        $t_array=array();
        foreach(explode("\n",$data) as $key=>$value){
            if(trim($value)!=""){
                $a_line=explode(":",$value);
                $k=trim($a_line[0]);
                if(isset($a_line[1]))
                {
                    $v=trim($a_line[1]);
                }
                else{
                    $v="";
                }
                $t_array[$k]=$v;
            }
        }
        return $t_array;
//end AmiToArray
    }
    public function AmiEventToArray($event){
        // $t_event=;
        $eventr=array();
        foreach (explode("\n",$event) as $key=>$value){
            if(trim($value)!=""){
                $line=explode(":",$value);
                if(isset($line[1])) {
                    $eventr[trim($line[0])] = trim($line[1]);
                }else{
                    $eventr[trim($line[0])] = "";
                }
            }
        }
        return $eventr;
    }
    public function GetChannel($data){
// Local/1007@from-internal-00000006;1
        if(is_array($data)){
            $channel=explode(";",$data['Channel']);
        }else{
            $channel=explode(";",$data);
        }

        return trim($channel[0]);
    }
    public function getchanneldetail($channel){
//[Channel] => Local/784523125485@checker-000001ff;2
        $result=array();
        $t1=explode("/",$channel);
        $result['proto']=$t1[0];
        $t2=explode("@",$t1[1]);
        $result['number']=$t2[0];
        $t3=explode(";",$t2[1]);
        $result['channelid']=$t3[0];
        $result['channelnumber']=$t3[1];
        $result['original'] =$channel;
        $result['channelmain']=$result['number']."@".$result['channelid'];
        $result['channelfull']=$result['proto']."/".$result['channelmain'];
        /*
Array
(
    [proto] => Local
    [number] => 784523125485
    [channelid] => checker-000001ff
    [channelnumber] => 2
    [original] => Local/784523125485@checker-000001ff;2
    [channelmain] => 784523125485/checker-000001ff
)
         */
        return $result;
    }
    public function GetNumberFromChannelId($data){
        if(is_array($data)){
            $channel=explode(";",$data['Channel']);
        }else{
            $channel=explode(";",$data);
        }
        $t1=explode("@",$channel[0]);
        $number=trim(explode("/",$t1[0]));

        return $number[1];
    }
}
