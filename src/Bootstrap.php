<?php
declare(strict_types=1);

namespace Fulcrum;

use Fulcrum\Controllers\AuthController;
use Fulcrum\Controllers\ContentController;
use Fulcrum\Controllers\DashboardController;
use Fulcrum\Controllers\FrontendController;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class Bootstrap{
    public readonly Environment             $twig;
    public readonly Database                $db;
    public readonly Auth                    $auth;
    public readonly DashboardController     $dashboard;
    public readonly ContentController       $content;
    public readonly AuthController          $authCtrl;
    public readonly FrontendController      $frontend;

    public function __construct(){
        $this->twig = $this->bootTwig();
        $this->db = Database::connect();
        $this->auth = new Auth($this->db);
        
        $this->auth->start();
        $this->bootTwigGlobals();

        $this->dashboard = new DashboardController($this->twig, $this->db, $this->auth);
        $this->content = new ContentController($this->twig, $this->db, $this->auth);
        $this->authCtrl = new AuthController($this->twig, $this->auth);
        $this->frontend = new FrontendController($this->twig, $this->db);
    }

    /*
    |----------------------------------------------------------------------
    | Internal boot methods
    |----------------------------------------------------------------------
    */

    private function bootTwig(): Environment{
        $loader = new FilesystemLoader(
            dirname(__DIR__).'/resources/templates'
        );

        return new Environment($loader,[
            'cache'         => false,
            'debug'         => true,
            'auto_reload'   => 'true'
        ]);
    }

    private function bootTwigGlobals():void{
        $this->twig->addGlobal('flash', $this->auth->getFlash());
        $this->twig->addGlobal('csrf_token', $this->auth->csrfToken());
    }
}