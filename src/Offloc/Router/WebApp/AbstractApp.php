<?php

/**
 * This file is a part of offloc/router-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Router\WebApp;

use Silex\Application;

/**
 * Abstract Base App
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
abstract class AbstractApp extends Application
{
    /**
     * Constructor
     *
     * @param string $env   Environment
     * @param bool   $debug Debug?
     */
    public function __construct($env = 'prod', $debug = false)
    {
        parent::__construct();

        $this['env'] = $env;
        $this['debug'] = $debug;

        $this->configure();
    }

    protected function configure()
    {

        $app = $this;

        $app['offloc.router.projectRoot'] = __DIR__.'/../../../..';

        $this->register(new \Silex\Provider\UrlGeneratorServiceProvider);
    }
}
