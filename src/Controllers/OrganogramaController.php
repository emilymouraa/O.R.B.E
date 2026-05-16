<?php
/*
 * Controller responsável pela API do organograma funcional.
 * Ele protege as rotas com autenticação, consulta o model e devolve
 * o organograma em formato JSON para consumo do frontend.
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrganogramaModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use PDO;

class OrganogramaController extends Controller
{
    private OrganogramaModel $model;

    public function __construct(PDO $conn)
    {
        $this->model = new OrganogramaModel($conn);
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);

        $organograma = $this->model->getOrganogramaBase();

        if (empty($organograma)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Nenhum administrador ativo encontrado para montar o organograma.'
            ], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Organograma carregado com sucesso.',
            'data' => $organograma
        ]);
    }

    public function usuariosPorGestor(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);

        $gestorId = filter_input(INPUT_GET, 'gestor_id', FILTER_VALIDATE_INT);

        if (!$gestorId || $gestorId <= 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Informe um gestor_id válido.'
            ], 422);
        }

        $resultado = $this->model->getUsuariosPorGestor($gestorId);

        if ($resultado === null) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Gestor não encontrado ou inativo.'
            ], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Usuários carregados com sucesso.',
            'data' => $resultado
        ]);
    }

     public function hierarquiaUsuario(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['user', 'gestor']);
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            $this->jsonResponse(['success' => false, 'message' => 'Sessão inválida.'], 401);
        }
        $dados = $this->model->getHierarquiaDoUsuario((int) $userId);
        if ($dados === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Dados não encontrados.'], 404);
        }
        $this->jsonResponse(['success' => true, 'data' => $dados]);
    }
}