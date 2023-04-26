<?php

	
	namespace App\Commands;
	
	use Marwa\Application\Commands\AbstractCommand;
	
	class DemoCommand extends AbstractCommand {
		
		//this is command name
		var $name = "demo:greet";
		
		//this is description for command
		var $description = "This command will print current date and time";
		
		//this is help for command
		var $help = "This command allows you to show current date.";
		
		
		/**
		 * [__construct description]
		 */
		public function __construct()
		{
			parent::__construct();
		}
		
		public function handle() : void
		{
			$this->println("Welcome to MarwaPHP");
		}
		
	}
