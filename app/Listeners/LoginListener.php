<?php
namespace App\Listeners;
use Marwa\Application\Events\AbstractListener;

class LoginListener extends AbstractListener {

	public function handle( $event, $param = null )
	{
		logger('User Logged In', $param);
	}
}

