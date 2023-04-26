<?php
	
	namespace App\Mail;
	
	use Marwa\Application\Notification\Mailer\MailBuilder;
	
	class NewCustomer extends MailBuilder {
		
		public function __construct() { }
		
		/**
		 * @return NewCustomer|mixed
		 */
		public function build()
		{
			return $this->view('welcome')->subject('it is test email');
		}
		
	}
