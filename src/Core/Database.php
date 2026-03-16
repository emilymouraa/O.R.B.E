<?php

/*
 * Classe responsável por gerenciar a conexão com o banco de dados.
 * Implementa um padrão Singleton para garantir que apenas uma instância
 * de conexão PDO seja criada e reutilizada durante toda a execução da aplicação.
 * Também centraliza o tratamento de erros de conexão.
 */

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        // Cria a conexão apenas se ainda não existir (Singleton)
        if (self::$instance === null) {

            // Carrega as configurações do banco a partir das variáveis de ambiente
            $host = Env::get('DB_HOST');
            $port = Env::get('DB_PORT');
            $db   = Env::get('DB_DATABASE');
            $user = Env::get('DB_USERNAME');
            $pass = Env::get('DB_PASSWORD');

            try {
                // Cria a instância PDO com charset UTF-8
                self::$instance = new PDO(
                    "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                    $user,
                    $pass
                );

                // Configura o PDO para lançar exceções em caso de erro
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {
                // Centraliza o tratamento de falhas de conexão
                self::handleError($e);
            }
        }

        return self::$instance;
    }

    private static function handleError(PDOException $e): void {
        http_response_code(500);

        // Se o modo debug estiver ativo, exibe detalhes do erro
        // (útil durante desenvolvimento)
        if (Env::get('APP_DEBUG') === 'true') {
            echo json_encode([
                'error' => 'Erro de conexão com banco',
                'message' => $e->getMessage()
            ]);
        } else {
            // Em produção, retorna apenas uma mensagem genérica
            echo json_encode([
                'error' => 'Erro interno. Contate o administrador.'
            ]);
        }

        exit;
    }
}