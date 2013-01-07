<?php

/**
 * This file is a part of offloc/prism-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Prism\WebApp\Api\Controller;

use Offloc\Prism\Api\Common\Message;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Auth API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class AuthControllerTest extends AbstractControllerTest
{
    /**
     * Test auth root
     */
    public function testAuthRoot()
    {
        $client = $this->createClient();

        list($response, $json) = $this->traverseToAuthRoot($client);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_AUTH_ROOT, $json['type']);
        $this->assertArrayHasKey('authenticate', $json);
    }

    /**
     * Test authentication
     */
    public function testAuthAuthenticateSuccess()
    {
        $service = new \Offloc\Prism\Domain\Model\Service\Service(
            'service key',
            'Some Service',
            'http://service.com'
        );

        $this->app['offloc.prism.requestAuthenticator'] = $this
            ->getMockBuilder('Offloc\Prism\WebApp\Api\RequestAuthenticator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->app['offloc.prism.requestAuthenticator']
            ->expects($this->exactly(2))
            ->method('authenticate')
            ->will($this->returnValue($service));

        $this->app['offloc.prism.domain.model.service.serviceRepository'] = $this
            ->getMock('Offloc\Prism\Domain\Model\Service\ServiceRepositoryInterface');
        $this->app['offloc.prism.domain.model.service.serviceRepository']
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo($service->key()))
            ->will($this->returnValue($service));

        $client = $this->createClient();

        list($response, $json) = $this->traverseToAuthRoot($client);

        $client->request('POST', $json['authenticate']);
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);

        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_SERVICE_LINK, $json['type']);
        $this->assertArrayHasKey('link', $json);
        $this->assertEquals($json['link'], $response->headers->get('location'));

        $client->request('GET', $json['link']);
        $response = $client->getResponse();

        $json = json_decode($response->getContent(), true);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_SERVICE_DETAIL, $json['type']);
        $this->assertEquals('service key', $json['key']);
    }
}
