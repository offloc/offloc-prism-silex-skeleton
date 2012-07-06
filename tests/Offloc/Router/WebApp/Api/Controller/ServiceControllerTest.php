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

use Offloc\Router\Api\Common\Message;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Service API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class ServiceControllerTest extends AbstractControllerTest
{
    /**
     * Test route root
     */
    public function testServiceRoot()
    {
        $client = $this->createClient();

        list($response, $json) = $this->traverseToServiceRoot($client);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_SERVICE_ROOT, $json['type']);
        $this->assertArrayHasKey('create', $json);
        $this->assertArrayHasKey('find', $json);
    }

    /**
     * Test find (success)
     */
    public function testServiceFindSuccess()
    {
        $service = new \Offloc\Router\Domain\Model\Service\Service(
            'some key',
            'Some Name',
            'http://example.com'
        );

        $this->app['offloc.router.domain.model.service.serviceRepository'] = $this
            ->getMock('Offloc\Router\Domain\Model\Service\ServiceRepositoryInterface');
        $this->app['offloc.router.domain.model.service.serviceRepository']
            ->expects($this->exactly(2))
            ->method('find')
            ->with($this->equalTo($service->key()))
            ->will($this->returnValue($service));

        $this->app['offloc.router.requestAuthenticator'] = $this
            ->getMockBuilder('Offloc\Router\WebApp\Api\RequestAuthenticator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->app['offloc.router.requestAuthenticator']
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($service));

        $client = $this->createClient();

        list($response, $json) = $this->traverseToServiceRoot($client);

        $client->request('POST', $json['find'], array('key' => $service->key()));
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);

        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_SERVICE_LINK, $json['type']);
        $this->assertArrayHasKey('link', $json);

        $client->request('GET', $json['link']);
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_SERVICE_DETAIL, $json['type']);
        $this->assertEquals('some key', $json['key']);
        $this->assertEquals('Some Name', $json['name']);
        $this->assertEquals('http://example.com', $json['url']);
    }
}
