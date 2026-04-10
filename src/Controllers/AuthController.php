<?php

declare(strict_types=1);

namespace Fulcrum\Controllers;

use Fulcrum\Auth;
use Twig\Environment;

final class AuthController{
    public function __construct(
        private readonly Environment    $twig,
        private readonly Auth           $auth,
    ){}

    public function login(): string
    {
        if ($this->auth->check()) {
            header('Location: /_fulcrum');
            exit;
        }

        // No session_start() here either — already started
        $devLink = $_SESSION['dev_magic_link'] ?? null;
        unset($_SESSION['dev_magic_link']);

        $sent  = isset($_GET['sent']);
        $error = $_GET['error'] ?? null;

        return $this->twig->render('admin/auth/login.html.twig', [
            'sent'     => $sent,
            'error'    => $error,
            'dev_link' => $devLink,
        ]);
    }

    public function sendLink(): void
    {
        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: /_fulcrum/login');
            exit;
        }

        $token = $this->auth->generateToken($email);

        if ($token !== null) {
            $link = sprintf(
                '%s/_fulcrum/auth/%s',
                $_ENV['APP_URL'] ?? 'http://localhost:8080',
                $token
            );

            // Store in session via Auth which already started it
            $_SESSION['dev_magic_link'] = $link;
        }

        header('Location: /_fulcrum/login?sent=1');
        exit;
    }

    public function consumeLink(string $token): void{
        if($this->auth->consumeToken($token)){
            header("Location:/_fulcrum");
            exit;
        }

        // Invalid or expired token
        Header("Location:/_fulcrum/login?error=invalid_token");
        exit;
    }

    public function logout(): void{
        $this->auth->logout();
        header("Location:/_fulcrum/login");
        exit;
    }
}