<?php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = Env::get('DB_HOST');
            $port = Env::get('DB_PORT');
            $db   = Env::get('DB_DATABASE');
            $user = Env::get('DB_USERNAME');
            $pass = Env::get('DB_PASSWORD');
            try {
                self::$instance = new PDO(
                    "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                    $user,
                    $pass
                );
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                self::handleError($e);
            }
        }
        return self::$instance;
    }

    private static function handleError(PDOException $e): void {
        http_response_code(500);
        if (Env::get('APP_DEBUG') === 'true') {
            echo json_encode([
                'error' => 'Erro de conexão com banco',
                'message' => $e->getMessage()
            ]);
        } else {
            echo json_encode([
                'error' => 'Erro interno. Contate o administrador.'
            ]);
        }

        exit;
    }
}