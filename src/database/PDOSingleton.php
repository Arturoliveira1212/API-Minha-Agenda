<?php

namespace MinhaAgenda\Database;

use PDO;
use PDOException;

class PDOSingleton {
    private static ?PDO $pdo = null;

    private static function conectar(): PDO {
        try {
            $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8";
            $username = $_ENV['DB_USERNAME'];
            $password = $_ENV['DB_PASSWORD'];
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public static function get(): PDO {
        if (!self::$pdo instanceof PDO) {
            self::$pdo = self::conectar();
        }

        return self::$pdo;
    }
}
