<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/vendor/autoload.php';

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use function FastRoute\simpleDispatcher;

//Boot Twig
$loader = new FilesystemLoader(dirname(__DIR__).'/resources/templates');
$twig = new Environment($loader,[
    'cache'       => false,
    'debug'       => true,
    'auto_reload' =>  true
]);

$dispatcher = simpleDispatcher(function(RouteCollector $r){
    $r->get('/', 'home');
    $r->get('/_fulcrum', 'admin.dashboard');
    $r->get('/_fulcrum/', 'admin.dashboard');
});

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

//strip query string
if(str_contains($uri, '?')){
    $uri = strstr($uri, '?',true);
}

$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($method, $uri);

match($routeInfo[0]){
    Dispatcher::NOT_FOUND => (function() use ($twig){
        http_response_code(404);
        echo $twig->render('errors/404.html.twig');
    })(),
    Dispatcher::METHOD_NOT_ALLOWED => (function() use ($twig){
        http_response_code(405);
        echo '405 - Method not allowed';
    })(),
    Dispatcher::FOUND => (function() use ($routeInfo, $twig){
        echo match($routeInfo[1]){
            'home'              => $twig->render('home.html.twig'),
            'admin.dashboard'   => $twig->render('admin/dashboard/index.html.twig'),
            default             => $twig->render('errors/404.html.twig'),
        };
    })(),
};