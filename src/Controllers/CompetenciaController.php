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

    // GET /api/competencias — lista só as do usuário logado
    public function index(): void
    {
        AuthMiddleware::handle();
        $user       = $_SESSION['user'];
        $role       = $user['role'] ?? 'user';
        $servidorId = (int) ($user['servidor_id'] ?? 0);
        $unidadeId  = (int) ($user['unidade_id']  ?? 0);

        $filtroServidorId = isset($_GET['servidor_id']) ? (int) $_GET['servidor_id'] : null;

        if ($role === 'user') {
            if (!$servidorId) {
                $this->jsonResponse(['error' => 'Servidor não vinculado.'], 400);
                return;
            }
            $dados = $this->model->listarPorServidor($servidorId);
        } else {
            if ($filtroServidorId) {
                $isAdmin = $role === 'admin';
                $dados = $this->model->listarPorServidorPublico($filtroServidorId, $isAdmin);
            } elseif ($role === 'admin') {
                $dados = $this->model->listarTodas();
            } else {
                if (!$unidadeId) {
                    $this->jsonResponse(['error' => 'Unidade não vinculada.'], 400);
                    return;
                }
                $dados = $this->model->listarPorUnidade($unidadeId);
            }
        }

        $this->jsonResponse(['data' => $dados]);
    }

    // POST /api/competencias — cria nova competência para o usuário logado
    public function store(): void
    {
        AuthMiddleware::handle();
        $user       = $_SESSION['user'];
        $servidorId = (int) ($user['servidor_id'] ?? 0);
        if (!$servidorId) {
            $this->jsonResponse(['error' => 'Servidor não vinculado.'], 400);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $nome         = trim($body['nome']         ?? '');
        $tipo         = trim($body['tipo']         ?? '');
        $instituicao  = trim($body['instituicao']  ?? '');
        $descricao    = trim($body['descricao']    ?? '');
        $cargaHoraria = isset($body['carga_horaria']) ? (int) $body['carga_horaria'] : null;
        $dataConclusao = $body['data_conclusao']   ?? null;
        $validade      = $body['validade']         ?? null;
        $visibilidade  = in_array($body['visibilidade'] ?? '', ['publica', 'privada'])
                         ? $body['visibilidade'] : 'privada';

        if (!$nome || !$tipo) {
            $this->jsonResponse(['error' => 'Nome e tipo são obrigatórios.'], 422);
            return;
        }

        $tiposValidos = ['curso', 'certificacao', 'especializacao', 'habilidade'];
        if (!in_array($tipo, $tiposValidos, true)) {
            $this->jsonResponse(['error' => 'Tipo inválido.'], 422);
            return;
        }

        $id = $this->model->criar([
            'servidor_id'   => $servidorId,
            'nome'          => $nome,
            'tipo'          => $tipo,
            'instituicao'   => $instituicao ?: null,
            'descricao'     => $descricao   ?: null,
            'carga_horaria' => $cargaHoraria,
            'data_conclusao'=> $dataConclusao ?: null,
            'validade'      => $validade      ?: null,
            'visibilidade'  => $visibilidade,
        ]);

        if (!$id) {
            $this->jsonResponse(['error' => 'Erro ao salvar competência.'], 500);
            return;
        }

        $this->jsonResponse([
            'mensagem' => 'Competência cadastrada com sucesso!',
            'id'       => $id,
        ], 201);
    }

    // POST /api/competencias/{id}/anexo — upload de certificado
    public function uploadAnexo(int $competenciaId): void
    {
        AuthMiddleware::handle();
        $user       = $_SESSION['user'];
        $servidorId = (int) ($user['servidor_id'] ?? 0);

        // Verifica se a competência pertence ao usuário
        if (!$this->model->pertenceAoServidor($competenciaId, $servidorId)) {
            $this->jsonResponse(['error' => 'Acesso negado.'], 403);
        }

        $file = $_FILES['anexo'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'Arquivo inválido.'], 400);
        }

        $maxSize  = 5 * 1024 * 1024; // 5MB
        $allowed  = ['application/pdf', 'image/jpeg', 'image/png'];
        $mimeType = mime_content_type($file['tmp_name']);

        if ($file['size'] > $maxSize) {
            $this->jsonResponse(['error' => 'Arquivo muito grande. Máximo: 5MB.'], 400);
        }
        if (!in_array($mimeType, $allowed, true)) {
            $this->jsonResponse(['error' => 'Formato inválido. Use PDF, JPG ou PNG.'], 400);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'cert_' . $servidorId . '_' . $competenciaId . '_' . time() . '.' . $ext;
        $destDir  = __DIR__ . '/../../public/assets/uploads/certificados/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $destPath = $destDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->jsonResponse(['error' => 'Erro ao salvar arquivo.'], 500);
        }

        $url = '/assets/uploads/certificados/' . $filename;
        $this->model->salvarAnexo($competenciaId, $url, $file['name']);

        $this->jsonResponse([
            'mensagem'   => 'Anexo salvo com sucesso!',
            'anexo_url'  => $url,
            'anexo_nome' => $file['name'],
        ]);
    }
}