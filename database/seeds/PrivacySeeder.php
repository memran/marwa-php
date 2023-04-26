<?php

use Marwa\Application\Migrations\AbstractSeeder;
use Marwa\Application\Facades\DB;

class PrivacySeeder extends AbstractSeeder
{
	public function run() : void
	{
			DB::table('privacy')->insert([
				'user_id' => 1
			                             ]);
	}
}
