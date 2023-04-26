<?php

	use Marwa\Application\Migrations\AbstractMigration;
	
	class Role extends AbstractMigration {
		
		/**
		 * @throws Exception
		 */
		public function up() : void
		{
			
			$this->table('role')
				->id()
				->strings('name')
				->strings('description')
				->create();
			
			$this->table('user_has_role')
				->bigInteger('user_id')
				->bigInteger('role_id')
				->create();
			
			$this->table('user_has_role')->foreign('user_id','user','id',['delete'=>'CASCADE']);
			$this->table('user_has_role')->foreign('role_id','role','id',['delete'=>'CASCADE']);
			
		}
		
		/**
		 *
		 */
		public function down() : void
		{
			$this->table('user_has_role')->drop();
			$this->table('role')->drop();
		}
	}
