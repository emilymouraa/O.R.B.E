<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class ChatModel extends Model
{
    protected string $table = 'chat_conversas';

    public function getConversasDoUsuario(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cc.id,
                cc.tipo,
                cc.nome,
                -- Para conversa direta, pega o nome do outro participante
                CASE WHEN cc.tipo = 'direta' THEN
                    (SELECT u2.nome FROM chat_participantes cp2
                    INNER JOIN users u2 ON u2.id = cp2.user_id
                    WHERE cp2.conversa_id = cc.id AND cp2.user_id != :uid
                    LIMIT 1)
                ELSE cc.nome END AS nome_exibido,
                -- Foto do outro participante
                (SELECT s2.foto_url FROM chat_participantes cp2
                INNER JOIN users u2 ON u2.id = cp2.user_id
                INNER JOIN servidores s2 ON s2.id = u2.servidor_id
                WHERE cp2.conversa_id = cc.id AND cp2.user_id != :uid7
                LIMIT 1) AS foto_outro,
                -- Role do outro participante (para saber se é admin)
                (SELECT u2.role FROM chat_participantes cp2
                INNER JOIN users u2 ON u2.id = cp2.user_id
                WHERE cp2.conversa_id = cc.id AND cp2.user_id != :uid2
                LIMIT 1) AS role_outro,
                -- Unidade do outro participante
                (SELECT u2.unidade_gestor_id FROM chat_participantes cp2
                INNER JOIN users u2 ON u2.id = cp2.user_id
                WHERE cp2.conversa_id = cc.id AND cp2.user_id != :uid6
                LIMIT 1) AS unidade_outro,
                -- Última mensagem
                (SELECT cm.mensagem FROM chat_mensagens cm
                 WHERE cm.conversa_id = cc.id
                 ORDER BY cm.created_at DESC LIMIT 1) AS ultima_mensagem,
                (SELECT cm.created_at FROM chat_mensagens cm
                 WHERE cm.conversa_id = cc.id
                 ORDER BY cm.created_at DESC LIMIT 1) AS ultima_at,
                -- Não lidas
                (SELECT COUNT(*) FROM chat_mensagens cm
                 WHERE cm.conversa_id = cc.id
                   AND cm.user_id != :uid3
                   AND NOT (cm.lida_por @> jsonb_build_array(:uid4::int)))
                AS nao_lidas
            FROM chat_conversas cc
            INNER JOIN chat_participantes cp ON cp.conversa_id = cc.id
            WHERE cp.user_id = :uid5
            ORDER BY ultima_at DESC NULLS LAST
        ");
        $stmt->execute([
            'uid'  => $userId, 'uid2' => $userId,
            'uid3' => $userId, 'uid4' => $userId,
            'uid5' => $userId, 'uid6' => $userId,
            'uid7' => $userId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOuCriarConversa(int $userId1, int $userId2): int
    {
        $stmt = $this->db->prepare("
            SELECT cc.id FROM chat_conversas cc
            INNER JOIN chat_participantes cp1 ON cp1.conversa_id = cc.id AND cp1.user_id = :uid1
            INNER JOIN chat_participantes cp2 ON cp2.conversa_id = cc.id AND cp2.user_id = :uid2
            WHERE cc.tipo = 'direta'
            LIMIT 1
        ");
        $stmt->execute(['uid1' => $userId1, 'uid2' => $userId2]);
        $existing = $stmt->fetchColumn();
        if ($existing) return (int) $existing;

        $this->db->prepare("
            INSERT INTO chat_conversas (tipo, created_by) VALUES ('direta', :uid)
        ")->execute(['uid' => $userId1]);
        $conversaId = (int) $this->db->lastInsertId();

        $ins = $this->db->prepare("
            INSERT INTO chat_participantes (conversa_id, user_id, pode_enviar)
            VALUES (:cid, :uid, true)
        ");
        $ins->execute(['cid' => $conversaId, 'uid' => $userId1]);
        $ins->execute(['cid' => $conversaId, 'uid' => $userId2]);

        return $conversaId;
    }

    public function getMensagens(int $conversaId, int $userId, int $limit = 50): array
    {
        $check = $this->db->prepare("
            SELECT 1 FROM chat_participantes
            WHERE conversa_id = :cid AND user_id = :uid
        ");
        $check->execute(['cid' => $conversaId, 'uid' => $userId]);
        if (!$check->fetchColumn()) return [];

        $stmt = $this->db->prepare("
            SELECT
                cm.id, cm.user_id, cm.mensagem, cm.created_at,
                u.nome AS autor_nome, u.role AS autor_role,
                (cm.lida_por @> jsonb_build_array(:uid::int)) AS lida
            FROM chat_mensagens cm
            INNER JOIN users u ON u.id = cm.user_id
            WHERE cm.conversa_id = :cid
            ORDER BY cm.created_at DESC
            LIMIT :lim
        ");
        $stmt->execute(['uid' => $userId, 'cid' => $conversaId, 'lim' => $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($rows);
    }

    public function enviarMensagem(int $conversaId, int $userId, string $mensagem): ?array
    {
        $check = $this->db->prepare("
            SELECT pode_enviar FROM chat_participantes
            WHERE conversa_id = :cid AND user_id = :uid
        ");
        $check->execute(['cid' => $conversaId, 'uid' => $userId]);
        $pode = $check->fetchColumn();
        if (!$pode) return null;

        $stmt = $this->db->prepare("
            INSERT INTO chat_mensagens (conversa_id, user_id, mensagem, lida_por)
            VALUES (:cid, :uid, :msg, :lida_por)
            RETURNING id, created_at
        ");
        $stmt->execute([
            'cid'     => $conversaId,
            'uid'     => $userId,
            'msg'     => $mensagem,
            'lida_por'=> json_encode([$userId]),
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function marcarLidas(int $conversaId, int $userId): void
    {
        $this->db->prepare("
            UPDATE chat_mensagens
            SET lida_por = lida_por || jsonb_build_array(:uid::int)
            WHERE conversa_id = :cid
              AND user_id != :uid2
              AND NOT (lida_por @> jsonb_build_array(:uid3::int))
        ")->execute([
            'uid' => $userId, 'cid' => $conversaId,
            'uid2' => $userId, 'uid3' => $userId,
        ]);
    }

    public function getContatosDisponiveis(int $userId, string $role): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.nome, u.role,
                u.unidade_gestor_id AS unidade_id,
                un.nome AS unidade, un.sigla AS unidade_sigla,
                s.foto_url
            FROM users u
            LEFT JOIN servidores s ON s.id = u.servidor_id
            LEFT JOIN unidades un ON un.id = u.unidade_gestor_id
            WHERE u.id != :uid
              AND u.ativo = true
              AND u.role IN ('admin', 'gestor')
            ORDER BY u.role ASC, u.nome ASC
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countNaoLidas(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM chat_mensagens cm
            INNER JOIN chat_participantes cp ON cp.conversa_id = cm.conversa_id
            WHERE cp.user_id = :uid
              AND cm.user_id != :uid2
              AND NOT (cm.lida_por @> jsonb_build_array(:uid3::int))
        ");
        $stmt->execute(['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId]);
        return (int) $stmt->fetchColumn();
    }
    public function getColaboradoresDaUnidade(int $unidadeId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id AS user_id, u.nome, s.id AS servidor_id, s.cargo
            FROM users u
            INNER JOIN servidores s ON s.id = u.servidor_id
            WHERE u.unidade_gestor_id = :uid AND u.ativo = true AND u.role = 'user'
            ORDER BY u.nome ASC
        ");
        $stmt->execute(['uid' => $unidadeId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function criarSolicitacao(
        int $servidorId,
        int $unidadeOrigemId,
        int $unidadeDestinoId,
        int $solicitanteUserId,
        string $motivo,
        ?int $conversaId = null
    ): int {
        $stmt = $this->db->prepare("
            SELECT u.id FROM users u
            WHERE u.unidade_gestor_id = :uid AND u.role = 'gestor' AND u.ativo = true
            LIMIT 1
        ");
        $stmt->execute(['uid' => $unidadeDestinoId]);
        $gestorDestinoId = $stmt->fetchColumn() ?: null;

        $stmt = $this->db->prepare("
            INSERT INTO solicitacoes_transferencia
                (servidor_id, unidade_origem_id, unidade_destino_id,
                solicitante_user_id, gestor_destino_user_id, motivo, status, conversa_id)
            VALUES
                (:servidor_id, :origem, :destino, :solicitante, :gestor_destino, :motivo, 'pendente'::status_solicitacao, :conversa_id)
            RETURNING id
        ");
        $stmt->execute([
            'servidor_id'    => $servidorId,
            'origem'         => $unidadeOrigemId,
            'destino'        => $unidadeDestinoId,
            'solicitante'    => $solicitanteUserId,
            'gestor_destino' => $gestorDestinoId,
            'motivo'         => $motivo,
            'conversa_id'    => $conversaId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function getSolicitacao(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                st.*,
                s.nome  AS servidor_nome,
                uo.nome AS unidade_origem_nome,
                ud.nome AS unidade_destino_nome
            FROM solicitacoes_transferencia st
            INNER JOIN servidores s  ON s.id  = st.servidor_id
            INNER JOIN unidades   uo ON uo.id = st.unidade_origem_id
            INNER JOIN unidades   ud ON ud.id = st.unidade_destino_id
            WHERE st.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getDb(): \PDO
    {
        return $this->db;
    }

    public function getSolicitacoesPendentes(int $userId, string $role): array
    {
        if ($role === 'admin') {
            $where = "st.status NOT IN ('executado', 'cancelado')
                    AND st.aprovado_admin_at IS NULL";
        } else {
            $where = "st.status NOT IN ('executado', 'cancelado')
                    AND (
                        st.solicitante_user_id = :uid_extra
                        OR (st.gestor_destino_user_id = :uid_extra2
                            AND st.aprovado_gestor_destino_at IS NULL)
                    )";
        }

        $sql = "
            SELECT
                st.id, st.status, st.motivo, st.created_at,
                st.solicitante_user_id, st.gestor_destino_user_id,
                st.aprovado_gestor_destino_at, st.aprovado_admin_at,
                s.nome   AS servidor_nome,
                uo.nome  AS unidade_origem_nome,
                ud.nome  AS unidade_destino_nome,
                us.nome  AS solicitante_nome
            FROM solicitacoes_transferencia st
            INNER JOIN servidores s  ON s.id  = st.servidor_id
            INNER JOIN unidades   uo ON uo.id = st.unidade_origem_id
            INNER JOIN unidades   ud ON ud.id = st.unidade_destino_id
            INNER JOIN users      us ON us.id = st.solicitante_user_id
            WHERE {$where}
            ORDER BY st.created_at DESC
        ";

        $params = $role === 'admin'
            ? []
            : ['uid_extra' => $userId, 'uid_extra2' => $userId];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function aprovarSolicitacao(int $id, int $userId, string $role): array
    {
        $sol = $this->getSolicitacao($id);
        if (!$sol) return ['ok' => false, 'error' => 'Solicitação não encontrada.'];

        if ($sol['status'] === 'rejeitado' || $sol['status'] === 'executado') {
            return ['ok' => false, 'error' => 'Solicitação não pode ser aprovada neste status.'];
        }

        if ($role === 'gestor') {
            if ((int) $sol['gestor_destino_user_id'] !== $userId) {
                return ['ok' => false, 'error' => 'Sem permissão.'];
            }
            $this->db->prepare("
                UPDATE solicitacoes_transferencia
                SET aprovado_gestor_destino_at = NOW(),
                    status = CASE
                        WHEN aprovado_admin_at IS NOT NULL THEN 'aprovado'::status_solicitacao
                        ELSE 'aprovado_gestor_destino'::status_solicitacao
                    END,
                    updated_at = NOW()
                WHERE id = :id
            ")->execute(['id' => $id]);
        } elseif ($role === 'admin') {
            $this->db->prepare("
                UPDATE solicitacoes_transferencia
                SET aprovado_admin_at      = NOW(),
                    aprovado_admin_user_id = :uid,
                    status = CASE
                        WHEN aprovado_gestor_destino_at IS NOT NULL THEN 'aprovado'::status_solicitacao
                        ELSE 'aprovado_admin'::status_solicitacao
                    END,
                    updated_at = NOW()
                WHERE id = :id
            ")->execute(['id' => $id, 'uid' => $userId]);
        }
        $stmt = $this->db->prepare("SELECT status FROM solicitacoes_transferencia WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $novoStatus = $stmt->fetchColumn();

        return ['ok' => true, 'status' => $novoStatus, 'solicitacao' => $sol];
    }

    public function rejeitarSolicitacao(int $id, int $userId, string $role, string $motivo): array
    {
        $sol = $this->getSolicitacao($id);
        if (!$sol) return ['ok' => false, 'error' => 'Solicitação não encontrada.'];

        $podeRejeitar = $role === 'admin'
            || (int) $sol['gestor_destino_user_id'] === $userId;

        if (!$podeRejeitar) return ['ok' => false, 'error' => 'Sem permissão.'];

        $this->db->prepare("
            UPDATE solicitacoes_transferencia
            SET status           = 'rejeitado'::status_solicitacao,
                rejeitado_por    = :uid,
                motivo_rejeicao  = :motivo,
                updated_at       = NOW()
            WHERE id = :id
        ")->execute(['id' => $id, 'uid' => $userId, 'motivo' => $motivo]);

        return ['ok' => true, 'status' => 'rejeitado', 'solicitacao' => $sol];
    }

    public function executarTransferencia(int $id, int $userId): array
    {
        $sol = $this->getSolicitacao($id);
        if (!$sol) return ['ok' => false, 'error' => 'Solicitação não encontrada.'];
        if ($sol['status'] !== 'aprovado') {
            return ['ok' => false, 'error' => 'Aguardando aprovações pendentes.'];
        }
        if ((int) $sol['solicitante_user_id'] !== $userId) {
            return ['ok' => false, 'error' => 'Apenas o gestor solicitante pode executar.'];
        }

        $this->db->prepare("
            UPDATE servidores SET unidade_id = :uid, updated_at = NOW()
            WHERE id = :sid
        ")->execute(['uid' => $sol['unidade_destino_id'], 'sid' => $sol['servidor_id']]);

        $this->db->prepare("
            UPDATE users SET unidade_gestor_id = :uid, updated_at = NOW()
            WHERE servidor_id = :sid
        ")->execute(['uid' => $sol['unidade_destino_id'], 'sid' => $sol['servidor_id']]);

        $this->db->prepare("
            INSERT INTO movimentacoes
                (servidor_id, unidade_origem_id, unidade_destino_id,
                tipo, descricao, motivo, data_inicio, executado_por, executado_at, status)
            VALUES
                (:sid, :origem, :destino,
                'transferencia', :descricao, :motivo, CURRENT_DATE, :exec, NOW(), 'executado')
        ")->execute([
            'sid'      => $sol['servidor_id'],
            'origem'   => $sol['unidade_origem_id'],
            'destino'  => $sol['unidade_destino_id'],
            'descricao'=> $sol['motivo'] ?: 'Transferência aprovada via solicitação.',
            'motivo'   => $sol['motivo'] ?: 'Solicitação aprovada por gestor e admin.',
            'exec'     => $userId,
        ]);

        $this->db->prepare("
            UPDATE solicitacoes_transferencia
            SET status = 'executado'::status_solicitacao, executado_at = NOW(), updated_at = NOW()
            WHERE id = :id
        ")->execute(['id' => $id]);

        return ['ok' => true, 'status' => 'executado', 'solicitacao' => $sol];
    }

    public function getUnidadeDoOutroParticipante(int $conversaId, int $userId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT u.unidade_gestor_id
            FROM chat_participantes cp
            INNER JOIN users u ON u.id = cp.user_id
            WHERE cp.conversa_id = :cid AND cp.user_id != :uid
            LIMIT 1
        ");
        $stmt->execute(['cid' => $conversaId, 'uid' => $userId]);
        $val = $stmt->fetchColumn();
        return $val ? (int) $val : null;
    }

    public function enviarMensagemSistema(int $conversaId, string $mensagem): void
    {
        $stmt = $this->db->prepare("
            SELECT id FROM users WHERE role = 'admin' AND ativo = true LIMIT 1
        ");
        $stmt->execute();
        $adminId = (int) $stmt->fetchColumn();
        if (!$adminId || !$conversaId) return;

        $this->db->prepare("
            INSERT INTO chat_mensagens (conversa_id, user_id, mensagem, lida_por)
            VALUES (:cid, :uid, :msg, '[]'::jsonb)
        ")->execute([
            'cid' => $conversaId,
            'uid' => $adminId,
            'msg' => $mensagem,
        ]);
    }
}