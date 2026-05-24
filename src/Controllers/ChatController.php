<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\ChatModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

class ChatController extends Controller
{
    private ChatModel $model;

    public function __construct(\PDO $conn)
    {
        $this->model = new ChatModel($conn);
    }

    public function conversas(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);
        $userId = (int) $_SESSION['user']['id'];
        $this->jsonResponse([
            'data'      => $this->model->getConversasDoUsuario($userId),
            'nao_lidas' => $this->model->countNaoLidas($userId),
        ]);
    }

    public function contatos(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);
        $userId = (int) $_SESSION['user']['id'];
        $role   = $_SESSION['user']['role'];
        $this->jsonResponse([
            'data' => $this->model->getContatosDisponiveis($userId, $role),
        ]);
    }

    public function abrirConversa(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);
        $userId = (int) $_SESSION['user']['id'];
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $outroId = (int) ($data['user_id'] ?? 0);
        if (!$outroId) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'user_id obrigatório.']);
            return;
        }
        $conversaId     = $this->model->getOuCriarConversa($userId, $outroId);
        $unidadeDestino = $this->model->getUnidadeDoOutroParticipante($conversaId, $userId);
        $this->jsonResponse([
            'conversa_id'        => $conversaId,
            'unidade_destino_id' => $unidadeDestino,
        ]);
    }

    public function mensagens(int $conversaId): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);
        $userId = (int) $_SESSION['user']['id'];
        $this->model->marcarLidas($conversaId, $userId);
        $this->jsonResponse([
            'data' => $this->model->getMensagens($conversaId, $userId),
        ]);
    }

    public function enviar(int $conversaId): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);
        $userId  = (int) $_SESSION['user']['id'];
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $mensagem = trim($data['mensagem'] ?? '');
        if (!$mensagem) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'Mensagem não pode ser vazia.']);
            return;
        }
        $result = $this->model->enviarMensagem($conversaId, $userId, $mensagem);
        if (!$result) {
            http_response_code(403);
            $this->jsonResponse(['error' => 'Sem permissão para enviar nesta conversa.']);
            return;
        }
        $this->jsonResponse(['success' => true, 'id' => $result['id'], 'created_at' => $result['created_at']]);
    }

    public function count(): void
    {
        AuthMiddleware::handle();
        $userId = (int) $_SESSION['user']['id'];
        $role   = $_SESSION['user']['role'] ?? 'user';
        if (!in_array($role, ['admin', 'gestor'])) {
            $this->jsonResponse(['nao_lidas' => 0]);
            return;
        }
        $this->jsonResponse(['nao_lidas' => $this->model->countNaoLidas($userId)]);
    }

    public function colaboradores(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['gestor']);
        $unidadeId = (int) ($_SESSION['user']['unidade_id'] ?? 0);
        if (!$unidadeId) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'Unidade não identificada.']);
            return;
        }
        $this->jsonResponse(['data' => $this->model->getColaboradoresDaUnidade($unidadeId)]);
    }

    public function solicitarTransferencia(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['gestor']);
        $userId    = (int) $_SESSION['user']['id'];
        $unidadeId = (int) ($_SESSION['user']['unidade_id'] ?? 0);
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];

        $servidorId       = (int) ($data['servidor_id']        ?? 0);
        $unidadeDestinoId = (int) ($data['unidade_destino_id'] ?? 0);
        $conversaId       = (int) ($data['conversa_id']        ?? 0);
        $motivo           = trim($data['motivo'] ?? '');

        if (!$servidorId || !$unidadeDestinoId || !$conversaId) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'Campos obrigatórios ausentes.']);
            return;
        }
        if ($unidadeDestinoId === $unidadeId) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'Unidade de destino deve ser diferente da origem.']);
            return;
        }

        $solicitacaoId = $this->model->criarSolicitacao(
            $servidorId, $unidadeId, $unidadeDestinoId, $userId, $motivo, $conversaId
        );

        $sol = $this->model->getSolicitacao($solicitacaoId);

        $msgTexto = "*Solicitação de Transferência*\n"
            . "Servidor: {$sol['servidor_nome']}\n"
            . "De: {$sol['unidade_origem_nome']}\n"
            . "Para: {$sol['unidade_destino_nome']}\n"
            . ($motivo ? "Motivo: {$motivo}\n" : '')
            . "Status: Aguardando aprovação\n"
            . "[solicitacao:{$solicitacaoId}]";

        $this->model->enviarMensagem($conversaId, $userId, $msgTexto);
        $notifModel = new \App\Models\NotificacaoModel($this->model->getDb());

        $stmtAdmin = $this->model->getDb()->prepare("
            SELECT id FROM users WHERE role = 'admin' AND ativo = true LIMIT 1
        ");
        $stmtAdmin->execute();
        $adminId = $stmtAdmin->fetchColumn();

        $titulo  = "Solicitação de transferência: {$sol['servidor_nome']}";
        $mensagem = "De {$sol['unidade_origem_nome']} para {$sol['unidade_destino_nome']}.";

        if ($sol['gestor_destino_user_id']) {
            $notifModel->criar(
                (int) $sol['gestor_destino_user_id'],
                'solicitacao_transferencia', $titulo, $mensagem,
                $solicitacaoId, 'solicitacoes_transferencia'
            );
        }
        if ($adminId) {
            $notifModel->criar(
                (int) $adminId,
                'solicitacao_transferencia', $titulo, $mensagem,
                $solicitacaoId, 'solicitacoes_transferencia'
            );
        }

        $this->jsonResponse(['success' => true, 'solicitacao_id' => $solicitacaoId]);
    }

    public function getDb(): \PDO
    {
        return $this->model->getDb();
    }

    public function solicitacoes(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);
        $userId = (int) $_SESSION['user']['id'];
        $role   = $_SESSION['user']['role'];
        $this->jsonResponse([
            'data' => $this->model->getSolicitacoesPendentes($userId, $role),
        ]);
    }

    public function aprovar(int $id): void
    {
        error_log("APROVAR chamado, id=$id, userId=" . ($_SESSION['user']['id'] ?? 'N/A') . ", role=" . ($_SESSION['user']['role'] ?? 'N/A'));
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);
        $userId = (int) $_SESSION['user']['id'];
        $role   = $_SESSION['user']['role'];

        $result = $this->model->aprovarSolicitacao($id, $userId, $role);
        if (!$result['ok']) {
            http_response_code(422);
            $this->jsonResponse(['error' => $result['error']]);
            return;
        }

        $sol = $result['solicitacao'];
        if ($sol['conversa_id']) {
            $quem = $role === 'admin' ? '👤 Administrador' : '👤 Gestor de destino';
            $this->model->enviarMensagemSistema(
                (int) $sol['conversa_id'],
                "🔔 *Atualização da solicitação*\n{$quem} aprovou a transferência de {$sol['servidor_nome']}.\nStatus: " . ($result['status'] === 'aprovado' ? '✅ Aprovado por ambos — aguardando execução.' : '⏳ Aguardando aprovação restante.')
            );
        }

        if ($result['status'] === 'aprovado') {
            $notif = new \App\Models\NotificacaoModel($this->model->getDb());
            $notif->criar(
                (int) $sol['solicitante_user_id'],
                'aprovacao_transferencia',
                'Transferência aprovada!',
                "A transferência de {$sol['servidor_nome']} foi aprovada. Você já pode executá-la.",
                $id,
                'solicitacoes_transferencia'
            );
        }
        $this->jsonResponse(['success' => true, 'status' => $result['status']]);
    }

    public function rejeitar(int $id): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);
        $userId = (int) $_SESSION['user']['id'];
        $role   = $_SESSION['user']['role'];
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $motivo = trim($data['motivo'] ?? '');

        $result = $this->model->rejeitarSolicitacao($id, $userId, $role, $motivo);
        if (!$result['ok']) {
            http_response_code(422);
            $this->jsonResponse(['error' => $result['error']]);
            return;
        }

        $sol   = $result['solicitacao'];

        if ($sol['conversa_id']) {
            $quem = $role === 'admin' ? '👤 Administrador' : '👤 Gestor de destino';
            $this->model->enviarMensagemSistema(
                (int) $sol['conversa_id'],
                "*Solicitação rejeitada*\n{$quem} rejeitou a transferência de {$sol['servidor_nome']}." . ($motivo ? "\nMotivo: {$motivo}" : '')
            );
        }

        $notif = new \App\Models\NotificacaoModel($this->model->getDb());
        $notif->criar(
            (int) $sol['solicitante_user_id'],
            'solicitacao_transferencia',
            'Solicitação de transferência rejeitada',
            "A transferência de {$sol['servidor_nome']} foi rejeitada." . ($motivo ? " Motivo: {$motivo}" : ''),
            $id,
            'solicitacoes_transferencia'
        );
        $this->jsonResponse(['success' => true, 'status' => 'rejeitado']);
    }

    public function executar(int $id): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['gestor']);
        $userId = (int) $_SESSION['user']['id'];

        $result = $this->model->executarTransferencia($id, $userId);
        if (!$result['ok']) {
            http_response_code(422);
            $this->jsonResponse(['error' => $result['error']]);
            return;
        }

        $sol = $result['solicitacao'];

        if ($sol['conversa_id']) {
            $this->model->enviarMensagemSistema(
                (int) $sol['conversa_id'],
                "*Transferência concluída*\n{$sol['servidor_nome']} foi transferido de {$sol['unidade_origem_nome']} para {$sol['unidade_destino_nome']}."
            );
        }

        $notif = new \App\Models\NotificacaoModel($this->model->getDb());
        $stmtU = $this->model->getDb()->prepare("
            SELECT u.id FROM users u WHERE u.servidor_id = :sid LIMIT 1
        ");
        $stmtU->execute(['sid' => $sol['servidor_id']]);
        $userIdServidor = $stmtU->fetchColumn();
        if ($userIdServidor) {
            $notif->criar(
                (int) $userIdServidor,
                'transferencia',
                'Você foi transferido de unidade',
                "Sua transferência para {$sol['unidade_destino_nome']} foi concluída.",
                $id,
                'solicitacoes_transferencia'
            );
        }

        $this->jsonResponse(['success' => true]);
    }
}