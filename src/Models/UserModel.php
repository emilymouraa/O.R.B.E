<?php
/*
 * Model responsável pelo acesso à tabela de usuários (users).
 * Contém métodos específicos para buscar usuários por email,
 * por servidor vinculado, para criar novos registros,
 * e para listar com paginação/filtros (gestão de usuários).
 */
namespace App\Models;

use App\Core\Model;
use PDO;

class UserModel extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findByServidorId(int $servidorId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE servidor_id = :servidor_id
            LIMIT 1
        ");
        $stmt->execute(['servidor_id' => $servidorId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (servidor_id, nome, email, password, role, ativo)
            VALUES
            (:servidor_id, :nome, :email, :password, :role, TRUE)
        ");
        return $stmt->execute([
            'servidor_id' => $data['servidor_id'],
            'nome'        => $data['nome'],
            'email'       => $data['email'],
            'password'    => $data['password'],
            'role'        => $data['role'],
        ]);
    }

    /*
     * Lista usuários ativos com paginação e filtros opcionais.
     * Faz JOIN com servidores e unidades para trazer o nome da unidade.
     * Exclui usuários sem vínculo (servidor_id NULL) e inativos (ativo = false).
     */
    public function listPaginated(int $page, int $limit, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where  = "u.ativo = true AND u.servidor_id IS NOT NULL";

        if (!empty($filters['search'])) {
            $where .= " AND (u.nome ILIKE :search OR u.email ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['role'])) {
            $where .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $where .= " AND u.ativo = :ativo";
            $params['ativo'] = filter_var($filters['ativo'], FILTER_VALIDATE_BOOLEAN);
        }

        if (!empty($filters['unidade_id'])) {
            $where .= " AND s.unidade_id = :unidade_id";
            $params['unidade_id'] = (int) $filters['unidade_id'];
        }

        $sql = "
            SELECT
                u.id,
                u.nome,
                u.email,
                u.role,
                u.ativo,
                TO_CHAR(u.created_at, 'DD/MM/YYYY') AS data_cadastro,
                un.nome AS unidade
            FROM users u
            INNER JOIN servidores s ON s.id = u.servidor_id
            INNER JOIN unidades un  ON un.id = s.unidade_id
            WHERE {$where}
            ORDER BY u.nome ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Conta o total de usuários respeitando os mesmos filtros da listagem.
     * Usado para calcular o total de páginas e exibir "Total de X usuários".
     */
    public function countFiltered(array $filters = []): int
    {
        $params = [];
        $where  = "u.ativo = true AND u.servidor_id IS NOT NULL";

        if (!empty($filters['search'])) {
            $where .= " AND (u.nome ILIKE :search OR u.email ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['role'])) {
            $where .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $where .= " AND u.ativo = :ativo";
            $params['ativo'] = filter_var($filters['ativo'], FILTER_VALIDATE_BOOLEAN);
        }

        if (!empty($filters['unidade_id'])) {
            $where .= " AND s.unidade_id = :unidade_id";
            $params['unidade_id'] = (int) $filters['unidade_id'];
        }

        $sql = "
            SELECT COUNT(*)
            FROM users u
            INNER JOIN servidores s ON s.id = u.servidor_id
            INNER JOIN unidades un  ON un.id = s.unidade_id
            WHERE {$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function updatePassword(int $userId, string $hash): bool
{
    $stmt = $this->db->prepare("
        UPDATE {$this->table}
        SET password = :password, updated_at = NOW()
        WHERE id = :id
    ");

    return $stmt->execute([
        'password' => $hash,
        'id' => $userId
    ]);
}
}