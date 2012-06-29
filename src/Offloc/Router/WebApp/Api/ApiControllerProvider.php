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
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function() use ($app) {
            return $app->json(array(
                'type' => 'offloc_router_api_root',
                'auth' => $app['url_generator']->generate('offloc_router_api_auth_root'),
                'route' => $app['url_generator']->generate('offloc_router_api_route_root'),
                'service' => $app['url_generator']->generate('offloc_router_api_service_root'),
                //'foo' => $app['url_generator']->generate('foo'),
            ));
        })->bind('offloc_router_api_root');

        $controllers->get('/auth', function() use ($app) {
            return $app->json(array(
                'type' => 'offloc_router_api_auth',
                'authenticate' => $app['url_generator']->generate('offloc_router_api_auth_authenticate'),
            ));
        })->bind('offloc_router_api_auth_root');

        $controllers->post('/auth/authenticate', function() use ($app) {
            return $app->json(array(
                'type' => 'offloc_router_api_auth_authenticate',
            ));
        })->bind('offloc_router_api_auth_authenticate');

        $controllers->get('/route', function() use ($app) {
            return $app->json(array(
                'type' => 'offloc_router_api_route',
                'create' => $app['url_generator']->generate('offloc_router_api_route_create'),
                'find' => $app['url_generator']->generate('offloc_router_api_route_find'),
            ));
        })->bind('offloc_router_api_route_root');

        $controllers->post('/route/routes', function(Request $request) use ($app) {
            $routeFactory = $app['offloc.router.domain.model.route.routeFactory'];
            $service = $app['offloc.router.authenticatedService'];

            try {
                $routeInput = json_decode($request->getContent(), true);

                $target = $routeInput['target'];
                $name = $routeInput['name'];
                $id = $routeInput['id'];
                $headers = $routeInput['headers'];

                $route = $routeFactory->create($service, $target, $name, $id, $headers);

                return $app->json(array(
                    'type' => 'offloc_router_api_route_create',
                    'link' => $app['url_generator']->generate('offloc_router_api_route_detail', array('routeId' => $route->id())),
                ));
            } catch (\Exception $e) {
                return $app->json(array(
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ), 501);
            }
        })->bind('offloc_router_api_route_create');

        $controllers->post('/route/find', function(Request $request) use ($app) {
            $routeRepository = $app['offloc.router.domain.model.route.routeRepository'];
            $route = $routeRepository->find($request->request->get('id'));

            return $app->json(array(
                'type' => 'offloc_router_api_route_find',
                'link' => $app['url_generator']->generate('offloc_router_api_route_detail', array('routeId' => $route->id())),
            ));
        })->bind('offloc_router_api_route_find');

        $controllers->get('/route/routes/{routeId}', function(Request $request, $routeId) use ($app) {
            $routeRepository = $app['offloc.router.domain.model.route.routeRepository'];
            $route = $routeRepository->find($routeId);

            return $app->json(array(
                'type' => 'offloc_router_api_route_detail',
                'link' => $app['url_generator']->generate('offloc_router_api_route_detail', array('routeId' => $route->id())),
                'id' => $route->id(),
                'target' => $route->target(),
                'name' => $route->name(),
                'headers' => $route->headers(),
                'service' => array(
                    'link' => $app['url_generator']->generate('offloc_router_api_service_detail', array('serviceKey' => $route->service()->key(), ))
                )
            ));
        })->bind('offloc_router_api_route_detail');

        $controllers->get('/service', function() use ($app) {

        })->bind('offloc_router_api_service_root');

        $controllers->get('/service/services/{serviceKey}', function($serviceKey) use ($app) {

        })->bind('offloc_router_api_service_detail');

        return $controllers;
    }
}
