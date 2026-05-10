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
    public function findByIdWithUnidade(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.nome,
                u.email,
                u.role,
                u.ativo,
                u.servidor_id,
                TO_CHAR(u.created_at, 'DD/MM/YYYY') AS data_cadastro,
                s.ra,
                s.cpf,
                s.cargo,
                s.patente,
                s.situacao,
                s.data_nascimento,
                TO_CHAR(s.data_ingresso,          'DD/MM/YYYY') AS data_ingresso,
                TO_CHAR(s.previsao_aposentadoria, 'DD/MM/YYYY') AS previsao_aposentadoria,
                s.foto_url,
                s.unidade_id,
                un.nome  AS unidade,
                un.sigla AS unidade_sigla,
                un.estado
            FROM users u
            LEFT JOIN servidores s  ON s.id  = u.servidor_id
            LEFT JOIN unidades   un ON un.id = s.unidade_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }
    public function updateFotoUrl(int $servidorId, string $url): bool
    {
        $stmt = $this->db->prepare("
            UPDATE servidores
            SET foto_url   = :foto_url,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'foto_url' => $url,
            'id'       => $servidorId,
        ]);
    }
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (servidor_id, nome, email, password, role, ativo, unidade_gestor_id)
            VALUES
            (:servidor_id, :nome, :email, :password, :role, TRUE, :unidade_gestor_id)
        ");
        return $stmt->execute([
            'servidor_id'       => $data['servidor_id'],
            'nome'              => $data['nome'],
            'email'             => $data['email'],
            'password'          => $data['password'],
            'role'              => $data['role'],
            'unidade_gestor_id' => $data['unidade_gestor_id'] ?? null,
        ]);
    }
    /*
     * Lista usuários ativos com paginação e filtros opcionais.
     * Faz JOIN com servidores e unidades para trazer o nome da unidade.
     * Exclui usuários sem vínculo (servidor_id NULL).
     */
    public function listPaginated(int $page, int $limit, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = "u.servidor_id IS NOT NULL";
        if (!empty($filters['search'])) {
            $where .= " AND (u.nome ILIKE :search OR u.email ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['role'])) {
            $where .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }
        if (!empty($filters['situacao'])) {
            $where .= " AND s.situacao = :situacao";
            $params['situacao'] = $filters['situacao'];
        }
        if (!empty($filters['unidade_id'])) {
            $where .= " AND u.unidade_gestor_id = :unidade_id";
            $params['unidade_id'] = (int) $filters['unidade_id'];
        }
        $sql = "
            SELECT
                u.id,
                u.servidor_id,
                u.nome,
                u.email,
                u.role,
                u.ativo,
                s.situacao,
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
        $where  = "u.servidor_id IS NOT NULL";
        if (!empty($filters['search'])) {
            $where .= " AND (u.nome ILIKE :search OR u.email ILIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['role'])) {
            $where .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }
        if (!empty($filters['situacao'])) {
            $where .= " AND s.situacao = :situacao";
            $params['situacao'] = $filters['situacao'];
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
    /* Verifica se já existe um gestor na unidade informada. O $excludeUserId evita falso positivo ao editar o próprio gestor. */
    public function hasGestorInUnidade(int $unidadeId, ?int $excludeUserId = null): bool
    {
        $sql = "
            SELECT COUNT(*) FROM {$this->table}
            WHERE role = 'gestor'
              AND unidade_gestor_id = :unidade_id
        ";
        $params = ['unidade_id' => $unidadeId];
        if ($excludeUserId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
    /* Atualiza dados do usuário em users. O campo ativo é derivado da situacao: só 'ativo' = true. */
    public function update(int $userId, array $data): bool
    {
        $ativo = ($data['situacao'] === 'ativo');
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET nome       = :nome,
                email      = :email,
                role       = :role,
                ativo      = :ativo,
                unidade_gestor_id = :unidade_gestor_id,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'nome'              => $data['nome'],
            'email'             => $data['email'],
            'role'              => $data['role'],
            'ativo'             => $ativo ? 'true' : 'false',
            'unidade_gestor_id' => $data['unidade_id'] ?? null,
            'id'                => $userId,
        ]);
    }
    /*Busca um usuário pelo ID retornando também o servidor_id vinculado.*/
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} WHERE id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}