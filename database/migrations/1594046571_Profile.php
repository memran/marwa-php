<?php
	
	use Marwa\Application\Exceptions\FileNotFoundException;
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Profile extends AbstractMigration {
		
		/**
		 * @throws FileNotFoundException
		 * @throws Exception
		 */
		public function up() : void
		{
			$this->table('profile')
				->id()
				->bigInteger('user_id')
				->strings("batch")
				->strings("roll")
				->strings("blood_group")
				->strings("mobile")
				->strings("position")
				->strings("company")
				->strings("city")
				->strings("country")
				->strings("facebook")
				->strings("linked_in")
				->strings("twitter")
				->strings("website", 200)
				->strings("profile_img", 255)
				->strings("about", 1000)
				->strings("hobbies")
				->timestamps()
				->create();
			
			/**
			 * make relationship with user table
			 **/
			$this->table('profile')->foreign('user_id', 'user', 'id', ['delete' => 'CASCADE']);
		}
		
		/**
		 *
		 */
		public function down() : void
		{
			$this->table('profile')->drop();
		}
	}
