<?php

require_once __DIR__.'/../app.php';

Offloc\Prism\Silex\App\Api\ApiConfigurer::configure($app);

// Customizations to $app at this point only apply to the
// api aspect of the application.
