<?php

/**
 * This file is a part of offloc/prism-silex-app.
 *
 * (c) Offloc Incorporated
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Offloc\Prism\WebApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;

/**
 * Admin App
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class Admin extends AbstractApp
{
    protected function configure()
    {
        parent::configure();

        $app = $this;

        $app->register(new \Silex\Provider\TwigServiceProvider, array(
            'twig.path' => array(
                __DIR__ . '/Admin/Resources/views',
            ),
        ));

        $app->register(new \Silex\Provider\SessionServiceProvider);
        $app->register(new \Silex\Provider\SecurityServiceProvider);

        $app['offloc.prism.admin.users'] = $app['offloc.prism.projectRoot'].'/config/admin.users.json';

        $app['security.firewalls'] = array(
            'login' => array(
                'pattern' => '^/login$',
                'anonymous' => true,
            ),
            'secured' => array(
                'pattern' => '^.*$',
                'form' => array('login_path' => '/login', 'check_path' => '/login_check'),
                'logout' => array('logout_path' => '/logout'),
                'users' => $app->share(function($app) {
                    $adminUsersFile = $app['offloc.prism.admin.users'];

                    $users = array();

                    if (file_exists($adminUsersFile)) {
                        $users = json_decode(file_get_contents($adminUsersFile), true);
                    }

                    return new InMemoryUserProvider($users);
                }),
            ),
        );

        $app->get('/', function() use ($app) {
            return $app['twig']->render('home.html.twig');
        })->bind('offloc_prism_admin_home');

        $app->post('/search', function(Request $request) use ($app) {
            $routeRepository = $app['offloc.prism.domain.model.route.routeRepository'];

            $route = $routeRepository->find($request->get('query'));

            return $app->redirect($app['url_generator']->generate(
                'offloc_prism_admin_route_detail',
                array('routeId' => $route->id(),)
            ));
        })->bind('offloc_prism_admin_search');

        $app->get('/services', function() use ($app) {
            return $app['twig']->render('service_list.html.twig', array(
                'services' => $app['offloc.prism.domain.model.service.serviceRepository']->findAll(),
            ));
        })->bind('offloc_prism_admin_service_root');

        $app->post('/services', function(Request $request) use ($app) {
            $serviceFactory = $app['offloc.prism.domain.model.service.serviceFactory'];
            $serviceRepository = $app['offloc.prism.domain.model.service.serviceRepository'];
            $session = $app['offloc.prism.domain.model.session'];

            $service = $serviceFactory->create($request->get('name'), $request->get('url'));

            $serviceRepository->store($service);

            $session->flush();

            return $app->redirect($app['url_generator']->generate(
                'offloc_prism_admin_service_detail',
                array('serviceKey' => $service->key(),))
            );
        })->bind('offloc_prism_admin_service_create');

        $app->get('/services/{serviceKey}', function($serviceKey) use ($app) {
            $serviceRepository = $app['offloc.prism.domain.model.service.serviceRepository'];
            $routeRepository = $app['offloc.prism.domain.model.route.routeRepository'];

            $service = $serviceRepository->find($serviceKey);

            return $app['twig']->render('service_detail.html.twig', array(
                'service' => $service,
                'routes' => $routeRepository->findByService($service),
            ));
        })->bind('offloc_prism_admin_service_detail');

        $app->post('/services/{serviceKey}', function(Request $request, $serviceKey) use ($app) {
            $serviceRepository = $app['offloc.prism.domain.model.service.serviceRepository'];

            $sevice = $serviceRepository->find($serviceKey);

            $service->setName($request->get('name'));
            $service->setUrl($request->get('url'));

            return $app['twig']->render('service_detail.html.twig', array(
                'service' => $serviceRepository->find($serviceKey),
            ));
        })->bind('offloc_prism_admin_service_update');

        $app->post('/routes', function(Request $request) use ($app) {
            $serviceRepository = $app['offloc.prism.domain.model.service.serviceRepository'];
            $routeFactory = $app['offloc.prism.domain.model.route.routeFactory'];
            $routeRepository = $app['offloc.prism.domain.model.route.routeRepository'];
            $session = $app['offloc.prism.domain.model.session'];

            $route = $routeFactory->create(
                $serviceRepository->find($request->get('serviceKey')),
                $request->get('target'),
                $request->get('name'),
                $request->get('suggestion') ?: null
            );

            $routeRepository->store($route);

            $session->flush();

            return $app->redirect($app['url_generator']->generate(
                'offloc_prism_admin_route_detail',
                array('routeId' => $route->id(),))
            );
        })->bind('offloc_prism_admin_route_create');

        $app->get('/routes/{routeId}', function($routeId) use ($app) {
            $routeRepository = $app['offloc.prism.domain.model.route.routeRepository'];

            $route = $routeRepository->find($routeId);

            return $app['twig']->render('route_detail.html.twig', array(
                'route' => $route,
            ));
        })->bind('offloc_prism_admin_route_detail');

        $app->get('/login', function(Request $request) use ($app) {
            return $app['twig']->render('login.html.twig', array(
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            ));
        })->bind('offloc_prism_admin_login');
    }
}
