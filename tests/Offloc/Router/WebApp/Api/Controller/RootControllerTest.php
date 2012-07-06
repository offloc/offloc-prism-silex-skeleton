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

/**
 * Tests the Root API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class RootControllerTest extends AbstractControllerTest
{
    public function testRoot()
    {
        $client = $this->createClient();

        list($response, $json) = $this->makeRootRequest($client);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
        $this->assertEquals(Message::TYPE_ROOT, $json['type']);
        $this->assertArrayHasKey('auth', $json);
        $this->assertArrayHasKey('service', $json);
        $this->assertArrayHasKey('route', $json);
    }
}
