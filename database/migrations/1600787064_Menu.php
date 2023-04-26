<?php
	
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Menu extends AbstractMigration {
		
		public function up() : void
		{
			$this->table('menu')
				->id()
				->strings("name")
				->timestamps()
				->create();
			
			$this->table('menu_item')
				->id()
				->strings("label")
				->strings("link")
				->tinyInteger("type")
				->tinyInteger("sort")
				->tinyInteger("depth")
				->strings("class")
				->bigInteger("parent")
				->bigInteger("menu")
				->timestamps()
				->create();
			
		}
		
		public function down() : void
		{
			$this->table('menu')->drop();
			$this->table('menu_item')->drop();
		}
	}
