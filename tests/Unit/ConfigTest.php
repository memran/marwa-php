<?php
	
	
	namespace Unit;
	
	use Marwa\Application\Configs\Exceptions\InvalidExtensionException;
	use Marwa\Application\Configs\Interfaces\ConfigInterface;
	use Marwa\Application\Configs\Config;
	use Marwa\Application\Configs\Exceptions\FileNotFoundException;
	use PHPUnit\Framework\TestCase;
	
	class ConfigTest extends TestCase {
		
		/**
		 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
		 */
		public function testConfigInstanceInterface()
		{
			$config = Config::getInstance();
			$this->assertInstanceOf(ConfigInterface::class, $config);
		}
		
		/**
		 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
		 */
		public function testFileNotFoundException()
		{
			$this->expectException(FileNotFoundException::class);
			Config::getInstance('test.php');
		}
		
		/**
		 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
		 */
		public function testSingletonInstance()
		{
			$config1 = Config::getInstance();
			$config2 = Config::getInstance();
			$this->assertEquals($config1, $config2);
		}
		
		/**
		 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
		 */
		public function testInvalidExtensionException()
		{
			$this->expectException(InvalidExtensionException::class);
			Config::getInstance('test.json');
		}
		
		/**
		 * @throws \Marwa\Application\Exceptions\InvalidArgumentException
		 */
		public function testInstanceWithoutArgument()
		{
			$config = Config::getInstance();
			$this->assertInstanceOf(ConfigInterface::class, $config);
		}
		
		public function testCheckConfigFileExtraction()
		{
			$config = Config::getInstance();
			$output = self::callMethod($config, 'checkFile', ['test.php']);
			$this->assertTrue($output);
		}
		
		public static function callMethod( $obj, $name, array $args = [] )
		{
			$class = new \ReflectionClass($obj);
			$method = $class->getMethod($name);
			$method->setAccessible(true);
			
			return $method->invokeArgs($obj, $args);
		}
		
		public function testCheckFileType()
		{
			$config = Config::getInstance();
			$config->setType('php');
			
			$this->assertEquals('php', $config->getType());
		}
		
		public function testInvalidFileExtension()
		{
			$this->expectException(InvalidExtensionException::class);
			$config = Config::getInstance();
			$output = self::callMethod($config, 'checkFile', ['test.json']);
			
		}
		
		public function testValidFileExtension()
		{
			$config = Config::getInstance();
			$output = self::callMethod($config, 'checkFile', ['app.php']);
			$this->assertTrue($output);
		}
		
	}