<?php
namespace App\Broadcasts;
use Marwa\Application\Notification\Broadcasts\BroadcastBuilder;

class Pusher extends BroadcastBuilder {
	public $pusher;

	public function __construct( $channel, $event )
	{
		$this->setChannel($channel);
		$this->setEvent($event);

		$options = [
			'cluster' => 'ap2',
			'useTLS' => true
		];
		$this->pusher = new \Pusher\Pusher(
			'3c410e7a81d1f478ef45',
			'639fa740a7bc9b250390',
			'598805',
			$options
		);
	}

	public function push()
	{
		$data['message'] = 'Hello World! It is MarwaPHP';

		return $this->pusher->trigger($this->getChannel(), $this->getEvent(), $data);
	}


}
