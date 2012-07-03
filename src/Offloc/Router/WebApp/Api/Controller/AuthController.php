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
 * Defines the Auth API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class AuthController extends AbstractController
{
    /**
     * Root action
     *
     * @return string
     */
    public function rootAction()
    {
        return $this->app->json(array(
            'type' => 'offloc_router_api_auth_root',
            'authenticate' => $this->generateUrl(ApiControllerProvider::ROUTE_AUTH_AUTHENTICATE),
        ));
    }

    /**
     * Authenticate action
     *
     * @param Request $request Request
     *
     * @return string
     */
    public function authenticateAction(Request $request)
    {
        $service = $this->requireAuthentication($request);

        return $this->app->json(array(
            'type' => 'offloc_router_api_auth_authenticate',
            'link' => $this->generateServiceUrl($service),
        ));
    }
}
