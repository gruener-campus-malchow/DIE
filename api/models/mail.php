<?php
require_once('model.php');


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


//Load ClassFiles
require_once './models/phpmailer/PHPMailer.php';
require_once './models/phpmailer/SMTP.php';
require_once './models/phpmailer/Exception.php';


class mail extends model{

	public function create()
	{
		
		$post_json = json_decode(file_get_contents("php://input"));
		
		$emails = array();
		
		foreach ($post_json as $email)
		{
			$address = $email->address;
			$subject = $email->subject;
			$body = $email->body;
			
			array_push($emails, array($address, $subject, $body));
			
			//$this->sendMail($address, $subject, $body);
			
			
			
			
			
			
		}
		
		
		
		
		return $emails;
		//return $foo;
		
	}
	
	private function sendMail($address, $subject, $body)
	{

		//Create an instance; passing `true` enables exceptions
		$mail = new PHPMailer(true);

try {
			
			
			//Server settings
			$mail->SMTPDebug = SMTP::DEBUG_CONNECTION;                      //Enable verbose debug output
			$mail->isSMTP();                                            //Send using SMTP
			$mail->Host       = $this->config['EMAIL_SMTP'];                     //Set the SMTP server to send through
			//echo $mail->Host;
			$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
			$mail->Username   = $this->config['EMAIL_USERNAME'];                     //SMTP username
			$mail->Password   = $this->config['EMAIL_PASSWORD'];                               //SMTP password
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
			$mail->Port       = $this->config['EMAIL_PORT'];                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

			//Recipients
			$mail->setFrom($this->config['EMAIL_USERNAME'], 'Automatic SwapMeet Mailing System');
			$mail->addAddress($address);     //Add a recipient
			//$mail->addAddress('baldauf@gruener-campus-malchow.de');               //Name is optional
			//$mail->addReplyTo('info@example.com', 'Information');
			//$mail->addCC('cc@example.com');
			//$mail->addBCC('bcc@example.com');
			
			/*

			//Attachments
			$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
			$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
			
			*/

			//Content
			$mail->isHTML(true);                                  //Set email format to HTML
			$mail->Subject = $subject;
			$mail->Body    = $body;
			//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

			$mail->send();
			echo 'Message has been sent';
		} catch (Exception $e) {
			echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}		
		
	}
	
	
	

	
}
?>
