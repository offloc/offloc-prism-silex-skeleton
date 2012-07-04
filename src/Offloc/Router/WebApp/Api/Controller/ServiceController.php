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
 * Defines the Service API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class ServiceController extends AbstractController
{
    /**
     * Root action
     *
     * @return string
     */
    public function rootAction()
    {
        return $this->app->json(array(
            'type' => 'service_root',
            'create' => $this->generateUrl(ApiControllerProvider::ROUTE_SERVICE_CREATE),
            'find' => $this->generateUrl(ApiControllerProvider::ROUTE_SERVICE_FIND),
        ));
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
        $service = $this->serviceRepository()->find($request->request->get('key'));

        return $this->app->json(array(
            'type' => 'service_link',
            'link' => $this->generateServiceUrl($service),
        ));
    }

    /**
     * Detail action
     *
     * @param Request $request    Request
     * @param string  $serviceKey Service key
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function detailAction(Request $request, $serviceKey)
    {
        $authenticatedService = $this->requireAuthentication($request);
        $service = $this->serviceRepository()->find($serviceKey);

        return $this->app->json(array(
            'type' => 'offloc_router_api_service_detail',
            'link' => $this->generateServiceUrl($service),
            'key' => $service->key(),
            'name' => $service->name(),
            'url' => $service->url(),
            'secret' => $authenticatedService->canAdmin($service) ? $service->secret() : null,
        ));
    }
}
