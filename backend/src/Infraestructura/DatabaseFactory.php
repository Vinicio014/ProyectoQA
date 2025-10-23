<?php

namespace App\Infraestructura;

use App\Infraestructura\Interfaces\DatabaseInterface;
use Database;
use PDO;
use Exception;

require_once __DIR__ . '/../../conf/database.php';

class DatabaseFactory
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $database = new Database();
            self::$instance = $database->getConnection();
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}