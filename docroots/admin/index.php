<?php

ini_set('display_errors', 0);

require_once('../../vendor/autoload.php');

$app = new Silex\Application;

$app['offloc.prism.admin.docroot'] = __DIR__;

require __DIR__.'/../../config/bootstrap/prod.php';
require __DIR__.'/../../src/app.php';
require __DIR__.'/../../src/admin/webapp.php';
require __DIR__.'/../../config/admin/prod.php';
require __DIR__.'/../../src/admin/controllers.php';

$app->run();
