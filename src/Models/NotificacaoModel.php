<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class NotificacaoModel extends Model
{
    protected string $table = 'notificacoes';

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, tipo, titulo, mensagem, lida, referencia_id, referencia_tipo, created_at
            FROM notificacoes
            WHERE user_id = :uid AND (lida = false OR created_at >= NOW() - INTERVAL '30 days')
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countNaoLidas(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notificacoes
            WHERE user_id = :uid AND lida = false
        ");
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function marcarLida(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notificacoes
            SET lida = true, data_leitura = NOW()
            WHERE id = :id AND user_id = :uid
        ");
        return $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public function marcarTodasLidas(int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notificacoes
            SET lida = true, data_leitura = NOW()
            WHERE user_id = :uid AND lida = false
        ");
        return $stmt->execute(['uid' => $userId]);
    }

    public function criar(int $userId, string $tipo, string $titulo, string $mensagem, ?int $refId = null, ?string $refTipo = null): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO notificacoes (user_id, tipo, titulo, mensagem, referencia_id, referencia_tipo)
            VALUES (:uid, :tipo, :titulo, :mensagem, :ref_id, :ref_tipo)
        ");
        return $stmt->execute([
            'uid'      => $userId,
            'tipo'     => $tipo,
            'titulo'   => $titulo,
            'mensagem' => $mensagem,
            'ref_id'   => $refId,
            'ref_tipo' => $refTipo,
        ]);
    }

    public function jaExiste(int $userId, string $tipo, int $refId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM notificacoes
            WHERE user_id = :uid AND tipo = :tipo AND referencia_id = :ref_id
            LIMIT 1
        ");
        $stmt->execute(['uid' => $userId, 'tipo' => $tipo, 'ref_id' => $refId]);
        return (bool) $stmt->fetchColumn();
    }

    public function gerarCompetenciasVencidas(): int
    {
        $stmt = $this->db->prepare("
            SELECT
                sc.id        AS sc_id,
                sc.validade,
                c.nome       AS competencia_nome,
                u.id         AS user_id
            FROM servidor_competencias sc
            INNER JOIN competencias c ON c.id = sc.competencia_id
            INNER JOIN servidores   s ON s.id = sc.servidor_id
            INNER JOIN users        u ON u.servidor_id = s.id
            WHERE sc.validade IS NOT NULL
              AND sc.validade < CURRENT_DATE
              AND u.ativo = true
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $inseridos = 0;
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $scId   = (int) $row['sc_id'];

            if ($this->jaExiste($userId, 'competencia_vencida', $scId)) {
                continue;
            }

            $validade  = (new \DateTime($row['validade']))->format('d/m/Y');
            $this->criar(
                $userId,
                'competencia_vencida',
                'Competência vencida',
                "Sua competência \"{$row['competencia_nome']}\" venceu em {$validade}. Considere renová-la.",
                $scId,
                'servidor_competencias'
            );
            $inseridos++;
        }
        return $inseridos;
    }

    public function gerarMovimentacao(
        int     $movimentacaoId,
        int     $userIdServidor,
        string  $tipo,
        string  $descricao,
        ?int    $userIdGestorDestino = null
    ): void {
        if ($tipo === 'promocao') {
            $this->criar(
                $userIdServidor,
                'promocao',
                'Você foi promovido(a)!',
                $descricao ?: 'Parabéns! Sua promoção foi registrada no sistema.',
                $movimentacaoId,
                'movimentacoes'
            );
            return;
        }

        if ($tipo === 'transferencia') {
            $this->criar(
                $userIdServidor,
                'transferencia',
                'Transferência de unidade',
                $descricao ?: 'Você foi transferido(a) para uma nova unidade.',
                $movimentacaoId,
                'movimentacoes'
            );

            if ($userIdGestorDestino) {
                $this->criar(
                    $userIdGestorDestino,
                    'transferencia',
                    'Novo servidor transferido para sua unidade',
                    'Um servidor foi transferido para a sua unidade. Verifique a lista de colaboradores.',
                    $movimentacaoId,
                    'movimentacoes'
                );
            }
        }
    }

    public function enviarAvisoUnidade(int $unidadeId, string $titulo, string $mensagem, string $tipo = 'aviso_gestor'): int
    {
        $stmt = $this->db->prepare("
            SELECT u.id FROM users u
            WHERE u.unidade_gestor_id = :uid AND u.ativo = true AND u.role = 'user'
        ");
        $stmt->execute(['uid' => $unidadeId]);
        $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $inseridos = 0;
        foreach ($userIds as $userId) {
            $this->criar((int) $userId, $tipo, $titulo, $mensagem);
            $inseridos++;
        }
        return $inseridos;
    }

    public function enviarAvisoGestores(string $titulo, string $mensagem): int
    {
        $stmt = $this->db->prepare("
            SELECT u.id FROM users u
            WHERE u.role = 'gestor' AND u.ativo = true
        ");
        $stmt->execute();
        $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $inseridos = 0;
        foreach ($userIds as $userId) {
            $this->criar((int) $userId, 'aviso_admin', $titulo, $mensagem);
            $inseridos++;
        }
        return $inseridos;
    }
}