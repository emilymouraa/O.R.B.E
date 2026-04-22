<?php
/*
 * Controller do Banco de Talentos.
 * Expõe 3 endpoints:
 *   GET /api/banco-talentos/indicadores
 *   GET /api/banco-talentos/ranking
 *   GET /api/banco-talentos/busca?q=termo
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Models\BancoTalentosModel;
use PDO;

class BancoTalentosController extends Controller
{
    private BancoTalentosModel $model;

    public function __construct(PDO $conn)
    {
        AuthMiddleware::handle();
        $this->model = new BancoTalentosModel($conn);
    }

    public function indicadores(): void
    {
        $data = $this->model->getIndicadores();
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function ranking(): void
    {
        $data = $this->model->getRanking();
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function busca(): void
    {
        $termo = trim($_GET['q'] ?? '');

        if ($termo === '') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Parâmetro "q" é obrigatório.'
            ], 400);
        }

        $data = $this->model->buscar($termo);
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }
}