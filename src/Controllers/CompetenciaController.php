<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CompetenciaModel;
use App\Middleware\AuthMiddleware;
use PDO;

class CompetenciaController extends Controller
{
    private CompetenciaModel $model;

    public function __construct(\PDO $conn)
    {
        $this->model = new CompetenciaModel($conn);
    }

    public function index(): void
    {
        AuthMiddleware::handle();

        $dados = $this->model->listar();

        $this->jsonResponse([
            'data' => $dados
        ]);
    }
}