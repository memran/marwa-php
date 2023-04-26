<?php
	
	use Marwa\Application\Exceptions\FileNotFoundException;
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Privacy extends AbstractMigration {
		
		/**
		 * @throws FileNotFoundException
		 */
		public function up() : void
		{
			$this->table('privacy')
				->id()
				->bigInteger('user_id')
				->boolean("privacy_mobile_show")
				->boolean("privacy_email_show")
				->boolean("privacy_image_show")
				->timestamps()
				->create();
			//make relationship with user table
			$this->table('privacy')->foreign('user_id', 'user', 'id', ['delete' => 'CASCADE']);
			
		}
		
		public function down() : void
		{
			$this->table('privacy')->drop();
		}
	}
