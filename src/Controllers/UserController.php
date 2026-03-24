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
        RoleMiddleware::handle(['admin']);

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

    /*
     * Converte o valor interno da role para o label exibido na interface.
     */
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