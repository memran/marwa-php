<?php
	
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Permission extends AbstractMigration {
		
		/**
		 * @throws Exception
		 */
		public function up() : void
		{
			$this->table('permission')
				->id()
				->strings("name")
				->strings('guard_name')
				->create();
			
			$this->table('role_has_permission')
				->bigInteger('permission_id')
				->bigInteger("role_id")
				->create();
			/**
			 * Make relation with role table
			 */
			$this->table('role_has_permission')->foreign('role_id', 'role', 'id',
			                                             ['delete' => 'CASCADE']);
			/**
			 * Make relation with permission table
			 */
			$this->table('role_has_permission')->foreign('permission_id', 'permission', 'id',
			                                             ['delete' => 'CASCADE']);
		}
		
		/**
		 *
		 */
		public function down() : void
		{
			$this->table('role_has_permission')->drop();
			$this->table('permission')->drop();
		}
	}
