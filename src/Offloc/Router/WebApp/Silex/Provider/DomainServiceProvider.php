<?php

/**
 * This file is a part of offloc/router-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Router\WebApp\Silex\Provider;

use Dflydev\IdentityGenerator\DataStore\Dbal\DataStore;
use Dflydev\IdentityGenerator\Generator\Base32CrockfordGenerator;
use Dflydev\IdentityGenerator\Generator\RandomNumberGenerator;
use Dflydev\IdentityGenerator\Generator\RandomStringGenerator;
use Dflydev\IdentityGenerator\IdentityGenerator;
use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Offloc\Router\Domain\Model\Route\RouteFactory;
use Offloc\Router\Domain\Model\Service\ServiceFactory;
use Offloc\Router\Domain\Service\DflydevIdentityGeneratorService;
use Offloc\Router\Domain\Service\UuidSecretGeneratorService;
use Offloc\Router\Infrastructure\Persistence\Doctrine\Route\RouteRepository;
use Offloc\Router\Infrastructure\Persistence\Doctrine\Service\ServiceRepository;
use Offloc\Router\Infrastructure\Persistence\Doctrine\Session;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Offloc Router Domain Service Provider
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class DomainServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        foreach ($app['offloc.router.domain.get_mapping_drivers']() as $mapping) {
            list ($mappingDriver, $namespace) = $mapping;

            $app['orm.add_mapping_driver']($mappingDriver, $namespace);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['offloc.router.domain.get_mapping_drivers'] = $app->protect(function() use ($app) {
            $resourceMapping = array(
                'Offloc\Router\Domain\Model\Service' => 'Offloc\Router\Infrastructure\Persistence\Doctrine\Service',
                'Offloc\Router\Domain\Model\Route' => 'Offloc\Router\Infrastructure\Persistence\Doctrine\Route',
            );

            $mapping = array();
            foreach ($resourceMapping as $entityNamespace => $resourceNamespace) {
                $directory = $app['psr0_resource_locator']->findFirstDirectory($resourceNamespace);
                $mapping[$directory] = $entityNamespace;
            }

            $locator = new SymfonyFileLocator($mapping, '.orm.xml');

            $mappingDrivers = array();
            foreach ($mapping as $path => $namespace) {
                $mappingDrivers[] = array(new XmlDriver($locator), $namespace);
            }

            return $mappingDrivers;
        });

        $app['offloc.router.domain.model.session'] = $app->share(function($app) {
            return new Session($app['orm.em']);
        });

        $app['offloc.router.domain.model.route.routeFactory'] = $app->share(function($app) {
            return new RouteFactory(
                $app['offloc.router.domain.route.identityGenerator.routeId']
            );
        });
        $app['offloc.router.domain.model.route.routeRepository'] = $app->share(function($app) {
            $objectRepository = $app['orm.em']->getRepository('Offloc\Router\Domain\Model\Route\Route');

            return new RouteRepository($app['offloc.router.domain.model.session'], $objectRepository);
        });

        $app['offloc.router.domain.model.service.serviceFactory'] = $app->share(function($app) {
            return new ServiceFactory(
                $app['offloc.router.domain.service.identityGenerator.serviceKey'],
                $app['offloc.router.domain.service.secretGenerator.serviceSecret']
            );
        });

        $app['offloc.router.domain.model.service.serviceRepository'] = $app->share(function($app) {
            $objectRepository = $app['orm.em']->getRepository('Offloc\Router\Domain\Model\Service\Service');

            return new ServiceRepository($app['offloc.router.domain.model.session'], $objectRepository);
        });

        $app['offloc.router.domain.route.identityGenerator.routeId'] = $app->share(function($app) {
            $dataStore = new DataStore($app['db'], 'routeIdentity', 'id');
            $randomNumberGenerator = new RandomNumberGenerator(32768, 1048575);
            $generator = new Base32CrockfordGenerator($randomNumberGenerator);

            $identityGenerator = new IdentityGenerator($dataStore, $generator);

            return new DflydevIdentityGeneratorService($identityGenerator);
        });

        $app['offloc.router.domain.service.identityGenerator.serviceKey.length'] = 16;

        $app['offloc.router.domain.service.identityGenerator.serviceKey.dataStore'] = $app->share(function($app) {
            return new DataStore($app['db'], 'serviceIdentity', 'key');
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
