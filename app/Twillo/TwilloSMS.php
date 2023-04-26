<?php
	
	namespace App\Twillo;
	
	use Marwa\Application\Notification\SMS\SMSBuilder;
	use Twilio\Rest\Client;
	
	class TwilloSMS extends SMSBuilder {
		
		public $twilio;
		
		public function __construct( $to, $from, $body )
		{
			$this->setTo($to);
			$this->setFrom($from);
			$this->setBody($body);
			$sid = "AC98dc7c175bbe414087ae7837cda6eac0";
			$token = "22e84b5bbf54542ed0a43e1cc9f0f761";
			$this->twilio = new Client($sid, $token);
		}
		
		public function send()
		{
			$message = $this->twilio->messages
				->create($this->getTo(), // to
				         [
					         "from" => $this->getFrom(),
					         "body" => $this->getBody()
				         ]
				);
			
			return $message;
		}
		
		
	}
