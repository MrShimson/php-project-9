<?php

namespace App;

final class Connection
{
    private static ?Connection $connection = null;

    public function connect(): \PDO
    {
        // Парсинг переменной окружения DATABASE_URL
        if (!isset($_ENV['DATABASE_URL'])) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../.');
            $dotenv->load();
        }

        $dbUrl = $_ENV['DATABASE_URL'];
        $parsedDbUrl = parse_url($dbUrl);
        // Подключение к базе данных PostgreSQL и возврат объекта PDO
        $connectionString = sprintf(
            "pgsql:host=%s;dbname=%s;user=%s;password=%s",
            $parsedDbUrl['host'],
            substr($parsedDbUrl['path'], 1),
            $parsedDbUrl['user'],
            $parsedDbUrl['pass']
        );

        $pdo = new \PDO($connectionString);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    public static function get(): Connection
    {
        if (self::$connection === null) {
            self::$connection = new self();
        }

        return self::$connection;
    }

    private function __construct()
    {
    }
}
