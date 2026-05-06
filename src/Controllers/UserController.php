<?php
/*
 * Controller responsável pelas ações relacionadas a usuários.
 * Aplica os middlewares de autenticação e autorização antes de processar
 * cada requisição. Retorna respostas JSON padronizadas.
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Core\Env;

class UserController extends Controller
{
    private UserModel $model;

    public function __construct(\PDO $conn)
    {
        $this->model = new UserModel($conn);
    }

    /*
     * Lista usuários com paginação, filtros e contagem total.
     * Rota: GET /api/users
     *
     * Query params aceitos:
     *   page       int     (default: 1)
     *   limit      int     (default: 10, máx: 50)
     *   search     string  busca por nome ou e-mail
     *   role       string  admin | gestor | user
     *   ativo      bool    true | false
     *   unidade_id int
     */
    public function index(): void
    {
        // Protege a rota: deve estar logado e ser admin
        AuthMiddleware::handle();

        $page  = max(1, (int) ($_GET['page']  ?? 1));
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

        $filters = [
            'search'     => trim($_GET['search']      ?? ''),
            'role'       => trim($_GET['role']         ?? ''),
            'ativo'      => $_GET['ativo']             ?? '',
            'unidade_id' => (int) ($_GET['unidade_id'] ?? 0) ?: null,
        ];

        $users = $this->model->listPaginated($page, $limit, $filters);
        $total = $this->model->countFiltered($filters);

        // Formata role e ativo para os badges e pills da interface
        $formatted = array_map(function (array $user): array {
            return [
                'id'            => $user['id'],
                'nome'          => $user['nome'],
                'email'         => $user['email'],
                'perfil'        => $this->formatRole($user['role']),
                'perfil_raw'    => $user['role'],
                'unidade'       => $user['unidade'],
                'status'        => $user['ativo'] ? 'Ativo' : 'Inativo',
                'status_raw'    => $user['ativo'],
                'data_cadastro' => $user['data_cadastro'],
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
}