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

use Symfony\Component\HttpFoundation\Request;
use Offloc\Router\Api\Common\Util\String;
use Offloc\Router\Domain\Model\Service\ServiceRepositoryInterface;

/**
 * Defines the Request Authenticator
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class RequestAuthenticator
{
    /**
     * @var int
     */
    protected $allowedDrift = 300;

    /**
     * @var ServiceRepositoryInterface
     */
    protected $serviceRepository;

    /**
     * Constructor
     *
     * @param ServiceRepositoryInterface $serviceRepository Service repository
     */
    public function __construct(ServiceRepositoryInterface $serviceRepository)
    {
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Set allowed drift
     *
     * Seconds.
     *
     * @param int $allowedDrift Allowed drift in seconds
     */
    public function setAllowedDrift($allowedDrift)
    {
        $this->allowedDrift = $allowedDrift;
    }

    /**
     * Authenticate a request
     *
     * @param Request $request Request
     *
     * @return \Offloc\Router\Domain\Model\Service\Service
     */
    public function authenticate(Request $request)
    {
        $authorization = array();

        if (!preg_match('/^OfflocRouter (.+)$/', $request->headers->get('authorization'), $matches)) {
            // TODO: This should throw something useful.
            throw new \Exception("Invalid authorization header, could not authenticate");
        }

        foreach (explode(', ', $matches[1]) as $part) {
            if (preg_match('/^(.+?)=(.+)$/', $part, $keyAndValue)) {
                list($dummy, $key, $value) = $keyAndValue;
                $authorization[strtolower($key)] = $value;
            }
        }

        // TODO: This should be wrapped and should throw something useful if finding
        // the service fails for some reason.
        $service = $this->serviceRepository->find($authorization['credential']);

        $signedItems = array(
            $request->getHost(),
            $request->getMethod(),
            $request->getPathInfo(),
            md5($request->getQueryString()),
        );

        $headers = $request->headers->all();
        uksort($headers, 'strnatcasecmp');

        foreach ($headers as $key => $value) {
            if (
                'content-' === substr(strtolower($key), 0, 8) ||
                'date' === strtolower($key) ||
                'expires' === strtolower($key) ||
                'host' === strtolower($key) ||
                'x-offloc-router-' === substr(strtolower($key), 0, 16)
            ) {
                $signedItems[] = $value[0];
            }
        }

        $stringToSign = implode("\n", $signedItems);

        $signature = String::hexToBase64(hash_hmac('sha256', $stringToSign, $service->secret()));

        if ($signature !== $authorization['signature']) {
            // TODO: This should throw something useful.
            throw new \Exception("Invalid signature, could not authenticate");
        }

        $time = strtotime($request->headers->get('date'));

        $now = time();

        if ($now > $time && $now - $time > $this->allowedDrift) {
            // TODO: This should throw something useful.
            throw new \Exception("Expired request, could not authenticate");
        }

        if ($now < $time && $time - $now > $this->allowedDrift) {
            // TODO: This should throw something useful.
            throw new \Exception("Request expires too far into the future, could not authenticate");
        }

        return $service;
    }
}
