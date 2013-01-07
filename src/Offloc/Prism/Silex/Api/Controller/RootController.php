<?php

/**
 * This file is a part of offloc/prism-silex.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Prism\Silex\Api\Controller;

use Offloc\Prism\Api\Common\Message;
use Offloc\Prism\Silex\Api;

/**
 * Defines the Root API Controller
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class RootController extends AbstractController
{
    /**
     * Root action
     *
     * @return string
     */
    public function rootAction()
    {
        return $this->app->json(array(
            'type' => Message::TYPE_ROOT,
            'auth' => $this->generateUrl(Api::ROUTE_AUTH_ROOT, array(), true),
            'route' => $this->generateUrl(Api::ROUTE_ROUTE_ROOT, array(), true),
            'service' => $this->generateUrl(Api::ROUTE_SERVICE_ROOT, array(), true),
        ));
    }
}
