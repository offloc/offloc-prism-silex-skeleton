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

use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Route API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class RouteControllerTest extends AbstractControllerTest
{
    /**
     * Test route root
     */
    public function testRouteRoot()
    {
        $client = $this->createClient();

        list($response, $json) = $this->traverseToRouteRoot($client);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals('offloc_router_api_route_root', $json['type']);
        $this->assertArrayHasKey('create', $json);
        $this->assertArrayHasKey('find', $json);
    }

    /**
     * Test find (success)
     */
    public function testRouteFindSuccess()
    {
        $service = new \Offloc\Router\Domain\Model\Service\Service(
            'service key',
            'Some Service',
            'http://service.com'
        );

        $route = new \Offloc\Router\Domain\Model\Route\Route(
            $service,
            'asdf',
            'http://example.com',
            'Some Name',
            array(
                'oapp-header' => 'Sample Header',
            )
        );

        $this->app['offloc.router.domain.model.route.routeRepository'] = $this
            ->getMock('Offloc\Router\Domain\Model\Route\RouteRepositoryInterface');
        $this->app['offloc.router.domain.model.route.routeRepository']
            ->expects($this->exactly(2))
            ->method('find')
            ->with($this->equalTo($route->id()))
            ->will($this->returnValue($route));

        $client = $this->createClient();

        list($response, $json) = $this->traverseToRouteRoot($client);

        $client->request('POST', $json['find'], array('id' => $route->id()));
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals('offloc_router_api_route_find', $json['type']);
        $this->assertArrayHasKey('link', $json);

        $client->request('GET', $json['link']);
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);
        $this->assertEquals('http://example.com', $json['target']);
        $this->assertEquals('Some Name', $json['name']);
        $this->assertEquals('asdf', $json['id']);
        $this->assertEquals('Sample Header', $json['headers']['oapp-header']);
        $this->assertArrayHasKey('link', $json['service']);
    }

    /**
     * Test create (success)
     */
    public function testRouteCreateSuccess()
    {
        $service = new \Offloc\Router\Domain\Model\Service\Service(
            'service key',
            'Some Service',
            'http://service.com'
        );

        $this->app['offloc.router.requestAuthenticator'] = $this
            ->getMockBuilder('Offloc\Router\WebApp\Api\RequestAuthenticator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->app['offloc.router.requestAuthenticator']
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($service));

        $route = new \Offloc\Router\Domain\Model\Route\Route(
            $service,
            'asdf',
            'http://example.com',
            'Some Name',
            array(
                'oapp-header' => 'Sample Header',
            )
        );

        $this->app['offloc.router.domain.model.route.routeFactory'] = $this
            ->getMockBuilder('Offloc\Router\Domain\Model\Route\RouteFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->app['offloc.router.domain.model.route.routeFactory']
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo($service),
                $this->equalTo($route->target()),
                $this->equalTo($route->name()),
                $this->equalTo(null),
                $this->equalTo($route->headers())
            )
            ->will($this->returnValue($route));

        $this->app['offloc.router.domain.model.route.routeRepository'] = $this
            ->getMock('Offloc\Router\Domain\Model\Route\RouteRepositoryInterface');
        $this->app['offloc.router.domain.model.route.routeRepository']
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo($route->id()))
            ->will($this->returnValue($route));

        $client = $this->createClient();

        list($response, $json) = $this->traverseToRouteRoot($client);

        $server = array('HTTP_CONTENT_TYPE' => 'application/json', );

        $body = json_encode(array(
            'target' => $route->target(),
            'name' => $route->name(),
            'id' => null,
            'headers' => $route->headers(),
        ));

        $client->request('POST', $json['create'], array(), array(), $server, $body);
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals('offloc_router_api_route_create', $json['type']);
        $this->assertArrayHasKey('link', $json);

        $client->request('GET', $json['link']);
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);
        $this->assertEquals('http://example.com', $json['target']);
        $this->assertEquals('Some Name', $json['name']);
        $this->assertEquals('asdf', $json['id']);
        $this->assertEquals('Sample Header', $json['headers']['oapp-header']);
        $this->assertArrayHasKey('link', $json['service']);
    }
}
