<?php

/**
 * This file is a part of offloc/router-api-controllers.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Router\WebApp\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the API Controller Provider
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class ApiControllerProvider implements ControllerProviderInterface
{
    const ROUTE_ROOT = 'offloc_router_api_root';
    const ROUTE_AUTH_ROOT = 'offloc_router_api_auth_root';
    const ROUTE_AUTH_AUTHENTICATE = 'offloc_router_api_auth_authenticate';
    const ROUTE_ROUTE_ROOT = 'offloc_router_api_route_root';
    const ROUTE_ROUTE_CREATE = 'offloc_router_api_route_create';
    const ROUTE_ROUTE_FIND = 'offloc_router_api_route_find';
    const ROUTE_ROUTE_DETAIL = 'offloc_router_api_route_detail';
    const ROUTE_SERVICE_ROOT = 'offloc_router_api_service_root';
    const ROUTE_SERVICE_CREATE = 'offloc_router_api_service_create';
    const ROUTE_SERVICE_FIND = 'offloc_router_api_service_find';
    const ROUTE_SERVICE_DETAIL = 'offloc_router_api_service_detail';

    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $app['offloc.router.webapp.api.controller.rootController'] = $app->share(function() use ($app) {
            return new Controller\RootController($app);
        });

        $app['offloc.router.webapp.api.controller.authController'] = $app->share(function() use ($app) {
            return new Controller\AuthController($app);
        });

        $app['offloc.router.webapp.api.controller.routeController'] = $app->share(function() use ($app) {
            return new Controller\RouteController($app);
        });

        $app['offloc.router.webapp.api.controller.serviceController'] = $app->share(function() use ($app) {
            return new Controller\ServiceController($app);
        });

        $controllers = $app['controllers_factory'];

        $controllers->get('/', function() use ($app) {
            return $app['offloc.router.webapp.api.controller.rootController']->rootAction();
        })->bind(self::ROUTE_ROOT);

        $controllers->get('/auth', function() use ($app) {
            return $app['offloc.router.webapp.api.controller.authController']->rootAction();
        })->bind(self::ROUTE_AUTH_ROOT);

        $controllers->post('/auth/authenticate', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.authController']->authenticateAction($request);
        })->bind(self::ROUTE_AUTH_AUTHENTICATE);

        $controllers->get('/route', function() use ($app) {
            return $app['offloc.router.webapp.api.controller.routeController']->rootAction();
        })->bind('offloc_router_api_route_root');

        $controllers->post('/route/routes', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.routeController']->createAction($request);
        })->bind(self::ROUTE_ROUTE_CREATE);

        $controllers->post('/route/find', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.routeController']->findAction($request);
        })->bind(self::ROUTE_ROUTE_FIND);

        $controllers->get('/route/routes/{routeId}', function(Request $request, $routeId) use ($app) {
            return $app['offloc.router.webapp.api.controller.routeController']->detailAction($request, $routeId);
        })->bind(self::ROUTE_ROUTE_DETAIL);

        $controllers->get('/service', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.serviceController']->rootAction($request);
        })->bind(self::ROUTE_SERVICE_ROOT);

        $controllers->post('/service/services', function(Request $request, $serviceKey) use ($app) {
            return $app['offloc.router.webapp.api.controller.serviceController']->createAction($request, $serviceKey);
        })->bind(self::ROUTE_SERVICE_CREATE);

        $controllers->post('/service/find', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.serviceController']->findAction($request);
        })->bind(self::ROUTE_SERVICE_FIND);

        $controllers->get('/service/services/{serviceKey}', function(Request $request, $serviceKey) use ($app) {
            return $app['offloc.router.webapp.api.controller.serviceController']->detailAction($request, $serviceKey);
        })->bind(self::ROUTE_SERVICE_DETAIL);

        return $controllers;
    }
}
