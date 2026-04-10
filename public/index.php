<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/vendor/autoload.php';

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

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
    Dispatcher::NOT_FOUND => (function(){
        http_response_code(404);
        echo '404 - Not Found';
    })(),
    Dispatcher::METHOD_NOT_ALLOWED => (function(){
        http_response_code(405);
        echo '405 - Method not allowed';
    })(),
    Dispatcher::FOUND => (function() use ($routeInfo){
        echo match($routeInfo[1]){
            'home' => 'Fulcrum is alive',
            'admin.dashboard' => 'Admin panel coming soon',
            default => '404 - Not Found',
        };
    })(),
};