<?php

/**
 * This file is a part of offloc/router-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Router\WebApp\Api\Controller;

use Offloc\Router\Api\Common\Message;
use Offloc\Router\WebApp\Api;
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
            'type' => Message::TYPE_ROUTE_ROOT,
            'create' => $this->generateUrl(Api::ROUTE_ROUTE_CREATE),
            'find' => $this->generateUrl(Api::ROUTE_ROUTE_FIND),
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

            $this->routeRepository()->store($route);

            $this->session()->flush();

            $routeLink = $this->generateRouteUrl($route);

            return $this->app->json(array(
                'type' => Message::TYPE_ROUTE_LINK,
                'link' => $routeLink,
            ), 201, array('Location' => $routeLink,));
        } catch (\Exception $e) {
            return $this->app->json(array(
                'type' => Message::TYPE_ERROR,
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

        $routeLink = $this->generateRouteUrl($route);

        return $this->app->json(array(
            'type' => Message::TYPE_ROUTE_LINK,
            'link' => $routeLink,
        ), 303, array('Location' => $routeLink,));
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
            'type' => Message::TYPE_ROUTE_DETAIL,
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
