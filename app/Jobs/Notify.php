<?php
	
	namespace App\Jobs;
	
	use Marwa\Application\Jobs\AbstractListener;
	
	class Notify extends AbstractListener {
		
		public function handle( array $params = [] ) : void
		{
			logger("it works from notify", $params);
			//throw new \Exception("Error From notify", 1);
		}
		
	}

