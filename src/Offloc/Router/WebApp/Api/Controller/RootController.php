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

/**
 * Defines the Root API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class RootController extends AbstractController
{
    /**
     * Root action
     *
     * @return string
     */
    public function rootAction()
    {
        return $this->app->json(array(
            'type' => 'offloc_router_api_root',
            'auth' => $this->generateUrl(ApiControllerProvider::ROUTE_AUTH_ROOT),
            'route' => $this->generateUrl(ApiControllerProvider::ROUTE_ROUTE_ROOT),
            'service' => $this->generateUrl(ApiControllerProvider::ROUTE_SERVICE_ROOT),
        ));
    }
}
