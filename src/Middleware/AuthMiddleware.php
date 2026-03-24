<?php
/*
 * Middleware de autenticação.
 * Verifica se existe uma sessão ativa antes de permitir acesso à rota.
 * Caso contrário, retorna 401.
 */
namespace App\Middleware;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (empty($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado. Faça login para continuar.']);
            exit;
        }
    }
}