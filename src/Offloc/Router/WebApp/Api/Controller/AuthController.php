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
            'type' => Message::TYPE_AUTH_ROOT,
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

        $serviceLink = $this->generateServiceUrl($service);

        return $this->app->json(array(
            'type' => Message::TYPE_SERVICE_LINK,
            'link' => $serviceLink,
        ), 303, array('Location' => $serviceLink,));
    }
}
