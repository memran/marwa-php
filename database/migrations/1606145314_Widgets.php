<?php
	
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Widgets extends AbstractMigration {
		
		public function up() : void
		{
			$this->table('cms_widgets')
				->id()
				->strings('name')
				->text('content')
				->create();
		}
		
		public function down() : void
		{
			$this->table('cms_widgets')->drop();
		}
	}
