<?php

/**
 * This file is a part of offloc/prism-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once('../../vendor/autoload.php');

$app = new Offloc\Prism\Silex\App\Admin\Admin('prod', false);
$app->run();
