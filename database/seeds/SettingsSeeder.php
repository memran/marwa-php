<?php
	
	use Marwa\Application\Facades\DB;
	use Marwa\Application\Migrations\AbstractSeeder;
	
	class SettingsSeeder extends AbstractSeeder {
		
		public function run() : void
		{
			DB::table('cms_settings')->insert([
				                                  ['site_key' => 'site_url','site_value'=> 'http://acbaforum.org'],
				                                  ['site_key' => 'site_title','site_value'=> 'Welcome'],
				                                  ['site_key' => 'site_desc','site_value'=> 'Welcome to ACBA'],
				                                  ['site_key' => 'site_logo','site_value'=> 'logo.png'],
				                                  ['site_key' => 'site_logo_alt','site_value'=> 'Logo Txt'],
				                                  ['site_key' => 'site_fb','site_value'=> ''],
				                                  ['site_key' => 'site_youtube','site_value'=> ''],
				                                  ['site_key' => 'site_linkedin','site_value'=> ''],
				                                  ['site_key' => 'site_twitter','site_value'=> ''],
				                                  ['site_key' => 'site_ga','site_value'=> ''],
				                                  ['site_key' => 'site_google_site_key','site_value'=> ''],
				                                  ['site_key' => 'site_alexa_site_key','site_value'=> ''],
				                                  ['site_key' => 'site_bing_site_key','site_value'=> '']
			                                  ]);
		}
	}
