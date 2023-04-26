<?php
	
	use Marwa\Application\Migrations\AbstractMigration;
	
	class User extends AbstractMigration {
		
		/**
		 * @throws Exception
		 */
		public function up() : void
		{
			$this->table('user')
				->id()
				->strings("name")
				->strings('username')
				->strings('password')
				->strings('email')
				->boolean('active')
				->rememberToken()
				->timestamps()
				->create();
		}
		
		/**
		 *
		 */
		public function down() : void
		{
				$this->table('user')->drop();
		}
	}
