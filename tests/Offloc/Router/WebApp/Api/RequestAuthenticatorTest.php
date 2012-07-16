<?php

/**
 * This file is a part of offloc/router-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Router\WebApp\Api;

use Offloc\Router\Api\Common\Util\String;
use Offloc\Router\WebApp\Api\RequestAuthenticator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Request Authenticator
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class RequestAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    protected function createMockServiceRepository(\Offloc\Router\Domain\Model\Service\Service $service)
    {
        $serviceRepository = $this
            ->getMockBuilder('Offloc\Router\Domain\Model\Service\ServiceRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceRepository
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo($service->key()))
            ->will($this->returnValue($service));

        return $serviceRepository;
    }

    protected function makeAuthenticateRequest($service, $body, $host, $verb, $contentType, $resource, $time, $queryString, $additionalHeaders, $tweakServerCallback = null)
    {
        $queryString = String::normalizeQueryString($queryString);
        $script = 'script.php';
        $date = gmdate("D, d M Y H:i:s", $time)." GMT";
        $scriptName = '/path/to/' . $script;
        $scriptFilename = '/webroot/' . $script;
        $contentMd5 = String::hexToBase64(md5($body));

        $server = array_merge(array(
            'SERVER_NAME' => $host,
            'REQUEST_METHOD' => $verb,
            'REQUEST_URI' => $scriptName . $resource,
            'PATH_INFO' => $resource,
            'SCRIPT_NAME' => $scriptName,
            'SCRIPT_FILENAME' => $scriptFilename,
            'QUERY_STRING' => $queryString,
        ), $additionalHeaders);

        $server['HTTP_CONTENT_MD5'] = $contentMd5;
        $server['HTTP_CONTENT_TYPE'] = $contentType;
        $server['HTTP_DATE'] = $date;

        $signedItems = array($host, $verb, $resource, md5($queryString));

        uksort($server, 'strnatcasecmp');

        foreach ($server as $key => $value) {
            $value = str_replace(array("\r", "\n"), '', $value);

            if ('' === $value) {
                unset($server[$key]);

                continue;
            }

            $server[$key] = $value;

            if (
                'http_content_' === substr(strtolower($key), 0, 13) ||
                'http_date' === strtolower($key) ||
                'http_expires' === strtolower($key) ||
                'http_host' === strtolower($key) ||
                'http_x_offloc_router_' === substr(strtolower($key), 0, 21)
            ) {
                $signedItems[] = $value;
            }
        }

        $stringToSign = implode("\n", $signedItems);

        $server['HTTP_AUTHORIZATION'] = 'OfflocRouter Credential=' . $service->key(). ", Signature=" . String::hexToBase64(hash_hmac('sha256', $stringToSign, $service->secret()));

        $getParams = array();
        parse_str($queryString, $getParams);

        if (null !== $tweakServerCallback) {
            $server = $tweakServerCallback($server);
        }

        $request = new Request($getParams, array(), array(), array(), array(), $server, $body);

        return $request;
    }

    /**
     * Test authenticate method (success)
     */
    public function testAuthenticateSuccess()
    {

        $testService = new \Offloc\Router\Domain\Model\Service\Service(
            'serviceKey',
            'Some Service',
            'http://service.com'
        );

        $request = $this->makeAuthenticateRequest(
            $testService,
            '{"Hello": "World!"}',
            'api.example.com',
            'POST',
            'application/json',
            '/hello/world',
            time(),
            'sort=foo&limit=5&offset=3&a[]=1',
            array(
                'HTTP_X_OFFLOC_ROUTER_SAMPLE_HEADER' => 'Sample Header Value',
                'HTTP_X_RANDOM_HEADER' => 'Ignored In Signature',
            )
        );

        $serviceRepository = $this->createMockServiceRepository($testService);

        $requestAuthenticator = new RequestAuthenticator($serviceRepository);

        $service = $requestAuthenticator->authenticate($request);

        $this->assertEquals($testService->key(), $service->key());
    }

    /**
     * Test authenticate method (failed, expired)
     */
    public function testAuthenticateFailedExpired()
    {

        $testService = new \Offloc\Router\Domain\Model\Service\Service(
            'serviceKey',
            'Some Service',
            'http://service.com'
        );

        $request = $this->makeAuthenticateRequest(
            $testService,
            '{"Hello": "World!"}',
            'api.example.com',
            'POST',
            'application/json',
            '/hello/world',
            time() - 500,
            'sort=foo&limit=5&offset=3&a[]=1',
            array(
                'HTTP_X_OFFLOC_ROUTER_SAMPLE_HEADER' => 'Sample Header Value',
                'HTTP_X_RANDOM_HEADER' => 'Ignored In Signature',
            )
        );

        $serviceRepository = $this->createMockServiceRepository($testService);

        $requestAuthenticator = new RequestAuthenticator($serviceRepository);

        $requestAuthenticator->setAllowedDrift(100);

        try {
            $service = $requestAuthenticator->authenticate($request);

            $this->fail('Should have thrown an exception (expired)');
        } catch (\Exception $e) {
            $this->assertContains('Expired', $e->getMessage());
        }
    }

    /**
     * Test authenticate method (failed, fure)
     */
    public function testAuthenticateFailedFuture()
    {

        $testService = new \Offloc\Router\Domain\Model\Service\Service(
            'serviceKey',
            'Some Service',
            'http://service.com'
        );

        $request = $this->makeAuthenticateRequest(
            $testService,
            '{"Hello": "World!"}',
            'api.example.com',
            'POST',
            'application/json',
            '/hello/world',
            time() + 500,
            'sort=foo&limit=5&offset=3&a[]=1',
            array(
                'HTTP_X_OFFLOC_ROUTER_SAMPLE_HEADER' => 'Sample Header Value',
                'HTTP_X_RANDOM_HEADER' => 'Ignored In Signature',
            )
        );

        $serviceRepository = $this->createMockServiceRepository($testService);

        $requestAuthenticator = new RequestAuthenticator($serviceRepository);

        $requestAuthenticator->setAllowedDrift(100);

        try {
            $service = $requestAuthenticator->authenticate($request);

            $this->fail('Should have thrown an exception (future)');
        } catch (\Exception $e) {
            $this->assertContains('future', $e->getMessage());
        }
    }

    /**
     * Test authenticate method (failed, malformed auth header)
     */
    public function testAuthenticateFailedMalformedAuthHeader()
    {

        $testService = new \Offloc\Router\Domain\Model\Service\Service(
            'serviceKey',
            'Some Service',
            'http://service.com'
        );

        $request = $this->makeAuthenticateRequest(
            $testService,
            '{"Hello": "World!"}',
            'api.example.com',
            'POST',
            'application/json',
            '/hello/world',
            time() + 500,
            'sort=foo&limit=5&offset=3&a[]=1',
            array(
                'HTTP_X_OFFLOC_ROUTER_SAMPLE_HEADER' => 'Sample Header Value',
                'HTTP_X_RANDOM_HEADER' => 'Ignored In Signature',
            ),
            function($server) {
                $server['HTTP_AUTHORIZATION'] = 'BREAK' . $server['HTTP_AUTHORIZATION'];

                return $server;
            }
        );

        $serviceRepository = $this
            ->getMockBuilder('Offloc\Router\Domain\Model\Service\ServiceRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $requestAuthenticator = new RequestAuthenticator($serviceRepository);

        $requestAuthenticator->setAllowedDrift(100);

        try {
            $service = $requestAuthenticator->authenticate($request);

            $this->fail('Should have thrown an exception (malformed auth header)');
        } catch (\Exception $e) {
            $this->assertContains('Invalid authorization header', $e->getMessage());
        }
    }

    /**
     * Test authenticate method (failed, invalid signature)
     */
    public function testAuthenticateFailedInvalidSignature()
    {

        $testService = new \Offloc\Router\Domain\Model\Service\Service(
            'serviceKey',
            'Some Service',
            'http://service.com'
        );

        $request = $this->makeAuthenticateRequest(
            $testService,
            '{"Hello": "World!"}',
            'api.example.com',
            'POST',
            'application/json',
            '/hello/world',
            time(),
            'sort=foo&limit=5&offset=3&a[]=1',
            array(
                'HTTP_X_OFFLOC_ROUTER_SAMPLE_HEADER' => 'Sample Header Value',
                'HTTP_X_RANDOM_HEADER' => 'Ignored In Signature',
            ),
            function($server) {
                // Remove a charater from the authorization header, specifically the first
                // character in the signature. We want to have everything else work exactly
                // as it should except that the signature should be altered slightly.
                $server['HTTP_AUTHORIZATION'] = preg_replace('/(Signature\=)(.+?)(.+)$/', '$1$3', $server['HTTP_AUTHORIZATION']);

                return $server;
            }
        );

        $serviceRepository = $this->createMockServiceRepository($testService);

        $requestAuthenticator = new RequestAuthenticator($serviceRepository);

        $requestAuthenticator->setAllowedDrift(100);

        try {
            $service = $requestAuthenticator->authenticate($request);

            $this->fail('Should have thrown an exception (future)');
        } catch (\Exception $e) {
            $this->assertContains('Invalid signature', $e->getMessage());
        }
    }
}
