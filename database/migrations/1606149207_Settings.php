<?php
	
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Settings extends AbstractMigration {
		
		public function up() : void
		{
			$this->table('cms_settings')
				->strings('site_key')
				->strings('site_value')
				->create();
		}
		
		public function down() : void
		{
			$this->table('cms_settings')->drop();
		}
	}
