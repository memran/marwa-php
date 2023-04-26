<?php
	
	namespace Unit;
	
	use Exception;
	use Marwa\Application\App;
	use Marwa\Application\Notification\Mailer\Mail;
	use PHPUnit\Framework\MockObject\MockBuilder;
	use PHPUnit\Framework\TestCase;
	use ReflectionClass;
	
	class MailTest extends TestCase {
		
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
		
		protected function getMailConfig()
		{
			return [
				'default' =>'smtp',
				'from' => [
						'test@test.com'
					],
				'smtp' => [
					'transport'=>'smtp' ,
					'host' => env('MAIL_HOST'),
					'port' => env('MAIL_PORT',25),
					'username' => env('MAIL_USERNAME'),
					'password'=> env('MAIL_PASSWORD'),
					'from' => ['dev@marwaphp.com'=> 'Mohammad Emran'],
					'encryption'=> 'tls', //ssl or tls
				],
				'sendmail' => [
					'transport' => 'sendmail',
					'path' => '/usr/sbin/sendmail -bs'
				],
				'mailgun' =>
					[
						'transport' => 'smtp',
						'domain' => 'your-mailgun-domain',
						'secret' => 'your-mailgun-key',
						'endpoint' => 'api.eu.mailgun.net',
					]
			
			];
		}
		
		/**
		 *
		 */
		public function test_mail_set_get_from_address()
		{
			$mail = new Mail($this->getMailConfig());
			
			$this->assertEquals('test@test.com',$mail->getFrom()[0]);
		}
		
		/**
		 *
		 */
		public function test_mail_default_mailer()
		{
			$mail = new Mail($this->getMailConfig());
			
			$this->assertEquals('smtp',$mail->getDefaultMailer());
		}
		
		/**
		 *
		 */
		public function test_mail__mailer()
		{
			$mail = new Mail($this->getMailConfig());
			
			$this->assertEquals('smtp',$mail->getDefaultMailer());
		}
	}