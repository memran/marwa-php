<?php
	
	
	namespace App\Jobs;
	
	use Marwa\Application\Jobs\AbstractListener;
	
	class EmailSend extends AbstractListener {
		
		public function handle( array $params = [] ) : void
		{
			logger('Email Send 1', $params);
		}
	}

