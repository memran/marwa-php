<?php
	
	namespace Unit;
	
	use Exception;
	use League\Route\RouteCollectionInterface;
	use Marwa\Application\App;
	use Marwa\Application\Routes\Router;
	use PHPUnit\Framework\TestCase;
	use Psr\Container\ContainerInterface;
	use ReflectionClass;
	
	class RouterTest extends TestCase {
		
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
		
		public function test_request_set_get()
		{
			$router = new Router();
			$router->setRequest('request');
			$this->assertEquals('request',$router->getRequest());
		}
		
		public function test_path_set_get()
		{
			$router = new Router();
			$router->path('router_path');
			$this->assertEquals('router_path',$router->getPath());
		}
		public function test_container_set_get()
		{
			$app = $this->createApplication();
			$router = new Router();

			$router->setContainer(container()->getPsrContainer());
			$this->assertInstanceOf(ContainerInterface::class,$router->getContainer());
		}
		
		public function test_route_instanceof_RouteCollectionInterface()
		{
			$router = new Router();
			$router->setRequest(app('request'));
			$router->path(app('route_path'));
			$router->setContainer(container()->getPsrContainer());
			$this->assertInstanceOf(RouteCollectionInterface::class,$router->getRouter());
		}
	}