<?php
namespace App\Core;

use mysqli;
use mysqli_sql_exception;

class Database
{
    private static ?mysqli $conn = null;

    public static function connect(): mysqli
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        $config = self::config();

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            self::$conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['database'],
                $config['port']
            );

            self::$conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            die(json_encode([
                "status" => false,
                "message" => "Database connection failed"
            ]));
        }

        return self::$conn;
    }

    private static function config(): array
    {
        return [
<<<<<<< HEAD
            "host"     => getenv("DB_HOST")     ?: "127.0.0.1",
            "port"     => (int)(getenv("DB_PORT") ?: 3307),
            "user"     => getenv("DB_USER")     ?: "root",
            "password" => getenv("DB_PASSWORD") ?: "",
            "database" => getenv("DB_NAME")     ?: "yukai",
=======
            "host" => getenv("DB_HOST") ?: "127.0.0.1",
            "port" => (int) (getenv("DB_PORT") ?: 3307),
            "user" => getenv("DB_USER") ?: "root",
            "password" => getenv("DB_PASSWORD") ?: "",
            "database" => getenv("DB_NAME") ?: "yukai",
>>>>>>> e2125db42423580ca46278fc98b4fb7bbbc24280
        ];
    }
}
