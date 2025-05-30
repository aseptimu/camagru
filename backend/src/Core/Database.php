<?php
namespace Camagru\Core;

use PDO;
class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $cfg = include __DIR__ . '/../../config/database.php';

            self::$pdo = new PDO(
                $cfg['dsn'],
                $cfg['user'],
                $cfg['password'],
                $cfg['options'],
            );
        }

        return self::$pdo;
    }
}