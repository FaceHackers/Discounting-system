<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
 
class lib_mailer {
 
    var $mail;
 		public $Init = array("SetMail"=>'a@gmail.com',
 												 "SetPwd"=> 'sss',
 												 "SetName"=>'name');
    public function __construct()
    {
        require_once('PHPMailer/class.phpmailer.php');
        require_once('PHPMailer/class.smtp.php');
 
        
    }
    public function ReStart(){
    	// the true param means it will throw exceptions on errors, which we need to catch
        $this->mail = new PHPMailer(true);
 
        $this->mail->IsSMTP(); // telling the class to use SMTP

        $this->mail->IsHTML(true);
 
        $this->mail->CharSet = "utf-8";                  // 一定要設定 CharSet 才能正確處理中文
        $this->mail->SMTPDebug  = 0;                     // enables SMTP debug information
        $this->mail->SMTPAuth   = true;                  // enable SMTP authentication
        $this->mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
        $this->mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
        $this->mail->Port       = 465;                   // set the SMTP port for the GMAIL server
        $this->mail->Username   = $this->Init['SetMail'];	 // GMAIL username
        $this->mail->Password   = $this->Init['SetPwd'];       // GMAIL password
        $this->mail->AddReplyTo($this->Init['SetMail'], $this->Init['SetName']);
        $this->mail->SetFrom($this->Init['SetMail'], $this->Init['SetName']);
    }
    public function sendmail($to, $to_name, $subject, $body){
        try{
            $this->mail->AddAddress($to, $to_name);
 
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
 
            $this->mail->Send();
             //echo "Message Sent OK</p>\n";
            return true;
 
        } catch (phpmailerException $e) {
        	return $e->errorMessage(); 
           // echo $e->errorMessage(); //Pretty error messages from PHPMailer
        } catch (Exception $e) {
        	return $e->getMessage();
          //  echo $e->getMessage(); //Boring error messages from anything else!
        }
    }
}
 
/* End of file mailer.php */
 