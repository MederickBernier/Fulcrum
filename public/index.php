<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/vendor/autoload.php';

use Fulcrum\Bootstrap;
use Fulcrum\Router;

$app = new Bootstrap();
$router = new Router($app, $app->twig);
$router->dispatch();