<?php
    require_once ("Classes/PHPMailer-master/class.phpmailer.php");
    $mail['theme']="Тема письмо - отчет";
    $mail['body']="Тело письма отчет содержиться в прикрепленном файле";
    $mail['from']="sols@sarfsin.ru";
    $operatordetail['mail']="danubise@gmail.com";

    $email = new PHPMailer();
    $email->CharSet = 'UTF-8';
    $email->From      = $mail['from'];
    $email->FromName  = $mail['from'];
    $email->Subject   = $mail['theme'];
    $email->Body      = $mail['body'];
    $mails=explode(",",$operatordetail['mail']);
    foreach($mails as $emailaddress) {
        $email->AddAddress($emailaddress);
    }
    $groupName = "Тестовое название файла отчета";
    $filename = "test.xls";
    $attachedFileName = iconv(mb_detect_encoding($groupName, mb_detect_order(), true), "UTF-8", $groupName);
    $email->AddAttachment( $filename , $attachedFileName.".xls" );
    if(!$email->Send()){
        echo "Message could not be sent. <p>";
        echo "Mailer Error: " . $email->ErrorInfo;
        $update = array(
            "send" => "2",
            "sentdetail" => "Mailer Error: " . $email->ErrorInfo
        );
        //$this->db->update("b_invoicemain",$update, "`invoiceid` = '" . $invoice['maindata']['invoiceid'] . "'");
        echo "Message has not sent";
        //die;
    }else {
        $update = array(
            "send" => "1",
            "sentdetail" => "Message has been sent successful to ". $operatordetail['mail']
        );
        //$this->db->update("b_invoicemain", $update, "`invoiceid` = '" . $invoice['maindata']['invoiceid'] . "'");
        echo "Message has been sent ";
        echo $operatordetail['mail'] . " sent <br>";
    }
?>