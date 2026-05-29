<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\ChamadoModel;
use App\Models\NotificacaoModel;
use App\Models\UserModel;
use App\Models\ServidorModel;
use App\Middleware\AuthMiddleware;

class ChamadoController extends Controller
{
    private ChamadoModel $model;
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->model = new ChamadoModel($conn);
        $this->conn  = $conn;
    }

    /** POST /api/chamados — abre chamado */
    public function store(): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($this->model->temPendente($userId)) {
            http_response_code(409);
            $this->jsonResponse(['error' => 'Você já possui um chamado pendente.']);
            return;
        }

        $motivo = trim($data['motivo'] ?? '');

        if (!$motivo) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'Descreva o que precisa ser alterado.']);
            return;
        }

        $chamadoId = $this->model->criar($userId, null, null, null, $motivo);

        // Notifica aprovador
        $notif  = new NotificacaoModel($this->conn);
        $role   = $_SESSION['user']['role'] ?? 'user';
        $nomeUser = $_SESSION['user']['nome'] ?? 'Servidor';

        if ($role === 'user') { 
            // Notifica gestor da unidade
            $unidadeId = (int) ($_SESSION['user']['unidade_id'] ?? 0);
            $stmt = $this->conn->prepare("
                SELECT id FROM users
                WHERE unidade_gestor_id = :uid AND role = 'gestor' AND ativo = true
                LIMIT 1
            ");
            $stmt->execute(['uid' => $unidadeId]);
            $gestorId = $stmt->fetchColumn();
            if ($gestorId) {
                $notif->criar(
                    (int) $gestorId,
                    'aviso_gestor',
                    'Novo chamado de edição de perfil',
                    "{$nomeUser} solicitou alteração nos seus dados pessoais.",
                    $chamadoId,
                    'chamados_edicao'
                );
            }
        } else {
            // Gestor notifica admin
            $stmt = $this->conn->prepare("
                SELECT id FROM users WHERE role = 'admin' AND ativo = true LIMIT 1
            ");
            $stmt->execute();
            $adminId = $stmt->fetchColumn();
            if ($adminId) {
                $notif->criar(
                    (int) $adminId,
                    'aviso_admin',
                    'Novo chamado de edição de perfil',
                    "Gestor {$nomeUser} solicitou alteração nos seus dados pessoais.",
                    $chamadoId,
                    'chamados_edicao'
                );
            }
        }

        $this->jsonResponse(['success' => true, 'chamado_id' => $chamadoId]);
    }

    /** GET /api/chamados/pendentes — lista para aprovador */
    public function pendentes(): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $role   = $_SESSION['user']['role'] ?? '';

        if (!in_array($role, ['admin', 'gestor'])) {
            http_response_code(403);
            $this->jsonResponse(['error' => 'Sem permissão.']);
            return;
        }

        $this->jsonResponse(['data' => $this->model->getPendentes($userId, $role)]);
    }

    /** GET /api/chamados/meus — chamados do próprio usuário */
    public function meus(): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $this->jsonResponse(['data' => $this->model->getMeusChamados($userId)]);
    }

    /** GET /api/chamados/{id} — dados do chamado para preencher modal */
    public function show(int $id): void
    {
        AuthMiddleware::handle();
        $chamado = $this->model->getById($id);
        if (!$chamado) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Chamado não encontrado.']);
            return;
        }
        $this->jsonResponse(['data' => $chamado]);
    }

    public function concluir(int $id): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $role   = $_SESSION['user']['role'] ?? '';

        if (!in_array($role, ['admin', 'gestor'])) {
            http_response_code(403);
            $this->jsonResponse(['error' => 'Sem permissão.']);
            return;
        }

        $chamado = $this->model->getById($id);
        if (!$chamado) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Chamado não encontrado.']);
            return;
        }

        if ($chamado['status'] !== 'pendente') {
            http_response_code(409);
            $this->jsonResponse(['error' => 'Chamado já foi processado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $userModel     = new UserModel($this->conn);
        $servidorModel = new ServidorModel($this->conn);

        $userModel->update((int) $chamado['user_id'], $data);
        if ($chamado['servidor_id']) {
            $servidorModel->update((int) $chamado['servidor_id'], $data);
        }

        $this->model->concluir($id, $userId, '');

        $notif = new NotificacaoModel($this->conn);
        $notif->criar(
            (int) $chamado['solicitante_user_id'],
            'aviso_gestor',
            'Chamado de edição concluído',
            'Seus dados pessoais foram atualizados conforme solicitado.',
            $id,
            'chamados_edicao'
        );

        $this->jsonResponse(['success' => true]);
    }

    public function rejeitar(int $id): void
    {
        AuthMiddleware::handle();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $role   = $_SESSION['user']['role'] ?? '';

        if (!in_array($role, ['admin', 'gestor'])) {
            http_response_code(403);
            $this->jsonResponse(['error' => 'Sem permissão.']);
            return;
        }

        $chamado = $this->model->getById($id);
        if (!$chamado) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Chamado não encontrado.']);
            return;
        }

        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $motivo = trim($data['motivo'] ?? '');

        $this->model->rejeitar($id, $userId, $motivo);

        $notif = new NotificacaoModel($this->conn);
        $notif->criar(
            (int) $chamado['solicitante_user_id'],
            'aviso_gestor',
            'Chamado de edição rejeitado',
            'Sua solicitação de alteração de dados foi rejeitada.' . ($motivo ? " Motivo: {$motivo}" : ''),
            $id,
            'chamados_edicao'
        );

        $this->jsonResponse(['success' => true]);
    }
}