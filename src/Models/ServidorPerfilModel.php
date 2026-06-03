<?php
/*
 * Model responsável por buscar todos os dados necessários para o
 * modal de perfil do servidor (admin / gestor).
 * Cada método retorna exatamente as chaves esperadas pelo app.js.
 */
namespace App\Models;

use App\Core\Model;
use PDO;

class ServidorPerfilModel extends Model
{
    protected string $table = 'servidores';

    public function findPerfilById(int $servidorId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                s.id,
                s.ra,
                s.unidade_id,
                s.nome,
                s.cargo,
                s.patente,
                s.situacao,
                TO_CHAR(s.data_ingresso,          'DD/MM/YYYY') AS data_ingresso,
                TO_CHAR(s.previsao_aposentadoria,  'DD/MM/YYYY') AS previsao_aposentadoria,
                s.foto_url,

                u.nome   AS unidade,
                u.sigla  AS unidade_sigla,
                u.estado AS estado,
                u.tipo   AS unidade_tipo,

                usr.email,
                usr.role,

                r.salario_base,
                TO_CHAR(r.data_vigencia, 'DD/MM/YYYY') AS salario_desde,
                r.motivo AS salario_motivo

            FROM servidores s
            LEFT JOIN unidades u   ON u.id  = s.unidade_id
            LEFT JOIN users    usr ON usr.servidor_id = s.id
            LEFT JOIN LATERAL (
                SELECT salario_base, data_vigencia, motivo
                FROM   remuneracoes
                WHERE  servidor_id = s.id
                ORDER  BY data_vigencia DESC
                LIMIT  1
            ) r ON true

            WHERE s.id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $servidorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    // ──────────────────────────────────────────────────────────────
    // Benefícios ativos
    // ──────────────────────────────────────────────────────────────

    public function getBeneficios(int $servidorId): array
    {
        $stmt = $this->db->prepare("
            SELECT tipo, descricao
            FROM   servidor_beneficios
            WHERE  servidor_id = :id
              AND  ativo = true
            ORDER  BY tipo
        ");

        $stmt->execute(['id' => $servidorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────────────────────
    // Histórico de movimentações
    // ──────────────────────────────────────────────────────────────

    public function getMovimentacoes(int $servidorId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                m.tipo,
                m.descricao,
                TO_CHAR(m.data_inicio, 'DD/MM/YYYY') AS data_inicio,
                uo.nome AS unidade_origem,
                ud.nome AS unidade_destino
            FROM   movimentacoes m
            LEFT JOIN unidades uo ON uo.id = m.unidade_origem_id
            LEFT JOIN unidades ud ON ud.id = m.unidade_destino_id
            WHERE  m.servidor_id = :id
            ORDER  BY m.data_inicio DESC
        ");

        $stmt->execute(['id' => $servidorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────────────────────
    // Avaliações de desempenho
    // ──────────────────────────────────────────────────────────────

    public function getAvaliacoes(int $servidorId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                a.pontuacao,
                a.observacao,
                TO_CHAR(a.data_avaliacao, 'DD/MM/YYYY') AS data_avaliacao,
                u.nome AS avaliador
            FROM   avaliacoes a
            LEFT JOIN users u ON u.id = a.avaliador_id
            WHERE  a.servidor_id = :id
            ORDER  BY a.data_avaliacao DESC
        ");

        $stmt->execute(['id' => $servidorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────────────────────
    // Competências e certificações
    // ──────────────────────────────────────────────────────────────

    public function getCompetencias(int $servidorId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                c.nome,
                c.tipo,
                TO_CHAR(sc.validade, 'DD/MM/YYYY') AS validade
            FROM   servidor_competencias sc
            JOIN   competencias c ON c.id = sc.competencia_id
            WHERE  sc.servidor_id = :id
            ORDER  BY c.tipo, c.nome
        ");

        $stmt->execute(['id' => $servidorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────────────────────
    // Férias e afastamentos
    // ──────────────────────────────────────────────────────────────

    public function getFeriasAfastamentos(int $servidorId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                tipo,
                TO_CHAR(data_inicio, 'DD/MM/YYYY') AS data_inicio,
                TO_CHAR(data_fim,    'DD/MM/YYYY') AS data_fim,
                dias
            FROM   ferias_afastamentos
            WHERE  servidor_id = :id
            ORDER  BY data_inicio DESC
        ");

        $stmt->execute(['id' => $servidorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────────────────────
    // PDIs com ações aninhadas
    // ──────────────────────────────────────────────────────────────

    public function getPdis(int $servidorId): array
    {
        $stmtPdi = $this->db->prepare("
            SELECT
                p.id,
                p.cargo_objetivo,
                p.status,
                TO_CHAR(p.data_revisao, 'DD/MM/YYYY') AS data_revisao,
                u.nome AS responsavel
            FROM   pdis p
            LEFT JOIN users u ON u.id = p.responsavel_id
            WHERE  p.servidor_id = :id
            ORDER  BY p.created_at DESC
        ");

        $stmtPdi->execute(['id' => $servidorId]);
        $pdis = $stmtPdi->fetchAll(PDO::FETCH_ASSOC);

        if (empty($pdis)) {
            return [];
        }

        // Busca ações de todos os PDIs em uma única query
        $pdiIds       = array_column($pdis, 'id');
        $placeholders = implode(',', array_fill(0, count($pdiIds), '?'));

        $stmtAcoes = $this->db->prepare("
            SELECT
                pdi_id,
                descricao,
                concluida,
                TO_CHAR(prazo, 'DD/MM/YYYY') AS prazo
            FROM   pdi_acoes
            WHERE  pdi_id IN ({$placeholders})
            ORDER  BY pdi_id, concluida, prazo
        ");

        $stmtAcoes->execute($pdiIds);
        $todasAcoes = $stmtAcoes->fetchAll(PDO::FETCH_ASSOC);

        // Agrupa ações por pdi_id
        $acoesPorPdi = [];
        foreach ($todasAcoes as $acao) {
            $acoesPorPdi[$acao['pdi_id']][] = [
                'descricao' => $acao['descricao'],
                'concluida' => (bool) $acao['concluida'],
                'prazo'     => $acao['prazo'],
            ];
        }

        foreach ($pdis as &$pdi) {
            $pdi['acoes'] = $acoesPorPdi[$pdi['id']] ?? [];
            unset($pdi['id']); // não expõe ID interno
        }
        unset($pdi);

        return $pdis;
    }
}