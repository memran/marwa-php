<?php
	
	namespace App\Listeners;
	
	use Marwa\Application\Events\AbstractListener;
	
	class RegisterListener extends AbstractListener {
		
		public function handle( $event, $param = null )
		{
			logger('i am from event', $param);
		}
	}
