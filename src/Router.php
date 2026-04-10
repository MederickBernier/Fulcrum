<?php
declare(strict_types=1);

namespace Fulcrum;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Twig\Environment;

use function FastRoute\simpleDispatcher;

final class Router{
    private readonly Dispatcher $dispatcher;

    public function __construct(
        private readonly Bootstrap $app, 
        private readonly Environment $twig
    ){
        $this->dispatcher = simpleDispatcher(
            function(RouteCollector $r){
                $this->registerRoutes($r);
            }
        );
    }

    /*
    |----------------------------------------------------------------------
    | Dispatch incoming request
    |----------------------------------------------------------------------
    */

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $_SERVER['REQUEST_URI'];

        if (str_contains($uri, '?')) {
            $uri = strstr($uri, '?', true);
        }

        $uri       = rawurldecode($uri);
        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => (function () {
                http_response_code(404);
                echo $this->twig->render('errors/404.html.twig');
            })(),

            Dispatcher::METHOD_NOT_ALLOWED => (function () {
                http_response_code(405);
                echo '405 — Method not allowed.';
            })(),

            Dispatcher::FOUND => $this->handle(
                handler: $routeInfo[1],
                vars:    $routeInfo[2],
            ),
        };
    }

    /*
    |----------------------------------------------------------------------
    | Route definitions
    |----------------------------------------------------------------------
    */

    private function registerRoutes(RouteCollector $r): void
    {
        // Auth
        $r->get('/_fulcrum/login',        'auth.login');
        $r->post('/_fulcrum/login',       'auth.send');
        $r->get('/_fulcrum/auth/{token}', 'auth.consume');
        $r->post('/_fulcrum/logout',      'auth.logout');

        // Admin — protected
        $r->get('/_fulcrum',  'admin.dashboard');
        $r->get('/_fulcrum/', 'admin.dashboard');

        // Content — protected
        $r->get('/_fulcrum/content',              'content.index');
        $r->get('/_fulcrum/content/create',       'content.create');
        $r->post('/_fulcrum/content',             'content.store');
        $r->get('/_fulcrum/content/{id}/edit',    'content.edit');
        $r->post('/_fulcrum/content/{id}',        'content.update');
        $r->post('/_fulcrum/content/{id}/delete', 'content.delete');

        // Public frontend — must be last
        $r->get('/',       'frontend.home');
        $r->get('/{slug}', 'frontend.show');
    }

    /*
    |----------------------------------------------------------------------
    | Route handler dispatch
    |----------------------------------------------------------------------
    */

      private function handle(string $handler, array $vars): void
    {
        $app  = $this->app;
        $auth = $app->auth;

        match ($handler) {
            // Public frontend
            'frontend.home' => (function () use ($app) {
                echo $app->frontend->home();
            })(),

            'frontend.show' => (function () use ($app, $vars) {
                echo $app->frontend->show($vars['slug']);
            })(),

            // Auth
            'auth.login' => (function () use ($app) {
                echo $app->authCtrl->login();
            })(),

            'auth.send' => (function () use ($app, $auth) {
                $auth->verifyCsrf();
                $app->authCtrl->sendLink();
            })(),

            'auth.consume' => (function () use ($app, $vars) {
                $app->authCtrl->consumeLink($vars['token']);
            })(),

            'auth.logout' => (function () use ($app, $auth) {
                $auth->verifyCsrf();
                $app->authCtrl->logout();
            })(),

            // Admin — protected
            'admin.dashboard' => (function () use ($app, $auth) {
                $auth->require();
                echo $app->dashboard->index();
            })(),

            // Content — protected
            'content.index' => (function () use ($app, $auth) {
                $auth->require();
                echo $app->content->index();
            })(),

            'content.create' => (function () use ($app, $auth) {
                $auth->require();
                echo $app->content->create();
            })(),

            'content.store' => (function () use ($app, $auth) {
                $auth->require();
                $auth->verifyCsrf();
                $app->content->store();
            })(),

            'content.edit' => (function () use ($app, $auth, $vars) {
                $auth->require();
                echo $app->content->edit($vars['id']);
            })(),

            'content.update' => (function () use ($app, $auth, $vars) {
                $auth->require();
                $auth->verifyCsrf();
                $app->content->update($vars['id']);
            })(),

            'content.delete' => (function () use ($app, $auth, $vars) {
                $auth->require();
                $auth->verifyCsrf();
                $app->content->delete($vars['id']);
            })(),

            default => (function () {
                http_response_code(404);
                echo $this->twig->render('errors/404.html.twig');
            })(),
        };
    }
}