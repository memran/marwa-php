<?php
	
	namespace App\Listeners;
	
	use Marwa\Application\Events\AbstractListener;
	
	class LogoutListener extends AbstractListener {
		
		public function handle( $event, $param = null )
		{
			logger('User Logged Out!!', $param);
		}
	}

