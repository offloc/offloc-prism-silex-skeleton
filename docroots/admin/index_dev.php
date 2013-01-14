<?php

use Symfony\Component\ClassLoader\DebugClassLoader;
use Symfony\Component\HttpKernel\Debug\ErrorHandler;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler;
use Dflydev\Composer\Autoload\ClassLoaderLocator;

require_once __DIR__.'/../../vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(-1);
ClassLoaderLocator::init();
DebugClassLoader::enable();
ErrorHandler::register();
if ('cli' !== php_sapi_name()) {
    ExceptionHandler::register();
}

$app = new Silex\Application;

require __DIR__.'/../../config/dev.php';
require __DIR__.'/../../src/admin/webapp.php';
require __DIR__.'/../../config/admin/dev.php';
require __DIR__.'/../../src/admin/controllers.php';

$app->run();
