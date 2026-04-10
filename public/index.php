<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Fulcrum\Auth;
use Fulcrum\Controllers\AuthController;
use Fulcrum\Controllers\ContentController;
use Fulcrum\Controllers\DashboardController;
use Fulcrum\Database;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use function FastRoute\simpleDispatcher;

// Boot Twig
$loader = new FilesystemLoader(dirname(__DIR__) . '/resources/templates');
$twig   = new Environment($loader, [
    'cache'       => false,
    'debug'       => true,
    'auto_reload' => true,
]);

// Boot database
$db = Database::connect();

// Boot auth
$auth = new Auth($db);
$auth->start();

// Make flash messages available in all templates
$twig->addGlobal('flash', $auth->getFlash());

// Boot controllers
$dashboard = new DashboardController($twig, $db, $auth);
$content   = new ContentController($twig, $db, $auth);
$authCtrl  = new AuthController($twig, $auth);

// Routes
$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // Home
    $r->get('/', 'home');

    // Auth
    $r->get('/_fulcrum/login',       'auth.login');
    $r->post('/_fulcrum/login',      'auth.send');
    $r->get('/_fulcrum/auth/{token}','auth.consume');
    $r->post('/_fulcrum/logout',     'auth.logout');

    // Admin — protected
    $r->get('/_fulcrum',       'admin.dashboard');
    $r->get('/_fulcrum/',      'admin.dashboard');

    // Content — protected
    $r->get('/_fulcrum/content',               'content.index');
    $r->get('/_fulcrum/content/create',        'content.create');
    $r->post('/_fulcrum/content',              'content.store');
    $r->get('/_fulcrum/content/{id}/edit',     'content.edit');
    $r->post('/_fulcrum/content/{id}',         'content.update');
    $r->post('/_fulcrum/content/{id}/delete',  'content.delete');
});

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

if (str_contains($uri, '?')) {
    $uri = strstr($uri, '?', true);
}

$uri       = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($method, $uri);

match ($routeInfo[0]) {
    Dispatcher::NOT_FOUND => (function () use ($twig) {
        http_response_code(404);
        echo $twig->render('errors/404.html.twig');
    })(),

    Dispatcher::METHOD_NOT_ALLOWED => (function () {
        http_response_code(405);
        echo '405 — Method not allowed.';
    })(),

    Dispatcher::FOUND => (function () use (
        $routeInfo,
        $twig,
        $auth,
        $dashboard,
        $content,
        $authCtrl,
    ) {
        $handler = $routeInfo[1];
        $vars    = $routeInfo[2];

        match ($handler) {
            // Public
            'home' => (function () use ($twig) {
                echo $twig->render('home.html.twig');
            })(),

            // Auth routes — public
            'auth.login' => (function () use ($authCtrl) {
                echo $authCtrl->login();
            })(),

            'auth.send' => (function () use ($authCtrl) {
                $authCtrl->sendLink();
            })(),

            'auth.consume' => (function () use ($authCtrl, $vars) {
                $authCtrl->consumeLink($vars['token']);
            })(),

            'auth.logout' => (function () use ($authCtrl) {
                $authCtrl->logout();
            })(),

            // Protected routes
            'admin.dashboard' => (function () use ($auth, $dashboard) {
                $auth->require();
                echo $dashboard->index();
            })(),

            'content.index' => (function () use ($auth, $content) {
                $auth->require();
                echo $content->index();
            })(),

            'content.create' => (function () use ($auth, $content) {
                $auth->require();
                echo $content->create();
            })(),

            'content.store' => (function () use ($auth, $content) {
                $auth->require();
                $content->store();
            })(),

            'content.edit' => (function () use ($auth, $content, $vars) {
                $auth->require();
                echo $content->edit($vars['id']);
            })(),

            'content.update' => (function () use ($auth, $content, $vars) {
                $auth->require();
                $content->update($vars['id']);
            })(),

            'content.delete' => (function () use ($auth, $content, $vars) {
                $auth->require();
                $content->delete($vars['id']);
            })(),

            default => (function () use ($twig) {
                http_response_code(404);
                echo $twig->render('errors/404.html.twig');
            })(),
        };
    })(),
};