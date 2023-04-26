<?php
	
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Category extends AbstractMigration {
		
		public function up() : void
		{
			//create category table
			$this->table('category')
				->id()
				->strings("name")
				->strings('slugs')
				->bigInteger("parent_id")
				->timestamps()
				->create();
		}
		
		public function down() : void
		{
			$this->table('category')->drop();
		}
	}
