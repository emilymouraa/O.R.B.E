<?php
/*
 * Controller responsável pelas ações relacionadas a usuários.
 * Aplica os middlewares de autenticação e autorização antes de processar
 * cada requisição. Retorna respostas JSON padronizadas.
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Models\ServidorModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Core\Env;

class UserController extends Controller
{
    private UserModel $model;
    private ServidorModel $servidorModel;
    private \App\Models\NotificacaoModel $notifModel;

    public function __construct(\PDO $conn) {
        $this->model         = new UserModel($conn);
        $this->servidorModel = new ServidorModel($conn);
        $this->notifModel    = new \App\Models\NotificacaoModel($conn);
    }

    public function index(): void
    {
        AuthMiddleware::handle();

        $role      = $_SESSION['user']['role']      ?? '';
        $sessionId = (int) ($_SESSION['user']['id'] ?? 0);

        $page  = max(1, (int) ($_GET['page']  ?? 1));
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

        $filters = [
            'search'     => trim($_GET['search']      ?? ''),
            'cargo'      => trim($_GET['cargo']        ?? ''),
            'situacao'   => trim($_GET['situacao']     ?? ''),
            'unidade_id' => (int) ($_GET['unidade_id'] ?? 0) ?: null,
        ];

        // ── Restrições por perfil ──────────────────────────────────────
        if ($role === 'gestor') {
            // Gestor só vê sua própria unidade, independente do filtro enviado
            $filters['unidade_id'] = (int) ($_SESSION['user']['unidade_id'] ?? 0);

        } elseif ($role === 'user') {
            // Usuário comum só vê sua própria unidade
            // (a view de colaboradores é separada, mas protegemos aqui também)
            $filters['unidade_id'] = (int) ($_SESSION['user']['unidade_id'] ?? 0);

        } elseif ($role !== 'admin') {
            // Role desconhecida: nega acesso
            http_response_code(403);
            $this->jsonResponse(['error' => 'Acesso negado.']);
            return;
        }
        // Admin: sem restrição, usa os filtros como vieram

        $users = $this->model->listPaginated($page, $limit, $filters);
        $total = $this->model->countFiltered($filters);

        $formatted = array_map(function (array $user) use ($role, $sessionId): array {
            return [
                'id'            => $user['id'],
                'servidor_id'   => $user['servidor_id'] ? (int) $user['servidor_id'] : null,
                'nome'          => $user['nome'],
                'email'         => $user['email'],
                'perfil'        => $this->formatRole($user['role']),
                'perfil_raw'    => $user['role'],
                'unidade'       => $user['unidade'],
                'unidade_id'    => $user['unidade_id'] ?? null, // ← necessário para o JS
                'situacao'      => $user['situacao'],
                'situacao_label'=> $this->formatSituacao($user['situacao']),
                'status_raw'    => $user['ativo'],
                'data_cadastro' => $user['data_cadastro'],
                // Flag que diz ao frontend se pode editar este usuário
                'pode_editar'   => $this->podeEditar($role, $user, $sessionId),
            ];
        }, $users);

        $this->jsonResponse([
            'data'       => $formatted,
            'total'      => $total,
            'mensagem'   => "Total de {$total} usuários",
            'page'       => $page,
            'limit'      => $limit,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    // Adicione este método privado na classe:
    private function podeEditar(string $role, array $user, int $sessionId): bool
    {
        return match ($role) {
            'admin'  => true,
            'gestor' => (int) ($user['unidade_id'] ?? 0) === (int) ($_SESSION['user']['unidade_id'] ?? 0),
            default  => false, // 'user' nunca edita
        };
    }

    public function perfil(): void
    {
        AuthMiddleware::handle();
 
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if (!$userId) {
            http_response_code(401);
            $this->jsonResponse(['error' => 'Não autenticado.']);
            return;
        }
 
        $user = $this->model->findByIdWithUnidade($userId);
        if (!$user) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Usuário não encontrado.']);
            return;
        }
 
        $this->jsonResponse([
            'data' => [
                'id'                    => $user['id'],
                'servidor_id'           => $user['servidor_id'],
                'nome'                  => $user['nome'],
                'email'                 => $user['email'],
                'perfil'                => $this->formatRole($user['role']),
                'perfil_raw'            => $user['role'],
                'unidade'               => $user['unidade']       ?? null,
                'unidade_sigla'         => $user['unidade_sigla'] ?? null,
                'estado'                => $user['estado']        ?? null,
                'status'                => $user['ativo'] ? 'Ativo' : 'Inativo',
                'status_raw'            => (bool) $user['ativo'],
                'data_cadastro'         => $user['data_cadastro'],
                'ra'                    => $user['ra']            ?? null,
                'cpf'                   => $user['cpf']           ?? null,
                'cargo'                 => $this->formatCargo($user['cargo'] ?? ''),
                'cargo_raw'             => $user['cargo']         ?? null,
                'patente'               => ucfirst($user['patente'] ?? ''),
                'situacao'              => ucfirst($user['situacao'] ?? ''),
                'data_nascimento'       => $user['data_nascimento']       ?? null,
                'data_ingresso'         => $user['data_ingresso']         ?? null,
                'previsao_aposentadoria'=> $user['previsao_aposentadoria'] ?? null,
                'foto_url'              => $user['foto_url']      ?? null,
            ],
        ]);
    }

    public function uploadFoto(): void
    {
        AuthMiddleware::handle();
 
        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            $this->jsonResponse(['error' => 'Nenhum arquivo enviado ou erro no upload.']);
            return;
        }
 
        $file     = $_FILES['foto'];
        $maxBytes = 2 * 1024 * 1024;
 
        if ($file['size'] > $maxBytes) {
            http_response_code(400);
            $this->jsonResponse(['error' => 'Arquivo muito grande. Máximo permitido: 2 MB.']);
            return;
        }
 
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
 
        if (!in_array($mimeType, $allowed, true)) {
            http_response_code(400);
            $this->jsonResponse(['error' => 'Formato inválido. Use JPG, PNG ou WEBP.']);
            return;
        }

        $ext        = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if (!$userId) {
            http_response_code(401);
            $this->jsonResponse(['error' => 'Não autenticado.']);
            return;
        }
        $user = $this->model->findByIdWithUnidade($userId);
        if (!$user || !$user['servidor_id']) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Servidor não encontrado.']);
            return;
        }
        $servidorId  = (int) $user['servidor_id'];
        $fileName    = "avatar_{$servidorId}.{$ext}";
        $fileContent = file_get_contents($file['tmp_name']);

        $supabaseUrl    = Env::get('SUPABASE_URL');
        $supabaseKey    = Env::get('SUPABASE_ANON_KEY');
        $bucket         = Env::get('SUPABASE_STORAGE_BUCKET', 'avatares');
        $uploadEndpoint = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fileName}";
 
        $ch = curl_init($uploadEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fileContent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$supabaseKey}",
                "Content-Type: {$mimeType}",
                "x-upsert: true",
            ],
        ]);
        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
 
        if ($httpCode !== 200 && $httpCode !== 201) {
            http_response_code(500);
            $this->jsonResponse(['error' => 'Falha ao enviar imagem ao storage.']);
            return;
        }
 
        $publicUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$fileName}";
        $userId    = (int) ($_SESSION['user']['id'] ?? 0);
        $user      = $this->model->findByIdWithUnidade($userId);
 
        if (!$user || !$user['servidor_id']) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Servidor não encontrado.']);
            return;
        }
 
        $this->model->updateFotoUrl((int) $user['servidor_id'], $publicUrl);
 
        $_SESSION['user']['foto_url'] = $publicUrl;
 
        $this->jsonResponse([
            'success'  => true,
            'foto_url' => $publicUrl,
        ]);
    }

    private function formatCargo(string $cargo): string
    {
        return match ($cargo) {
            '1a_classe'      => '1ª Classe',
            '2a_classe'      => '2ª Classe',
            '3a_classe'      => '3ª Classe',
            'classe_especial'=> 'Classe Especial',
            'chefe_divisao'  => 'Chefe de Divisão',
            'diretor_geral'  => 'Diretor-Geral',
            default          => $cargo,
        };
    }

    private function formatRole(string $role): string
    {
        return match ($role) {
            'admin'  => 'Administrador',
            'gestor' => 'Gestor',
            'user'   => 'Usuário',
            default  => $role,
        };
    }

    public function show(int $id): void
    {
        AuthMiddleware::handle();

        RoleMiddleware::handle(['admin', 'gestor']);

        $user = $this->model->findByIdWithUnidade($id);

        if (!$user) {

            http_response_code(404);

            $this->jsonResponse([
                'error' => 'Usuário não encontrado.'
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | GESTOR só pode visualizar usuários da própria unidade
        |--------------------------------------------------------------------------
        */

        $perfil = $_SESSION['user']['role'] ?? '';

        if ($perfil === 'gestor') {

            $gestorUnidade = (int) ($_SESSION['user']['unidade_id'] ?? 0);

            if ((int) $user['unidade_id'] !== $gestorUnidade) {

                http_response_code(403);

                $this->jsonResponse([
                    'error' => 'Você não pode visualizar este usuário.'
                ]);

                return;
            }
        }

        $this->jsonResponse([
            'data' => [

                'id'                     => $user['id'],
                'servidor_id'            => $user['servidor_id'],

                'nome'                   => $user['nome'],
                'email'                  => $user['email'],

                'perfil_raw'             => $user['role'],

                'cpf'                    => $user['cpf'] ?? null,

                'cargo_raw'              => $user['cargo'] ?? null,

                'ra'                     => $user['ra'] ?? null,

                'patente'                => $user['patente'] ?? null,

                'situacao'               => $user['situacao'] ?? 'ativo',

                'unidade_id'             => $user['unidade_id'] ?? null,

                'unidade'                => $user['unidade'] ?? null,

                'data_nascimento'        => $user['data_nascimento'] ?? null,

                'data_ingresso'          => $user['data_ingresso'] ?? null,

                'previsao_aposentadoria' => $user['previsao_aposentadoria'] ?? null,

                'foto_url'               => $user['foto_url'] ?? null,

                'data_cadastro'          => $user['data_cadastro'] ?? null,
            ],
        ]);
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $required = ['nome', 'email', 'cpf', 'cargo', 'role', 'unidade_id', 'situacao'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(422);
                $this->jsonResponse(['error' => "Campo obrigatório ausente: {$field}"]);
                return;
            }
        }

        if ($this->model->findByEmail($data['email'])) {
            http_response_code(409);
            $this->jsonResponse(['error' => 'E-mail já cadastrado.']);
            return;
        }

        if ($data['role'] === 'gestor' && $this->model->hasGestorInUnidade((int) $data['unidade_id'])) {
            http_response_code(409);
            $this->jsonResponse(['error' => 'Esta unidade já possui um gestor.']);
            return;
        }

        $ra          = $this->servidorModel->getNextRa();
        $servidorId  = $this->servidorModel->create([
            'ra'              => $ra,
            'nome'            => $data['nome'],
            'cpf'             => $data['cpf'],
            'cargo'           => $data['cargo'],
            'unidade_id'      => (int) $data['unidade_id'],
            'situacao'        => $data['situacao'],
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'data_ingresso'   => $data['data_admissao']   ?? null,
        ]);

        $senha = password_hash($data['cpf'], PASSWORD_BCRYPT);
        $this->model->create([
            'servidor_id' => $servidorId,
            'nome'        => $data['nome'],
            'email'       => $data['email'],
            'password'    => $senha,
            'role'        => $data['role'],
            'unidade_gestor_id' => (int) $data['unidade_id'], 
        ]);

        http_response_code(201);
        $this->jsonResponse(['success' => true, 'mensagem' => 'Usuário cadastrado com sucesso.']);
    }

    public function update(int $id): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin']);

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $user = $this->model->findByIdWithUnidade($id);
        if (!$user) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Usuário não encontrado.']);
            return;
        }

        if ($data['role'] === 'gestor' && $this->model->hasGestorInUnidade((int) $data['unidade_id'], $id)) {
            http_response_code(409);
            $this->jsonResponse(['error' => 'Esta unidade já possui um gestor.']);
            return;
        }

        $servidorId       = $user['servidor_id'] ? (int) $user['servidor_id'] : null;
        $adminId          = (int) ($_SESSION['user']['id'] ?? 0);
        $novaUnidadeId    = isset($data['unidade_id'])  ? (int) $data['unidade_id']  : null;
        $novaCargo        = $data['cargo']    ?? null;
        $novaPatente      = $data['patente']  ?? null;
        $descricaoCustom  = trim($data['motivo'] ?? $data['descricao'] ?? '');

        $houvTransferencia = $servidorId
            && $novaUnidadeId
            && $novaUnidadeId !== (int) ($user['unidade_id'] ?? 0);

        $houvPromocao = $servidorId
            && (
                ($novaCargo   && $novaCargo   !== ($user['cargo']   ?? ''))
             || ($novaPatente && $novaPatente !== ($user['patente'] ?? ''))
            );

        $this->model->update($id, $data);
        if ($user['servidor_id']) {
            $this->servidorModel->update((int) $user['servidor_id'], $data);
        }

        if ($servidorId && ($houvTransferencia || $houvPromocao)) {
            $stmtUid = $this->servidorModel->getDb()->prepare("
                SELECT id FROM users WHERE servidor_id = :sid LIMIT 1
            ");
            $stmtUid->execute(['sid' => $servidorId]);
            $userIdServidor = (int) $stmtUid->fetchColumn();

            if ($houvTransferencia) {
                $unidadeDestinoId = $novaUnidadeId ?? (int) ($user['unidade_id'] ?? 0);
                $stmtT = $this->servidorModel->getDb()->prepare("
                    INSERT INTO movimentacoes
                        (servidor_id, unidade_origem_id, unidade_destino_id,
                         tipo, descricao, motivo, data_inicio, executado_por, executado_at, status)
                    VALUES
                        (:servidor_id, :origem, :destino,
                         'transferencia', :descricao, :motivo, CURRENT_DATE, :exec_por, NOW(), 'executado')
                    RETURNING id
                ");
                $stmtT->execute([
                    'servidor_id' => $servidorId,
                    'origem'      => (int) ($user['unidade_id'] ?? $unidadeDestinoId),
                    'destino'     => $unidadeDestinoId,
                    'descricao'   => $descricaoCustom ?: null,
                    'motivo'      => $descricaoCustom ?: null,
                    'exec_por'    => $adminId,
                ]);
                $movIdT = (int) $stmtT->fetchColumn();

                $userIdGestorDestino = null;
                $stmtG = $this->servidorModel->getDb()->prepare("
                    SELECT u.id
                    FROM users u
                    WHERE u.unidade_gestor_id = :uid AND u.role = 'gestor' AND u.ativo = true
                    LIMIT 1
                ");
                $stmtG->execute(['uid' => $unidadeDestinoId]);
                $userIdGestorDestino = ($stmtG->fetchColumn()) ?: null;
                if ($userIdGestorDestino) {
                    $userIdGestorDestino = (int) $userIdGestorDestino;
                }

                if ($userIdServidor) {
                    $this->notifModel->gerarMovimentacao(
                        $movIdT,
                        $userIdServidor,
                        'transferencia',
                        $descricaoCustom,
                        $userIdGestorDestino
                    );
                }
            }

            if ($houvPromocao) {
                $unidadeAtualId = $novaUnidadeId ?? (int) ($user['unidade_id'] ?? 0);
                $cargoAnteriorLabel = $this->formatCargo($user['cargo']   ?? '');
                $cargoNovoLabel     = $this->formatCargo($novaCargo ?? $user['cargo'] ?? '');
                $patenteAnterior    = ucfirst($user['patente']  ?? '');
                $patenteNova        = ucfirst($novaPatente ?? $user['patente'] ?? '');

                $tituloPromocao = $descricaoCustom ?: trim(
                    ($cargoAnteriorLabel !== $cargoNovoLabel
                        ? "{$cargoAnteriorLabel} → {$cargoNovoLabel}"
                        : '') .
                    ($patenteAnterior !== $patenteNova
                        ? ($cargoAnteriorLabel !== $cargoNovoLabel ? ' · ' : '') . "{$patenteAnterior} → {$patenteNova}"
                        : '')
                ) ?: 'Promoção de cargo/patente';

                $stmtP = $this->servidorModel->getDb()->prepare("
                    INSERT INTO movimentacoes
                        (servidor_id, unidade_origem_id, unidade_destino_id,
                         tipo, descricao, motivo, data_inicio, executado_por, executado_at, status)
                    VALUES
                        (:servidor_id, :origem, :destino,
                         'promocao', :descricao, :motivo, CURRENT_DATE, :exec_por, NOW(), 'executado')
                    RETURNING id
                ");
                $stmtP->execute([
                    'servidor_id' => $servidorId,
                    'origem'      => $unidadeAtualId,
                    'destino'     => $unidadeAtualId,
                    'descricao'   => $tituloPromocao,
                    'motivo'      => $descricaoCustom ?: 'Promoção de cargo/patente.',
                    'exec_por'    => $adminId,
                ]);
                $movIdP = (int) $stmtP->fetchColumn();

                if ($userIdServidor) {
                    $this->notifModel->gerarMovimentacao(
                        $movIdP,
                        $userIdServidor,
                        'promocao',
                        $descricaoCustom,
                        null // Promoções não exigem ID de gestor de destino
                    );
                }
            }
        }

        $this->jsonResponse(['success' => true, 'mensagem' => 'Usuário atualizado com sucesso.']);
    }

    private function formatSituacao(string $situacao): string
    {
        return match ($situacao) {
            'ativo'               => 'Ativo',
            'afastado'            => 'Afastado',
            'aposentado'          => 'Aposentado',
            'licenca_maternidade' => 'Licença Maternidade',
            'desligado'           => 'Desligado',
            'outros'              => 'Outros',
            default               => ucfirst($situacao),
        };
    }

    public function updatePerfil(): void
    {
        AuthMiddleware::handle();

        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if (!$userId) {
            http_response_code(401);
            $this->jsonResponse(['error' => 'Não autenticado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $allowed = ['nome', 'email', 'data_nascimento'];
        $payload = array_intersect_key($data, array_flip($allowed));

        if (empty($payload['nome']) || empty($payload['email'])) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'Nome e e-mail são obrigatórios.']);
            return;
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'E-mail inválido.']);
            return;
        }

        $existing = $this->model->findByEmail($payload['email']);
        if ($existing && (int) $existing['id'] !== $userId) {
            http_response_code(409);
            $this->jsonResponse(['error' => 'Este e-mail já está em uso por outro usuário.']);
            return;
        }

        $user = $this->model->findById($userId);
        if (!$user) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Usuário não encontrado.']);
            return;
        }

        $this->model->updatePerfil($userId, $payload);

        if ($user['servidor_id'] && !empty($payload['data_nascimento'])) {
            $this->servidorModel->updateDataNascimento(
                (int) $user['servidor_id'],
                $payload['data_nascimento']
            );
        }

        $_SESSION['user']['nome'] = $payload['nome'];

        $this->jsonResponse(['success' => true, 'mensagem' => 'Perfil atualizado com sucesso.']);
    }

    public function colaboradores(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['gestor', 'admin', 'user']);
        $unidadeId = (int) ($_SESSION['user']['unidade_id'] ?? 0);
        if (!$unidadeId) {
            http_response_code(422);
            $this->jsonResponse(['error' => 'Unidade não identificada na sessão.']);
            return;
        }
        $search   = trim($_GET['search'] ?? '');
        $situacao = trim($_GET['situacao'] ?? '');
        $cargo    = trim($_GET['cargo'] ?? '');
        $data = $this->model->listColaboradoresDaUnidade(
            $unidadeId,
            $search,
            $situacao,
            $cargo
        );

        $total = $this->model->countColaboradoresDaUnidade(
            $unidadeId,
            $search,
            $situacao,
            $cargo
        );

        $formatted = array_map(function (array $row): array {
            return [
                'id'             => $row['id'],
                'perfil_raw'     => $row['role'] ?? 'user',

                'nome'           => $row['nome'],
                'email'          => $row['email'],
                'cargo'          => $this->formatCargo($row['cargo']),
                'unidade'        => $row['unidade'],
                'unidade_sigla'  => $row['unidade_sigla'],
                'situacao'       => $this->formatSituacao($row['situacao']),
            ];
        }, $data);

        $this->jsonResponse([
            'data'     => $formatted,
            'total'    => $total,
            'mensagem' => "Total de {$total} colaboradores na sua unidade",
        ]);
    }

    public function updateColaboradorGestor(int $id): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['gestor']);

        $gestorUnidadeId = (int) ($_SESSION['user']['unidade_id'] ?? 0);

        if (!$gestorUnidadeId) {
            http_response_code(403);
            $this->jsonResponse([
                'error' => 'Gestor sem unidade vinculada.'
            ]);
            return;
        }

        $colaborador = $this->model
            ->findColaboradorByIdAndUnidade($id, $gestorUnidadeId);

        if (!$colaborador) {
            http_response_code(403);
            $this->jsonResponse([
                'error' => 'Você não pode editar este colaborador.'
            ]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $situacao = strtolower(trim($data['situacao'] ?? ''));

        $payloadUser = [
            'nome'       => $data['nome']  ?? '',
            'email'      => $data['email'] ?? '',
            'role'       => $colaborador['role'],
            'situacao'   => $situacao,
            'unidade_id' => $gestorUnidadeId,
        ];
        $payloadServidor = [
            'nome'            => $data['nome']  ?? '',
            'cpf'             => preg_replace('/\D/', '', $colaborador['cpf'] ?? ''),
            'cargo'           => $colaborador['cargo'] ?? '',
            'situacao'        => $situacao,
            'unidade_id'      => $gestorUnidadeId,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'data_ingresso'   => $data['data_ingresso']   ?? null,
        ];

        $this->model->update($id, $payloadUser);

        $this->servidorModel->update(
            (int) $colaborador['servidor_id'],
            $payloadServidor
        );

        $this->jsonResponse([
            'success' => true,
            'mensagem' => 'Colaborador atualizado com sucesso.'
        ]);
    }
}