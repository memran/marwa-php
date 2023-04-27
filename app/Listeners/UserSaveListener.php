<?php
namespace App\Listeners;

use Marwa\Application\Events\AbstractListener;

class UserSaveListener extends AbstractListener {

	public function handle( $event, $param = null )
	{
		logger('User saved successfully', $param);
	}
}

