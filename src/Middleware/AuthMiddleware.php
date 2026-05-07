<?php
/*
 * Middleware de autenticação.
 * Verifica se existe uma sessão ativa antes de permitir acesso à rota.
 * Caso contrário, retorna 401.
 */
namespace App\Middleware;

use App\Helpers\Toast;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (empty($_SESSION['user'])) {
            $isApi = (
                str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api') ||
                ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            );
            if ($isApi) {
                http_response_code(401);
                echo json_encode(['error' => 'Não autenticado. Faça login para continuar.']);
                exit;
            }
            Toast::warning('Sessão expirada', 'Faça login novamente para continuar.');
            header('Location: /login');
            exit;
        }
    }
}