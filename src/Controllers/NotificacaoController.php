<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\NotificacaoModel;
use App\Middleware\AuthMiddleware;

class NotificacaoController extends Controller
{
    private NotificacaoModel $model;

    public function __construct(\PDO $conn)
    {
        $this->model = new NotificacaoModel($conn);
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if (!$userId) { $this->jsonResponse(['data' => [], 'nao_lidas' => 0]); return; }

        $this->model->gerarCompetenciasVencidas();
        $notifs   = $this->model->getByUserId($userId);
        $naoLidas = $this->model->countNaoLidas($userId);
        $this->jsonResponse(['data' => $notifs, 'nao_lidas' => $naoLidas]);
    }

    public function count(): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $this->jsonResponse(['nao_lidas' => $userId ? $this->model->countNaoLidas($userId) : 0]);
    }

    public function marcarLida(int $id): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $ok = $this->model->marcarLida($id, $userId);
        $this->jsonResponse(['success' => $ok]);
    }

    public function marcarTodasLidas(): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $ok = $this->model->marcarTodasLidas($userId);
        $this->jsonResponse(['success' => $ok]);
    }

    public function gerarCompetencias(): void
    {
        AuthMiddleware::handle();
        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'admin') {
            http_response_code(403);
            $this->jsonResponse(['error' => 'Apenas administradores podem executar esta ação.']);
            return;
        }
        $total = $this->model->gerarCompetenciasVencidas();
        $this->jsonResponse([
            'success'   => true,
            'inseridas' => $total,
            'mensagem'  => "{$total} notificação(ões) de competência vencida gerada(s).",
        ]);
    }

    public function enviarAviso(): void
    {
        AuthMiddleware::handle();
        $role      = $_SESSION['user']['role'] ?? '';
        $userId    = (int) ($_SESSION['user']['id'] ?? 0);
        $unidadeId = (int) ($_SESSION['user']['unidade_id'] ?? 0);

        if (!in_array($role, ['admin', 'gestor'])) {
            http_response_code(403);
            $this->jsonResponse(['error' => 'Sem permissão.']);
            return;
        }

        $data     = json_decode(file_get_contents('php://input'), true) ?? [];
        $titulo   = trim($data['titulo']   ?? '');
        $mensagem = trim($data['mensagem'] ?? '');

        if (!$titulo || !$mensagem) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'Título e mensagem são obrigatórios.']);
            return;
        }

        if ($role === 'gestor') {
            if (!$unidadeId) {
                http_response_code(422);
                $this->jsonResponse(['error' => 'Unidade não identificada.']);
                return;
            }
            $total = $this->model->enviarAvisoUnidade($unidadeId, $titulo, $mensagem);
        } else {
            $total = $this->model->enviarAvisoGestores($titulo, $mensagem);
        }

        $this->jsonResponse([
            'success' => true,
            'total'   => $total,
            'mensagem'=> "{$total} notificação(ões) enviada(s).",
        ]);
    }
}