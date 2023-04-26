<?php
	
	namespace Unit;
	
	use Exception;
	use Marwa\Application\App;
	use Marwa\Application\Response;
	use PHPUnit\Framework\TestCase;
	use Psr\Http\Message\ResponseInterface;
	use ReflectionClass;
	
	class ResponseTest extends TestCase {
		
	
		
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
		 *
		 */
		public function test_resposne_instance_of_psr7interface()
		{
			$response  = Response::getInstance();
			$this->assertInstanceOf(ResponseInterface::class, $response);
		}
		
		public function test_response_html_instance_of_psr7interface()
		{
			$this->assertInstanceOf(ResponseInterface::class, Response::html('Hello World'));
		}
		public function test_response_text_instance_of_psr7interface()
		{
			$this->assertInstanceOf(ResponseInterface::class, Response::text('Hello World'));
		}
	
		public function test_response_xml_instance_of_psr7interface()
		{
			$this->assertInstanceOf(ResponseInterface::class, Response::xml('Hello World'));
		}
		public function test_response_json_instance_of_psr7interface()
		{
			$this->assertInstanceOf(ResponseInterface::class, Response::json('Hello World'));
		}
		public function test_response_redirect_instance_of_psr7interface()
		{
			$this->assertInstanceOf(ResponseInterface::class, Response::redirect('/'));
		}
		public function test_response_empty_instance_of_psr7interface()
		{
			$this->assertInstanceOf(ResponseInterface::class, Response::empty());
		}
		public function test_response_error_instance_of_psr7interface()
		{
			$this->assertInstanceOf(ResponseInterface::class, Response::error('404 not found'));
		}
	}