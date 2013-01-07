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

use Dflydev\IdentityGenerator\DataStore\Dbal\DataStore;
use Dflydev\IdentityGenerator\Generator\Base32CrockfordGenerator;
use Dflydev\IdentityGenerator\Generator\RandomNumberGenerator;
use Dflydev\IdentityGenerator\Generator\RandomStringGenerator;
use Dflydev\IdentityGenerator\IdentityGenerator;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Offloc\Router\Infrastructure\Persistence\Doctrine\Route\RouteRepository;
use Offloc\Router\Infrastructure\Persistence\Doctrine\Service\ServiceRepository;
use Offloc\Router\Infrastructure\Persistence\Doctrine\Session;
use Offloc\Router\Domain\Model\Route\RouteFactory;
use Offloc\Router\Domain\Model\Service\ServiceFactory;
use Offloc\Router\Domain\Service\DflydevIdentityGeneratorService;
use Offloc\Router\Domain\Service\SimpleRandomStringIdentityGeneratorService;
use Offloc\Router\Domain\Service\UuidSecretGeneratorService;
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

        $this->register(new \Dflydev\Silex\Provider\Psr0ResourceLocator\Psr0ResourceLocatorServiceProvider);
        $this->register(new \Dflydev\Silex\Provider\Psr0ResourceLocator\Composer\ComposerResourceLocatorServiceProvider);
        $this->register(new \Silex\Provider\UrlGeneratorServiceProvider);
        $app->register(new \Igorw\Silex\ConfigServiceProvider(
            $app['offloc.router.projectRoot']."/config/".$app['env'].".json"
        ));

        $app->register(new \Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider);
        $app->register(new \Silex\Provider\DoctrineServiceProvider);
        $app->register(new Silex\Provider\DomainServiceProvider, array(
            'offloc.router.core_domain_lib_root' => $app['offloc.router.projectRoot'].'/vendor/offloc/router',
        ));
    }

    private function configureDoctrine()
    {
        $app = $this;

        $entityRoot = $app['offloc.router.projectRoot'].'/vendor/offloc/router/src/Offloc/Router/Infrastructure/Persistence/Doctrine';

        $namespaces = array(
            $entityRoot.'/Service' => 'Offloc\Router\Domain\Model\Service',
            $entityRoot.'/Route' => 'Offloc\Router\Domain\Model\Route',
        );

        $app['doctrine.dbal.event_manager'] = $app->share(function() {
            $eventManager = new EventManager;

            return $eventManager;
        });

        $app['doctrine.configuration'] = $app->share(function($app) use ($namespaces) {
            $config = Setup::createConfiguration('prod' !== $app['env']);
            $driver = new \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver($namespaces);
            $config->setMetadataDriverImpl($driver);
            $config->setAutoGenerateProxyClasses(true);
            //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

            return $config;
        });

        $app['doctrine.dbal.connection'] = $app->share(function() use($app) {

            if (!isset($app['doctrine.dbal.connection_options'])) {
                throw new \InvalidArgumentException('The "doctrine.dbal.connection_options" parameter must be defined');
            }
            $configuration = $app['doctrine.configuration'];
            $eventManager = $app['doctrine.dbal.event_manager'];

            return DriverManager::getConnection($app['doctrine.dbal.connection_options'], $configuration, $eventManager);
        });

        $app['doctrine.orm.em'] = $app->share(function($app) {
            $connection = $app['doctrine.dbal.connection'];
            $configuration = $app['doctrine.configuration'];

            return EntityManager::create($connection, $configuration);
        });

        $app['offloc.router.domain.model.session'] = $app->share(function($app) {
            return new Session($app['doctrine.orm.em']);
        });

        $app['offloc.router.domain.model.route.routeFactory'] = $app->share(function($app) {
            return new RouteFactory(
                $app['offloc.router.domain.route.identityGenerator.routeId']
            );
        });
        $app['offloc.router.domain.model.route.routeRepository'] = $app->share(function($app) {
            $objectRepository = $app['doctrine.orm.em']->getRepository('Offloc\Router\Domain\Model\Route\Route');

            return new RouteRepository($app['offloc.router.domain.model.session'], $objectRepository);
        });

        $app['offloc.router.domain.model.service.serviceFactory'] = $app->share(function($app) {
            return new ServiceFactory(
                $app['offloc.router.domain.service.identityGenerator.serviceKey'],
                $app['offloc.router.domain.service.secretGenerator.serviceSecret']
            );
        });

        $app['offloc.router.domain.model.service.serviceRepository'] = $app->share(function($app) {
            $objectRepository = $app['doctrine.orm.em']->getRepository('Offloc\Router\Domain\Model\Service\Service');

            return new ServiceRepository($app['offloc.router.domain.model.session'], $objectRepository);
        });

        $app['offloc.router.domain.route.identityGenerator.routeId'] = $app->share(function($app) {
            $dataStore = new DataStore($app['doctrine.dbal.connection'], 'routeIdentity', 'id');
            $randomNumberGenerator = new RandomNumberGenerator(32768, 1048575);
            $generator = new Base32CrockfordGenerator($randomNumberGenerator);

            $identityGenerator = new IdentityGenerator($dataStore, $generator);

            return new DflydevIdentityGeneratorService($identityGenerator);
        });

        $app['offloc.router.domain.service.identityGenerator.serviceKey.length'] = 16;

        $app['offloc.router.domain.service.identityGenerator.serviceKey.dataStore'] = $app->share(function($app) {
            return new DataStore($app['doctrine.dbal.connection'], 'serviceIdentity', 'key');
        });

        $app['offloc.router.domain.service.identityGenerator.serviceKey.generator'] = $app->share(function($app) {
            return RandomStringGenerator::createBase32Crockford(
                $app['offloc.router.domain.service.identityGenerator.serviceKey.length']
            );
        });

        $app['offloc.router.domain.service.identityGenerator.serviceKey'] = $app->share(function($app) {
            $dataStore = $app['offloc.router.domain.service.identityGenerator.serviceKey.dataStore'];
            $generator = $app['offloc.router.domain.service.identityGenerator.serviceKey.generator'];

            $identityGenerator = new IdentityGenerator($dataStore, $generator);

            return new DflydevIdentityGeneratorService($identityGenerator);
        });

        $app['offloc.router.domain.service.secretGenerator.serviceSecret'] = $app->share(function($app) {
            return new UuidSecretGeneratorService;
        });
    }
}
