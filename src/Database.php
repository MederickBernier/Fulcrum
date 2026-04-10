<?php
declare(strict_types=1);

namespace Fulcrum;

use PDO;
use PDOException;
use RuntimeException;

final class Database{
    private static ?PDO $connection = null;

    public static function connect(): PDO{
        if(self::$connection !== null){
            return self::$connection;
        }

        $host       = $_ENV['DB_HOST']      ?? 'postgres';
        $port       = $_ENV['DB_PORT']      ?? '5432';
        $name       = $_ENV['DB_NAME']      ?? 'fulcrum';
        $user       = $_ENV['DB_USER']      ?? 'fulcrum';
        $password   = $_ENV['DB_PASSWORD']  ?? 'fulcrum';

        try{
            self::$connection = new PDO(
                dsn: "pgsql:host={$host};port={$port};dbname={$name}",
                username:$user,
                password:$password,
                options:[
                    PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES      => false,
                ]
            );
        }catch(PDOException $e){
            throw new RuntimeException(
                'Database connection failed: '.$e->getMessage()
            );
        }
        return self::$connection;
    }
}