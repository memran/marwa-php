<?PHP
	return [
		'app' =>
			[
				/**
				 * Application base url
				 * @var string
				 */
				'base_url' => env('APP_PATH'),

				/**
				 * You can enable or disable debug option from .env files
				 * @var string
				 */
				'debug' => env('APP_DEBUG'),

				/**
				 * @var string app Application environ define
				 */
				'env' => env('APP_ENV'),

				/**
				 * @var string default language
				 */
				'locale' => env('LOCALE'),
				/**
				 * @var array list of allowed languages
				 */
				'supportLocale' => ['en', 'bn', 'fr'],
				/**
				 * locale detect from uri path
				 */
				'detectLocaleFromPath' => false,
				/**
				 * @var string application timezone
				 */
				'timezone' => env('APP_TIMEZONE'),

				/**
				 * If you enable this option then you can append lang i.e en or fr to the url
				 * it will auto detect for app locale and remove from path for route matching.
				 * @var boolean
				 */

				'isLangRequired' => false,

				/**
				 * It is option to enable or disable append version number on the url
				 * @var string
				 */
				'versionUrl' => 1,

				/**
				 * list of versions for this app to show in url
				 * @var arary
				 */
				'versions' => ['1', '5.3'],

				/**
				 * enable it if you need version number in the url
				 * @var boolean
				 */
				'isVersionRequired' => false,

				/**
				 * Change Log file name if you need other name
				 * @var string
				 */
				'log_file' => 'app.log',

				/**
				 * Log file default channel name
				 * @var string
				 */
				'log_channel' => 'MarwaApp',

				/**
				 * Default log level
				 * @var string
				 */
				'log_level' => 'debug',

				/**
				 * Caching template to faster the loading
				 * @var bool
				 */
				'templateCache' => false,

				/**
				 * template expire time
				 * @var int
				 */
				'templateExpire' => 300,

				/**
				 * developer documentation access property for the user
				 * @var string
				 */
				'developer_docs' => 'private' //public or private
			],
		/**
		 * list of service provider boot at startup
		 */
		'providers' => [
			"Marwa\Application\ServiceProvider\ViewServiceProvider",
			//"Marwa\Application\ServiceProvider\FileServiceProvider",
			//"Marwa\Application\ServiceProvider\EventServiceProvider",
			//"Marwa\Application\ServiceProvider\DatabaseServiceProvider",
			//"Marwa\Application\ServiceProvider\MailServiceProvider",
			//"Marwa\Application\ServiceProvider\CacheServiceProvider",
			//"Marwa\Application\ServiceProvider\RedisServiceProvider",
			//"Marwa\Application\ServiceProvider\MemcacheServiceProvider",
			//"Marwa\Application\ServiceProvider\AuthServiceProvider",
			//"Marwa\Application\ServiceProvider\NotifyServiceProvider",
			//"Marwa\Application\ServiceProvider\TranslatorServiceProvider",
		],
		//route middleware
		'middlewares' => [
			new Marwa\Application\Middlewares\ShutdownMiddleware,
			new Marwa\Application\Middlewares\CorsMiddleware,
			//new Marwa\Application\Middlewares\CsrfTokenMiddleware,
			//new Marwa\Application\Middlewares\ClientIpMiddleware,
			//new Marwa\Application\Middlewares\FirewallMiddleware,
			//new Marwa\Application\Middlewares\LocalizationMiddleware,
			//new Marwa\Application\Middlewares\SubdomainMiddleware //experimental
		]
	];

?>
