<?php
	
	use Marwa\Application\Exceptions\FileNotFoundException;
	use Marwa\Application\Migrations\AbstractMigration;
	
	class Preference extends AbstractMigration {
		
		/**
		 * @throws FileNotFoundException
		 * @throws Exception
		 */
		public function up() : void
		{
			$this->table('preference')
				->id()
				->bigInteger('user_id')
				->boolean("pref_email")
				->boolean("pref_mobile")
				->boolean("pref_job_offer")
				->boolean("pref_consultancy")
				->boolean("pref_mentor")
				->boolean("pref_mentee")
				->boolean("pref_call_time")
				->boolean("pref_holidays")
				->timestamps()
				->create();
			
			//make relationship with user table
			$this->table('preference')->foreign('user_id', 'user', 'id', ['delete' => 'CASCADE']);
		}
		
		/**
		 *
		 */
		public function down() : void
		{
			$this->table('preference')->drop();
		}
	}
