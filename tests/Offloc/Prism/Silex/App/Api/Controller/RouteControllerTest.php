<?php

/**
 * This file is a part of offloc/prism-silex.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Prism\Silex\App\Api\Controller;

use Offloc\Prism\Api\Common\Message;
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
        $this->assertEquals(Message::TYPE_ROUTE_ROOT, $json['type']);
        $this->assertArrayHasKey('create', $json);
        $this->assertArrayHasKey('find', $json);
    }

    /**
     * Test find (success)
     */
    public function testRouteFindSuccess()
    {
        $service = new \Offloc\Prism\Domain\Model\Service\Service(
            'service key',
            'Some Service',
            'http://service.com'
        );

        $route = new \Offloc\Prism\Domain\Model\Route\Route(
            $service,
            'asdf',
            'http://example.com',
            'Some Name',
            array(
                'oapp-header' => 'Sample Header',
            )
        );

        $this->app['offloc.prism.domain.model.route.routeRepository'] = $this
            ->getMock('Offloc\Prism\Domain\Model\Route\RouteRepositoryInterface');
        $this->app['offloc.prism.domain.model.route.routeRepository']
            ->expects($this->exactly(2))
            ->method('find')
            ->with($this->equalTo($route->id()))
            ->will($this->returnValue($route));

        $client = $this->createClient();

        list($response, $json) = $this->traverseToRouteRoot($client);

        $client->request('POST', $json['find'], array('id' => $route->id()));
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);

        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_ROUTE_LINK, $json['type']);
        $this->assertArrayHasKey('link', $json);
        $this->assertEquals($json['link'], $response->headers->get('location'));

        $client->request('GET', $json['link']);
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_ROUTE_DETAIL, $json['type']);
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
        $service = new \Offloc\Prism\Domain\Model\Service\Service(
            'service key',
            'Some Service',
            'http://service.com'
        );

        $this->app['offloc.prism.requestAuthenticator'] = $this
            ->getMockBuilder('Offloc\Prism\Silex\App\Api\RequestAuthenticator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->app['offloc.prism.requestAuthenticator']
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($service));

        $route = new \Offloc\Prism\Domain\Model\Route\Route(
            $service,
            'asdf',
            'http://example.com',
            'Some Name',
            array(
                'oapp-header' => 'Sample Header',
            )
        );

        $this->app['offloc.prism.domain.model.route.routeFactory'] = $this
            ->getMockBuilder('Offloc\Prism\Domain\Model\Route\RouteFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->app['offloc.prism.domain.model.route.routeFactory']
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

        $this->app['offloc.prism.domain.model.route.routeRepository'] = $this
            ->getMock('Offloc\Prism\Domain\Model\Route\RouteRepositoryInterface');
        $this->app['offloc.prism.domain.model.route.routeRepository']
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo($route->id()))
            ->will($this->returnValue($route));
        $this->app['offloc.prism.domain.model.route.routeRepository']
            ->expects($this->once())
            ->method('store')
            ->will($this->returnSelf());

        $this->app['offloc.prism.domain.model.session'] = $this
            ->getMock('Offloc\Prism\Domain\Model\SessionInterface');
        $this->app['offloc.prism.domain.model.session']
            ->expects($this->once())
            ->method('flush')
            ->will($this->returnSelf());

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

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_ROUTE_LINK, $json['type']);
        $this->assertArrayHasKey('link', $json);
        $this->assertEquals($json['link'], $response->headers->get('location'));

        $client->request('GET', $json['link']);
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_ROUTE_DETAIL, $json['type']);
        $this->assertEquals('http://example.com', $json['target']);
        $this->assertEquals('Some Name', $json['name']);
        $this->assertEquals('asdf', $json['id']);
        $this->assertEquals('Sample Header', $json['headers']['oapp-header']);
        $this->assertArrayHasKey('link', $json['service']);
    }
}
