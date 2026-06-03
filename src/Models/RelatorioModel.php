<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class RelatorioModel extends Model
{
    protected string $table = 'servidores';

    public function getDb(): \PDO
    {
        return $this->db;
    }

    private function montarFiltros(array $filtros): array
    {
        $where  = ["u.role != 'admin'"];
        $params = [];

        if (!empty($filtros['unidades'])) {
            $ids = array_map('intval', (array) $filtros['unidades']);
            $placeholders = implode(',', $ids);
            $where[] = "s.unidade_id IN ({$placeholders})";
        }

        if (!empty($filtros['cargo'])) {
            $where[]         = "s.cargo ILIKE :cargo";
            $params['cargo'] = '%' . $filtros['cargo'] . '%';
        }

        if (!empty($filtros['role'])) {
            $where[]        = "u.role = :role";
            $params['role'] = $filtros['role'];
        }

        if (!empty($filtros['situacao'])) {
            $situacoes    = array_map(fn($s) => "'{$s}'", (array) $filtros['situacao']);
            $where[]      = "s.situacao IN (" . implode(',', $situacoes) . ")";
        }

        if (!empty($filtros['idade_min'])) {
            $where[]              = "EXTRACT(YEAR FROM AGE(CURRENT_DATE, s.data_nascimento)) >= :idade_min";
            $params['idade_min']  = (int) $filtros['idade_min'];
        }
        if (!empty($filtros['idade_max'])) {
            $where[]              = "EXTRACT(YEAR FROM AGE(CURRENT_DATE, s.data_nascimento)) <= :idade_max";
            $params['idade_max']  = (int) $filtros['idade_max'];
        }

        if (!empty($filtros['servico_min'])) {
            $where[]                = "EXTRACT(YEAR FROM AGE(CURRENT_DATE, s.data_ingresso)) >= :servico_min";
            $params['servico_min']  = (int) $filtros['servico_min'];
        }
        if (!empty($filtros['servico_max'])) {
            $where[]                = "EXTRACT(YEAR FROM AGE(CURRENT_DATE, s.data_ingresso)) <= :servico_max";
            $params['servico_max']  = (int) $filtros['servico_max'];
        }

        if (!empty($filtros['admissao_de'])) {
            $where[]                 = "s.data_ingresso >= :admissao_de";
            $params['admissao_de']   = $filtros['admissao_de'];
        }
        if (!empty($filtros['admissao_ate'])) {
            $where[]                 = "s.data_ingresso <= :admissao_ate";
            $params['admissao_ate']  = $filtros['admissao_ate'];
        }

        if (!empty($filtros['competencia_nome'])) {
            $where[]                    = "EXISTS (
                SELECT 1 FROM servidor_competencias sc2
                INNER JOIN competencias c2 ON c2.id = sc2.competencia_id
                WHERE sc2.servidor_id = s.id
                AND c2.nome ILIKE :competencia_nome
            )";
            $params['competencia_nome'] = '%' . $filtros['competencia_nome'] . '%';
        }

        if (!empty($filtros['competencia_tipo'])) {
            $where[]                    = "EXISTS (
                SELECT 1 FROM servidor_competencias sc3
                INNER JOIN competencias c3 ON c3.id = sc3.competencia_id
                WHERE sc3.servidor_id = s.id
                AND c3.tipo = :competencia_tipo
            )";
            $params['competencia_tipo'] = $filtros['competencia_tipo'];
        }

        return [
            'where'  => 'WHERE ' . implode(' AND ', $where),
            'params' => $params,
        ];
    }

    private function montarOrdem(string $ordenar): string
    {
        return match($ordenar) {
            'idade'         => 'ORDER BY s.data_nascimento ASC',
            'tempo_servico' => 'ORDER BY s.data_ingresso ASC',
            'admissao'      => 'ORDER BY s.data_ingresso DESC',
            default         => 'ORDER BY s.nome ASC',
        };
    }

    public function contar(array $filtros): int
    {
        ['where' => $where, 'params' => $params] = $this->montarFiltros($filtros);

        $sql = "
            SELECT COUNT(DISTINCT s.id)
            FROM servidores s
            INNER JOIN users u ON u.servidor_id = s.id
            INNER JOIN unidades un ON un.id = s.unidade_id
            {$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function buscarParaRelatorio(array $filtros, string $ordenar = 'nome'): array
    {
        ['where' => $where, 'params' => $params] = $this->montarFiltros($filtros);
        $ordem = $this->montarOrdem($ordenar);

        $sql = "
            SELECT
                s.id,
                s.nome,
                s.cargo,
                s.patente,
                s.situacao,
                s.data_nascimento,
                s.data_ingresso,
                s.previsao_aposentadoria,
                un.nome  AS unidade_nome,
                un.sigla AS unidade_sigla,
                u.role,
                EXTRACT(YEAR FROM AGE(CURRENT_DATE, s.data_nascimento))::int AS idade,
                EXTRACT(YEAR FROM AGE(CURRENT_DATE, s.data_ingresso))::int   AS anos_servico,
                ROUND(AVG(a.pontuacao), 1) AS pontuacao
            FROM servidores s
            INNER JOIN users u     ON u.servidor_id = s.id
            INNER JOIN unidades un ON un.id = s.unidade_id
            LEFT JOIN avaliacoes a ON a.servidor_id = s.id
            {$where}
            GROUP BY s.id, s.nome, s.cargo, s.patente, s.situacao,
                     s.data_nascimento, s.data_ingresso, s.previsao_aposentadoria,
                     un.nome, un.sigla, u.role
            {$ordem}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($servidores)) {
            $ids         = array_column($servidores, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmtComp = $this->db->prepare("
                SELECT
                    sc.servidor_id,
                    c.nome       AS competencia_nome,
                    c.tipo       AS competencia_tipo,
                    sc.instituicao,
                    sc.carga_horaria,
                    sc.data_conclusao
                FROM servidor_competencias sc
                INNER JOIN competencias c ON c.id = sc.competencia_id
                WHERE sc.servidor_id IN ({$placeholders})
                AND sc.visibilidade = 'publica'
                ORDER BY sc.data_conclusao DESC NULLS LAST
            ");
            $stmtComp->execute($ids);
            $todasCompetencias = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

            $competenciasPorServidor = [];
            foreach ($todasCompetencias as $comp) {
                $competenciasPorServidor[$comp['servidor_id']][] = $comp;
            }

            foreach ($servidores as &$servidor) {
                $servidor['competencias'] = $competenciasPorServidor[$servidor['id']] ?? [];
            }
            unset($servidor);
        }

        return $servidores;
    }
}