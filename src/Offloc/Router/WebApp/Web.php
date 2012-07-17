<?php

/**
 * This file is a part of offloc/router-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Router\WebApp;

/**
 * Web App
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class Web extends AbstractApp
{
    protected function configure()
    {
        parent::configure();

        $app = $this;
    }
}
