<?php

/**
 * This file is a part of offloc/router-api-controllers.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Router\WebApp\Api\Controller;

use Offloc\Router\WebApp\Api\ApiControllerProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the Route API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class RouteController extends AbstractController
{
    /**
     * Root action
     *
     * @return string
     */
    public function rootAction()
    {
        return $this->app->json(array(
            'type' => 'offloc_router_api_route_root',
            'create' => $this->generateUrl(ApiControllerProvider::ROUTE_ROUTE_CREATE),
            'find' => $this->generateUrl(ApiControllerProvider::ROUTE_ROUTE_FIND),
        ));
    }

    /**
     * Create action
     *
     * @param Request $request Request
     *
     * @return string
     */
    public function createAction(Request $request)
    {
        $service = $this->requireAuthentication($request);

        try {
            $routeInput = json_decode($request->getContent(), true);

            $target = $routeInput['target'];
            $name = $routeInput['name'];
            $id = $routeInput['id'];
            $headers = $routeInput['headers'];

            $route = $this->routeFactory()->create($service, $target, $name, $id, $headers);

            return $this->app->json(array(
                'type' => 'offloc_router_api_route_create',
                'link' => $this->generateRouteUrl($route),
            ));
        } catch (\Exception $e) {
            return $this->app->json(array(
                'type' => 'error',
                'message' => $e->getMessage(),
            ), 501);
        }
    }

    /**
     * Find action
     *
     * @param Request $request Request
     *
     * @return string
     */
    public function findAction(Request $request)
    {
        $route = $this->routeRepository()->find($request->request->get('id'));

        return $this->app->json(array(
            'type' => 'offloc_router_api_route_find',
            'link' => $this->generateRouteUrl($route),
        ));
    }

    /**
     * Detail action
     *
     * @param Request $request Request
     * @param string  $routeId Route ID
     *
     * @return string
     */
    public function detailAction(Request $request, $routeId)
    {
        $route = $this->routeRepository()->find($routeId);

        return $this->app->json(array(
            'type' => 'offloc_router_api_route_detail',
            'link' => $this->generateRouteUrl($route),
            'id' => $route->id(),
            'target' => $route->target(),
            'name' => $route->name(),
            'headers' => $route->headers(),
            'service' => array(
                'link' => $this->generateServiceUrl($route->service()),
            )
        ));
    }
}
