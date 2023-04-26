<?php

use Marwa\Application\Migrations\AbstractSeeder;
use Marwa\Application\Facades\DB;

class PrefSeeder extends AbstractSeeder
{
	public function run() : void
	{
		DB::table('preference')->insert([
			                             'user_id' => 1
		                             ]);
	}
}
