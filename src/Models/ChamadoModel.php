<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class ChamadoModel extends Model
{
    protected string $table = 'chamados_edicao';

    /** Abre um novo chamado */
    public function criar(
        int $solicitanteId,
        ?string $nome,
        ?string $email,
        ?string $dataNascimento,
        ?string $motivo
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO chamados_edicao
                (solicitante_user_id, nome_solicitado, email_solicitado,
                 data_nascimento_solicitada, motivo, status)
            VALUES
                (:solicitante, :nome, :email, :nascimento, :motivo, 'pendente')
            RETURNING id
        ");
        $stmt->execute([
            'solicitante' => $solicitanteId,
            'nome'        => $nome        ?: null,
            'email'       => $email       ?: null,
            'nascimento'  => $dataNascimento ?: null,
            'motivo'      => $motivo      ?: null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    /** Lista chamados pendentes para o aprovador */
    public function getPendentes(int $aprovadorId, string $role): array
    {
        if ($role === 'admin') {
            // Admin vê chamados de gestores
            $where = "u.role = 'gestor' AND c.status = 'pendente'";
            $params = [];
        } else {
            // Gestor vê chamados dos colaboradores da sua unidade
            $where = "u.role = 'user' AND c.status = 'pendente'
                      AND u.unidade_gestor_id = :unidade_id";
            $stmt = $this->db->prepare("
                SELECT unidade_gestor_id FROM users WHERE id = :id LIMIT 1
            ");
            $stmt->execute(['id' => $aprovadorId]);
            $unidadeId = $stmt->fetchColumn();
            $params = ['unidade_id' => $unidadeId];
        }

        $stmt = $this->db->prepare("
            SELECT
                c.id, c.status, c.motivo, c.created_at,
                c.nome_solicitado, c.email_solicitado,
                c.data_nascimento_solicitada,
                u.id AS user_id, u.nome AS user_nome, u.email AS user_email,
                u.role AS user_role,
                s.cargo, s.foto_url
            FROM chamados_edicao c
            INNER JOIN users u ON u.id = c.solicitante_user_id
            INNER JOIN servidores s ON s.id = u.servidor_id
            WHERE {$where}
            ORDER BY c.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Busca chamado por ID */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                c.*,
                u.id AS user_id, u.nome AS user_nome, u.email AS user_email,
                u.role AS user_role, u.servidor_id,
                s.cargo, s.cpf, s.foto_url,
                s.data_ingresso, s.previsao_aposentadoria,
                un.nome AS unidade_nome, un.id AS unidade_id,
                u.unidade_gestor_id
            FROM chamados_edicao c
            INNER JOIN users u ON u.id = c.solicitante_user_id
            INNER JOIN servidores s ON s.id = u.servidor_id
            LEFT JOIN unidades un ON un.id = u.unidade_gestor_id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Conclui chamado — salva observação e marca como concluído */
    public function concluir(int $id, int $aprovadorId, string $obs = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE chamados_edicao
            SET status               = 'concluido',
                aprovador_user_id    = :aprovador,
                observacao_aprovador = :obs,
                concluido_at         = NOW(),
                updated_at           = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $id, 'aprovador' => $aprovadorId, 'obs' => $obs]);
    }

    /** Rejeita chamado */
    public function rejeitar(int $id, int $aprovadorId, string $motivo = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE chamados_edicao
            SET status               = 'rejeitado',
                aprovador_user_id    = :aprovador,
                observacao_aprovador = :motivo,
                updated_at           = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $id, 'aprovador' => $aprovadorId, 'motivo' => $motivo]);
    }

    /** Chamados abertos pelo próprio usuário */
    public function getMeusChamados(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, status, motivo, created_at, concluido_at,
                   nome_solicitado, email_solicitado, data_nascimento_solicitada,
                   observacao_aprovador
            FROM chamados_edicao
            WHERE solicitante_user_id = :uid
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Verifica se já tem chamado pendente */
    public function temPendente(int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM chamados_edicao
            WHERE solicitante_user_id = :uid AND status = 'pendente'
            LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        return (bool) $stmt->fetchColumn();
    }
}