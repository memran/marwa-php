<?php
	
	namespace Unit;
	
	use Exception;
	use Marwa\Application\App;
	use PHPUnit\Framework\TestCase;
	use ReflectionClass;
	
	class AppTest extends TestCase {
		
		public function testGetEnvFileNameOnTesting()
		{
			$app = $this->createApplication();
			$foo = self::callMethod($app, 'getEnvironmentFile');
			
			$this->assertEquals('.env.testing', basename($foo));
		}
		
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
		
		/**
		 * @throws Exception
		 */
		public function testDebugIsFalseOnTestingEnvironment()
		{
			$app = $this->createApplication();
			$foo = self::callMethod($app, 'isDebug');
			$this->assertFalse($foo);
		}
		
		/**
		 * @throws \Marwa\Application\Exceptions\FileNotFoundException
		 */
		public function testAppInstanceSingleton()
		{
			$app = $this->createApplication();
			$this->assertEquals($app, App::getInstance());
		}
		
		public function testContainerServiceAccessable()
		{
			$app = $this->createApplication();
			$this->assertTrue($app->getContainer()->has('config'));
		}
		
		public function testLocaleSettingsChangeSetGet()
		{
			$app = $this->createApplication();
			$app->setLocale('bn');
			$this->assertEquals('bn', $app->getLocale());
		}
		
		public function testRenderTimeIsString()
		{
			$app = $this->createApplication();
			$this->assertIsString($app->getRenderTime());
		}
	}