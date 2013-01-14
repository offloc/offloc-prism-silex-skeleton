<?php

ini_set('display_errors', 0);

require_once('../../vendor/autoload.php');

$app = new Silex\Application;

require __DIR__.'/../../config/bootstrap/prod.php';
require __DIR__.'/../../src/api/webapp.php';
require __DIR__.'/../../config/api/prod.php';
require __DIR__.'/../../src/api/controllers.php';

$app->run();
