<?php
/*
 * Middleware de autorização por perfil (role).
 * Recebe os roles permitidos e bloqueia acesso caso o usuário
 * autenticado não possua o perfil necessário. Retorna 403.
 */
namespace App\Middleware;

class RoleMiddleware
{
    public static function handle(array $allowedRoles): void
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], $allowedRoles, true)) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado. Permissão insuficiente.']);
            exit;
        }
    }
}