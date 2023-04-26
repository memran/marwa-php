<?php
	
	
	namespace Unit;
	
	
	use Marwa\Application\Configs\Interfaces\ConfigInterface;
	use Marwa\Application\Containers\Container;
	use Marwa\Application\Containers\ContainerInterface;
	use PHPUnit\Framework\TestCase;
	
	class ContainerTest extends TestCase {
		
		
		public function testContainerInterface()
		{
			$container = Container::getInstance();
			$this->assertInstanceOf(ContainerInterface::class, $container);
		}
		
		/**
		 *
		 */
		public function testContainerBindMethod()
		{
			$container = Container::getInstance();
			$container->bind('config', ['app' => 'test']);
			$this->assertTrue($container->has('config'));
		}
		
		/**
		 *
		 */
		public function testContainerBindSharedTrue()
		{
			$container = Container::getInstance();
			$container->bind('config', ['app' => 'test'], true);
			$container->bind('config', ['app' => 'test1'], true);
			
			$this->assertTrue($container->has('config'));
			$this->assertIsNotArray($container->get('config'));
			$this->assertInstanceOf(ConfigInterface::class, $container->get('config'));
		}
		
		public function testContainerBindSharedFalse()
		{
			$container = Container::getInstance();
			$container->bind('configA', ['app' => 'test'], false);
			$container->bind('configA', ['app' => 'test1'], false);
		
			$this->assertTrue($container->has('configA'));
			$this->assertArrayHasKey('app', $container->get('configA'));
			$this->assertEquals('test', $container->get('configA')['app']);
		}
		public function testContainerSingleton()
		{
			$container = Container::getInstance();
			$container->singleton('configB', ['app' => 'test']);
			$container->singleton('configC', ['app' => 'test1']);
			$this->assertTrue($container->has('configB'));
			$this->assertTrue($container->has('configC'));

		}

	}