<?php
	
	namespace Unit;
	
	use Exception;
	use Marwa\Application\App;
	use Marwa\Application\Logger;
	use PHPUnit\Framework\TestCase;
	use ReflectionClass;
	
	class  LoggerTest extends TestCase {
		
		
		/**
		 * @return App
		 */
		public function createApplication()
		{
			//APP START TIME
			defined('START_APP') or define('START_APP', microtime(true));
			defined('DS') or define('DS', DIRECTORY_SEPARATOR);
			defined('WEBROOT') or define('WEBROOT', dirname(__FILE__, 3));
			
			return new App(WEBROOT, true);
		}
		
		public function test_Create_Logger_Instance_SingleTon()
		{
			$logger1 = Logger::getInstance();
			$logger2 = Logger::getInstance();
			$this->assertEquals($logger1, $logger2);
		}
		
		public function test_log_file_name_set_get()
		{
			$logger1 = Logger::getInstance();
			self::callMethod($logger1, 'setLogFileName', ['application.log']);
			$output = self::callMethod($logger1, 'getLogFileName');
			$this->assertEquals('application.log', $output);
		}
		
		/**
		 * @param $obj
		 * @param $name
		 * @param array $args
		 * @return mixed
		 * @throws Exception
		 */
		public static function callMethod( $obj, $name, array $args = [] )
		{
			try
			{
				$class = new ReflectionClass($obj);
				$method = $class->getMethod($name);
				$method->setAccessible(true);
				
				return $method->invokeArgs($obj, $args);
			} catch ( \ReflectionException $e )
			{
				throw new Exception($e);
			}
			
		}
		
		public function test_cogger_channel_name_set_get()
		{
			$logger1 = Logger::getInstance();
			self::callMethod($logger1, 'setLogChannel', ['application']);
			$output = self::callMethod($logger1, 'getLogChannel');
			$this->assertEquals('application', $output);
		}
		
		public function test_default_log_level_set_get()
		{
			$logger1 = Logger::getInstance();
			self::callMethod($logger1, 'setDefaultLogLevel', ['info']);
			$output = self::callMethod($logger1, 'getDefaultLogLevel');
			$this->assertEquals('info', $output);
		}
		
		public function test_check_log_channel_value_returned_successfully()
		{
			$logger1 = Logger::getInstance();
			$output = self::callMethod($logger1, 'getLoggerConfig', ['log_channel']);
			$this->assertEquals('MarwaApp', $output);
		}
		
		public function test_log_write_to_file_level_info()
		{
			file_put_contents(private_storage('logs/app.log'),'');
			$logger = Logger::getInstance();
			$logger->info("Welcome from testing");
			$this->assertTrue(
				!empty(file_get_contents(private_storage('logs/app.log')))
			);
		}
		
		
	}