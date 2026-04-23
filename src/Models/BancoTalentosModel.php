<?php
/*
 * Model responsável pelas queries do Banco de Talentos.
 * Trabalha com as tabelas: servidores, avaliacoes,
 * servidor_competencias, competencias e unidades.
 */
namespace App\Models;
use App\Core\Model;
use PDO;

class BancoTalentosModel extends Model
{
    protected string $table = 'servidores';

    public function getIndicadores(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(DISTINCT s.id)                        AS total_talentos,
                ROUND(AVG(a.pontuacao), 1)                  AS pontuacao_media,
                COUNT(DISTINCT CASE
                    WHEN media_servidor.media >= 70 THEN s.id
                END)                                        AS alto_potencial,
                COUNT(DISTINCT CASE
                    WHEN media_servidor.media >= 40
                     AND media_servidor.media < 70 THEN s.id
                END)                                        AS medio_potencial,
                COUNT(DISTINCT CASE
                    WHEN media_servidor.media < 40 THEN s.id
                END)                                        AS baixo_potencial
            FROM servidores s
            INNER JOIN (
                SELECT servidor_id, ROUND(AVG(pontuacao), 1) AS media
                FROM avaliacoes
                GROUP BY servidor_id
            ) AS media_servidor ON media_servidor.servidor_id = s.id
            INNER JOIN avaliacoes a ON a.servidor_id = s.id
            WHERE s.situacao = 'ativo'
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int) $row['total_talentos'];

        return [
            'total_talentos'    => $total,
            'pontuacao_media'   => (float) $row['pontuacao_media'],
            'distribuicao'      => [
                'alto_potencial'  => [
                    'quantidade' => (int) $row['alto_potencial'],
                    'percentual' => $total > 0
                        ? round((int) $row['alto_potencial'] / $total * 100, 1)
                        : 0,
                ],
                'medio_potencial' => [
                    'quantidade' => (int) $row['medio_potencial'],
                    'percentual' => $total > 0
                        ? round((int) $row['medio_potencial'] / $total * 100, 1)
                        : 0,
                ],
                'baixo_potencial' => [
                    'quantidade' => (int) $row['baixo_potencial'],
                    'percentual' => $total > 0
                        ? round((int) $row['baixo_potencial'] / $total * 100, 1)
                        : 0,
                ],
            ],
        ];
    }

    public function getRanking(): array
    {
        $stmt = $this->db->query("
            SELECT
                s.id,
                s.nome,
                s.cargo,
                s.data_ingresso,
                u.nome          AS unidade_nome,
                u.sigla         AS unidade_sigla,
                ROUND(AVG(a.pontuacao), 1)  AS pontuacao,
                COUNT(DISTINCT sc.id)        AS total_competencias,
                CASE
                    WHEN ROUND(AVG(a.pontuacao), 1) >= 70 THEN 'Alto'
                    WHEN ROUND(AVG(a.pontuacao), 1) >= 40 THEN 'Médio'
                    ELSE 'Baixo'
                END             AS nivel_potencial
            FROM servidores s
            INNER JOIN avaliacoes a       ON a.servidor_id   = s.id
            INNER JOIN unidades u         ON u.id            = s.unidade_id
            LEFT JOIN servidor_competencias sc
                ON sc.servidor_id = s.id
               AND sc.visibilidade = 'publica'
            WHERE s.situacao = 'ativo'
            GROUP BY s.id, s.nome, s.cargo, s.data_ingresso,
                     u.nome, u.sigla
            ORDER BY pontuacao DESC
        ");

        $rows    = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ranking = [];

        foreach ($rows as $posicao => $row) {
            $ranking[] = [
                'posicao'            => $posicao + 1,
                'nome'               => $row['nome'],
                'cargo'              => $row['cargo'],
                'unidade'            => $row['unidade_sigla'] . ' — ' . $row['unidade_nome'],
                'pontuacao'          => (float) $row['pontuacao'],
                'total_competencias' => (int)   $row['total_competencias'],
                'tempo_servico'      => $this->calcularTempoServico($row['data_ingresso']),
                'nivel_potencial'    => $row['nivel_potencial'],
            ];
        }

        return $ranking;
    }

    public function buscar(string $termo): array
    {
        $like = '%' . $termo . '%';

        $stmt = $this->db->prepare("
            SELECT DISTINCT
                s.id,
                s.nome,
                s.cargo,
                s.data_ingresso,
                u.nome          AS unidade_nome,
                u.sigla         AS unidade_sigla,
                ROUND(AVG(a.pontuacao), 1)  AS pontuacao,
                COUNT(DISTINCT sc.id)        AS total_competencias,
                CASE
                    WHEN ROUND(AVG(a.pontuacao), 1) >= 70 THEN 'Alto'
                    WHEN ROUND(AVG(a.pontuacao), 1) >= 40 THEN 'Médio'
                    ELSE 'Baixo'
                END             AS nivel_potencial
            FROM servidores s
            INNER JOIN avaliacoes a       ON a.servidor_id   = s.id
            INNER JOIN unidades u         ON u.id            = s.unidade_id
            LEFT JOIN servidor_competencias sc
                ON sc.servidor_id = s.id
               AND sc.visibilidade = 'publica'
            LEFT JOIN competencias c      ON c.id = sc.competencia_id
            WHERE s.situacao = 'ativo'
              AND (
                  s.nome  ILIKE :termo
               OR s.cargo ILIKE :termo
               OR c.nome  ILIKE :termo
              )
            GROUP BY s.id, s.nome, s.cargo, s.data_ingresso,
                     u.nome, u.sigla
            ORDER BY pontuacao DESC
        ");

        $stmt->execute(['termo' => $like]);
        $rows    = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = [];

        foreach ($rows as $row) {
            $results[] = [
                'id'                 => (int)   $row['id'],
                'nome'               => $row['nome'],
                'cargo'              => $row['cargo'],
                'unidade'            => $row['unidade_sigla'] . ' — ' . $row['unidade_nome'],
                'pontuacao'          => (float) $row['pontuacao'],
                'total_competencias' => (int)   $row['total_competencias'],
                'tempo_servico'      => $this->calcularTempoServico($row['data_ingresso']),
                'nivel_potencial'    => $row['nivel_potencial'],
            ];
        }

        return $results;
    }

    private function calcularTempoServico(string $dataIngresso): string
    {
        $inicio = new \DateTime($dataIngresso);
        $hoje   = new \DateTime();
        $diff   = $inicio->diff($hoje);

        $anos  = $diff->y;
        $meses = $diff->m;

        if ($anos === 0) {
            return "{$meses} " . ($meses === 1 ? 'mês' : 'meses');
        }

        if ($meses === 0) {
            return "{$anos} " . ($anos === 1 ? 'ano' : 'anos');
        }

        return "{$anos} " . ($anos === 1 ? 'ano' : 'anos') .
               " e {$meses} " . ($meses === 1 ? 'mês' : 'meses');
    }
}