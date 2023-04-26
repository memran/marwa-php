<?php
	
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Gallery extends AbstractMigration {
		
		public function up() : void
		{
			$this->table('gallery')
				->id()
				->strings('img_name')
				->strings('img_title')
				->strings('img_description')
				->strings('img_path')
				->strings('alt_text')
				->timestamps()
				->create();
		}
		
		public function down() : void
		{
			$this->table('gallery')->drop();
		}
	}
