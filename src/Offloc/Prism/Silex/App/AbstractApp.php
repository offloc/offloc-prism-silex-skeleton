<?php

/**
 * This file is a part of offloc/prism-silex.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Prism\Silex\App;

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

        $app['offloc.prism.projectRoot'] = __DIR__.'/../../../../..';

        $this->register(new \Dflydev\Silex\Provider\Psr0ResourceLocator\Psr0ResourceLocatorServiceProvider);
        $this->register(new \Dflydev\Silex\Provider\Psr0ResourceLocator\Composer\ComposerResourceLocatorServiceProvider);
        $this->register(new \Silex\Provider\UrlGeneratorServiceProvider);
        $app->register(new \Igorw\Silex\ConfigServiceProvider(
            $app['offloc.prism.projectRoot']."/config/".$app['env'].".json"
        ));

        $app->register(new \Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider);
        $app->register(new \Silex\Provider\DoctrineServiceProvider);
        $app->register(new \Offloc\Prism\Silex\Provider\Domain\DomainServiceProvider);
    }
}
