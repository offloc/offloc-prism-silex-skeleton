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

use Offloc\Router\WebApp\Api\ApiControllerProvider;
use Silex\Application;
use Silex\Provider;
use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Abstract API Controller test
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
abstract class AbstractControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    public function createApplication()
    {
        $app = new Application;

        $app['debug'] = true;

        unset($app['exception_handler']);

        $app->register(new Provider\UrlGeneratorServiceProvider);

        $app->mount('/', new ApiControllerProvider);

        return $app;
    }

    protected function normalizeJsonResponse($response)
    {
        return array($response, json_decode($response->getContent(), true));
    }

    protected function makeRootRequest($client)
    {
        $client->request('GET', '/');

        return $this->normalizeJsonResponse($client->getResponse());
    }

    protected function traverseToAuthRoot($client)
    {
        list ($response, $json) = $this->makeRootRequest($client);

        $client->request('GET', $json['auth']);

        return $this->normalizeJsonResponse($client->getResponse());
    }

    protected function traverseToRouteRoot($client)
    {
        list ($response, $json) = $this->makeRootRequest($client);

        $client->request('GET', $json['route']);

        return $this->normalizeJsonResponse($client->getResponse());
    }

    protected function traverseToServiceRoot($client)
    {
        list ($response, $json) = $this->makeRootRequest($client);

        $client->request('GET', $json['service']);

        return $this->normalizeJsonResponse($client->getResponse());
    }
}
