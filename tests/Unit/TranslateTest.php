<?php
	
	namespace Unit;
	
	use Marwa\Application\Translate;
	use Exception;
	use PHPUnit\Framework\TestCase;
	use ReflectionClass;
	use Symfony\Contracts\Translation\TranslatorInterface;
	
	class TranslateTest extends TestCase {
		
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
		
		public function test_get_translator_instance_success()
		{
			$translator = Translate::getInstance();
			$this->assertInstanceOf(TranslatorInterface::class,$translator);
		}
		public function test_translate_message_failed()
		{
			$translator = Translate::getInstance();
			$this->assertEquals("Symfony is great",$translator->trans('Symfony is great'));
		}
	}