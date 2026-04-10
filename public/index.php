<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
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

// Boot controllers
$dashboard = new DashboardController($twig, $db);
$content   = new ContentController($twig, $db);

// Routes
$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // Home
    $r->get('/', 'home');

    // Admin
    $r->get('/_fulcrum',        'admin.dashboard');
    $r->get('/_fulcrum/',       'admin.dashboard');

    // Content
    $r->get('/_fulcrum/content',              'content.index');
    $r->get('/_fulcrum/content/create',       'content.create');
    $r->post('/_fulcrum/content',             'content.store');
    $r->get('/_fulcrum/content/{id}/edit',    'content.edit');
    $r->post('/_fulcrum/content/{id}',        'content.update');
    $r->post('/_fulcrum/content/{id}/delete', 'content.delete');
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
        $dashboard,
        $content,
    ) {
        $handler = $routeInfo[1];
        $vars    = $routeInfo[2];

        match ($handler) {
            'home' => (function () use ($twig) {
                echo $twig->render('home.html.twig');
            })(),

            'admin.dashboard' => (function () use ($dashboard) {
                echo $dashboard->index();
            })(),

            'content.index' => (function () use ($content) {
                echo $content->index();
            })(),

            'content.create' => (function () use ($content) {
                echo $content->create();
            })(),

            'content.store' => (function () use ($content) {
                $content->store();
            })(),

            'content.edit' => (function () use ($content, $vars) {
                echo $content->edit($vars['id']);
            })(),

            'content.update' => (function () use ($content, $vars) {
                $content->update($vars['id']);
            })(),

            'content.delete' => (function () use ($content, $vars) {
                $content->delete($vars['id']);
            })(),

            default => (function () use ($twig) {
                http_response_code(404);
                echo $twig->render('errors/404.html.twig');
            })(),
        };
    })(),
};