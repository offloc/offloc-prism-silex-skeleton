<?php

/**
 * This file is a part of offloc/prism-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Prism\WebApp;

use Symfony\Component\HttpFoundation\Request;

/**
 * API App
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class Api extends AbstractApp
{
    const ROUTE_ROOT = 'offloc_prism_api_root';
    const ROUTE_AUTH_ROOT = 'offloc_prism_api_auth_root';
    const ROUTE_AUTH_AUTHENTICATE = 'offloc_prism_api_auth_authenticate';
    const ROUTE_ROUTE_ROOT = 'offloc_prism_api_route_root';
    const ROUTE_ROUTE_CREATE = 'offloc_prism_api_route_create';
    const ROUTE_ROUTE_FIND = 'offloc_prism_api_route_find';
    const ROUTE_ROUTE_DETAIL = 'offloc_prism_api_route_detail';
    const ROUTE_SERVICE_ROOT = 'offloc_prism_api_service_root';
    const ROUTE_SERVICE_CREATE = 'offloc_prism_api_service_create';
    const ROUTE_SERVICE_FIND = 'offloc_prism_api_service_find';
    const ROUTE_SERVICE_DETAIL = 'offloc_prism_api_service_detail';

    protected function configure()
    {
        parent::configure();

        $app = $this;

        $app['resolver'] = $app->share($app->extend('resolver', function ($resolver, $app) {
            return new Silex\ServiceControllerResolver($resolver, $app);
        }));

        $app['offloc.prism.webapp.api.controller.rootController'] = $app->share(function() use ($app) {
            return new Api\Controller\RootController($app);
        });

        $app['offloc.prism.webapp.api.controller.authController'] = $app->share(function() use ($app) {
            return new Api\Controller\AuthController($app);
        });

        $app['offloc.prism.webapp.api.controller.routeController'] = $app->share(function() use ($app) {
            return new Api\Controller\RouteController($app);
        });

        $app['offloc.prism.webapp.api.controller.serviceController'] = $app->share(function() use ($app) {
            return new Api\Controller\ServiceController($app);
        });

        $app->get('/', 'offloc.prism.webapp.api.controller.rootController:rootAction')->bind(self::ROUTE_ROOT);

        $app->get('/auth', 'offloc.prism.webapp.api.controller.authController:rootAction')
            ->bind(self::ROUTE_AUTH_ROOT);

        $app->post('/auth/authenticate', 'offloc.prism.webapp.api.controller.authController:authenticateAction')
            ->bind(self::ROUTE_AUTH_AUTHENTICATE);

        $app->get('/route', 'offloc.prism.webapp.api.controller.routeController:rootAction')
            ->bind(self::ROUTE_ROUTE_ROOT);

        $app->post('/route/routes', 'offloc.prism.webapp.api.controller.routeController:createAction')
            ->bind(self::ROUTE_ROUTE_CREATE);

        $app->post('/route/find', 'offloc.prism.webapp.api.controller.routeController:findAction')
            ->bind(self::ROUTE_ROUTE_FIND);

        $app->get('/route/routes/{routeId}', 'offloc.prism.webapp.api.controller.routeController:detailAction')
            ->bind(self::ROUTE_ROUTE_DETAIL);

        $app->get('/service', 'offloc.prism.webapp.api.controller.serviceController:rootAction')
            ->bind(self::ROUTE_SERVICE_ROOT);

        $app->post('/service/services', 'offloc.prism.webapp.api.controller.serviceController:createAction')
            ->bind(self::ROUTE_SERVICE_CREATE);

        $app->post('/service/find', 'offloc.prism.webapp.api.controller.serviceController:findAction')
            ->bind(self::ROUTE_SERVICE_FIND);

        $app->get('/service/services/{serviceKey}', 'offloc.prism.webapp.api.controller.serviceController:detailAction')
            ->bind(self::ROUTE_SERVICE_DETAIL);
    }
}
