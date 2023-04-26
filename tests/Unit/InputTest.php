<?php
	
	namespace Unit;
	
	use Exception;
	use Marwa\Application\App;
	use Marwa\Application\Input;
	use PHPUnit\Framework\TestCase;
	use ReflectionClass;
	
	class InputTest extends TestCase {
		
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
		
		public function test_http_get_request()
		{
			$input = new Input();
			$this->assertFalse($input->get('app'));
		}
		
		public function test_http_post_request()
		{
			$input = new Input();
			$this->assertFalse($input->post('app'));
		}
		public function test_http_any_request()
		{
			$input = new Input();
			$this->assertFalse($input->any('app'));
		}
		public function test_http_files_request()
		{
			$input = new Input();
			$this->assertIsArray($input->files('app'));
		}
	}