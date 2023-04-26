<?php

//APP START TIME
define('START_APP', microtime(true));

//directory separator
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

//directory for web root
defined('WEBROOT') or define('WEBROOT', dirname(__FILE__, 2));


//loading autoload
require_once WEBROOT . DS . 'vendor/autoload.php';

//initialize application
$app = new Marwa\Application\App(WEBROOT);

//dispatch the response
$app->run();
