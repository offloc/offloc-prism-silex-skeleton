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

use Symfony\Component\HttpFoundation\Request;

/**
 * API App
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class Api extends AbstractApp
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

    protected function configure()
    {
        parent::configure();

        $app = $this;

        $app['offloc.router.webapp.api.controller.rootController'] = $app->share(function() use ($app) {
            return new Api\Controller\RootController($app);
        });

        $app['offloc.router.webapp.api.controller.authController'] = $app->share(function() use ($app) {
            return new Api\Controller\AuthController($app);
        });

        $app['offloc.router.webapp.api.controller.routeController'] = $app->share(function() use ($app) {
            return new Api\Controller\RouteController($app);
        });

        $app['offloc.router.webapp.api.controller.serviceController'] = $app->share(function() use ($app) {
            return new Api\Controller\ServiceController($app);
        });

        $app->get('/', function() use ($app) {
            return $app['offloc.router.webapp.api.controller.rootController']->rootAction();
        })->bind(self::ROUTE_ROOT);

        $app->get('/auth', function() use ($app) {
            return $app['offloc.router.webapp.api.controller.authController']->rootAction();
        })->bind(self::ROUTE_AUTH_ROOT);

        $app->post('/auth/authenticate', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.authController']->authenticateAction($request);
        })->bind(self::ROUTE_AUTH_AUTHENTICATE);

        $app->get('/route', function() use ($app) {
            return $app['offloc.router.webapp.api.controller.routeController']->rootAction();
        })->bind('offloc_router_api_route_root');

        $app->post('/route/routes', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.routeController']->createAction($request);
        })->bind(self::ROUTE_ROUTE_CREATE);

        $app->post('/route/find', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.routeController']->findAction($request);
        })->bind(self::ROUTE_ROUTE_FIND);

        $app->get('/route/routes/{routeId}', function(Request $request, $routeId) use ($app) {
            return $app['offloc.router.webapp.api.controller.routeController']->detailAction($request, $routeId);
        })->bind(self::ROUTE_ROUTE_DETAIL);

        $app->get('/service', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.serviceController']->rootAction($request);
        })->bind(self::ROUTE_SERVICE_ROOT);

        $app->post('/service/services', function(Request $request, $serviceKey) use ($app) {
            return $app['offloc.router.webapp.api.controller.serviceController']->createAction($request, $serviceKey);
        })->bind(self::ROUTE_SERVICE_CREATE);

        $app->post('/service/find', function(Request $request) use ($app) {
            return $app['offloc.router.webapp.api.controller.serviceController']->findAction($request);
        })->bind(self::ROUTE_SERVICE_FIND);

        $app->get('/service/services/{serviceKey}', function(Request $request, $serviceKey) use ($app) {
            return $app['offloc.router.webapp.api.controller.serviceController']->detailAction($request, $serviceKey);
        })->bind(self::ROUTE_SERVICE_DETAIL);
    }
}
