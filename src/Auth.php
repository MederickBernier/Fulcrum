<?php
declare(strict_types=1);

namespace Fulcrum;

use PDO;

final class Auth{
    private const SESSION_KEY = 'fulcrum_user_id';
    private const TOKEN_EXPIRY = 3600; // 1 hour

    public function __construct(
        private readonly Database $db,
    ){}

    /*
    |----------------------------------------------------------------------
    | Session management
    |----------------------------------------------------------------------
    */

    public function start():void{
        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
    }

    public function check():bool{
        $this->start();
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public function user(): ?array{
        if($this->check()){
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT id, name, email, role FROM users where id=:id"
        );
        $stmt->execute(['id' => $_SESSION[self::SESSION_KEY]]);
        return $stmt->fetch() ?: null;
    }

    public function login(string $userId):void{
        $this->start();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $userId;
    }

    public function logout():void{
        $this->start();
        session_destroy();
    }

    /*
    |----------------------------------------------------------------------
    | Magic Link
    |----------------------------------------------------------------------
    */

    public function generateToken(string $email): ?string{
        // Find user by email
        $stmt = $this->db->prepare("
            SELECT id FROM users WHERE email = :email
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // If no user found return null silently
        // Don't reveal whether email exists
        if(!$user) return null;

        // Delete any existing unused token for this user
        $stmt = $this->db->prepare("
            DELETE FROM auth_tokens
            WHERE user_id = :user_id
            AND used_at IS NULL
        ");
        $stmt->execute(['user_id' => $user['id']]);

        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self:: TOKEN_EXPIRY);

        $stmt = $this->db->prepare("
            INSERT INTO auth_tokens (user_id, token, expires_at)
            VALUES (:user_id, :token, :expires_at)
        ");
        $stmt->execute([
            'user_id'       => $user['id'],
            'token'         => $token,
            'expires_at'    => $expiresAt,
        ]);
        return $token;
    }

    public function consumeToken(string $token):bool{
        // Find valid unused unexpired token
        $stmt = $this->db->prepare("
            SELECT t.id, t.user_id
            FROM auth_tokens t
            WHERE t.token = :token
            AND t.used_at IS NULL
            AND t.expires_at > NOW()
        ");
        $stmt->execute(['token' => $token]);
        $record = $stmt->fetch();

        if(!$record) return false;

        // Mark token as used
        $stmt = $this->db->prepare("
            UPDATE auth_tokens
            SET used_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $record['id']]);

        // Log the user in
        $this->login($record['user_id']);
        return true;
    }

    /*
    |----------------------------------------------------------------------
    | Flash Message
    |----------------------------------------------------------------------
    */

    public function flash(string $type, string $message):void{
        $_SESSION['_flash'][$type] = $message;
    }

    public function getFlash():array{
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    /*
    |----------------------------------------------------------------------
    | Require auth -  call at top of protected routes
    |----------------------------------------------------------------------
    */

    public function require(): void{
        if(!$this->check()){
            header("Location:/_fulcrum/login");
            exit;
        }
    }

    /*
    |----------------------------------------------------------------------
    | CSRF Protection
    |----------------------------------------------------------------------
    */

    public function csrfToken():string{
        if(empty($_SESSION['_csrf_token'])){
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public function verifyCsrf():void{
        $token = $_POST['_csrf_token'] ?? '';
        if(!hash_equals($_SESSION['_csrf_token'] ?? '', $token)){
            http_response_code(419);
            exit('CSRF token mismatch');
        }
    }
}