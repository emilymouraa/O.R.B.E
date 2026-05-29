<?php
/*
 * Controller responsável por retornar dados de unidades para uso na interface.
 * Por enquanto expõe apenas a listagem para popular o filtro do select.
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use PDO;

class UnidadeController extends Controller
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    /*
     * Retorna id e nome de todas as unidades ativas.
     * Rota: GET /api/unidades
     */
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);

        $stmt = $this->conn->prepare("
            SELECT id, nome
            FROM unidades
            WHERE ativa = true
            ORDER BY nome ASC
        ");
        $stmt->execute();

        $this->jsonResponse([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }
}