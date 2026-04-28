<?php
/*
 * Controllers/PainelController.php
 * Expõe os endpoints do Painel de RH da PRF.
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use PDO;

class PainelController extends Controller
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function indicadores(): void
    {
        AuthMiddleware::handle();

        $total = $this->conn
            ->query("SELECT COUNT(*) FROM servidores WHERE situacao = 'ativo'")
            ->fetchColumn();

        $crescimento = $this->conn->query("
            SELECT ROUND(
                (
                    COUNT(*) FILTER (WHERE DATE_TRUNC('month', data_ingresso) = DATE_TRUNC('month', CURRENT_DATE))
                    - COUNT(*) FILTER (WHERE DATE_TRUNC('month', data_ingresso) = DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month'))
                )::NUMERIC
                / NULLIF(
                    COUNT(*) FILTER (WHERE DATE_TRUNC('month', data_ingresso) = DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month')),
                    0
                ) * 100
            , 2)
            FROM servidores
        ")->fetchColumn();

        $aposentadoria = $this->conn->query("
            SELECT COUNT(*)
            FROM servidores
            WHERE situacao = 'ativo'
              AND previsao_aposentadoria IS NOT NULL
              AND previsao_aposentadoria BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '2 years'
        ")->fetchColumn();

        $capacitacoesAno = 0;
        $crescimentoCap  = 0;

        $tempoMedio = $this->conn->query("
            SELECT ROUND(
                AVG(EXTRACT(YEAR FROM AGE(CURRENT_DATE, data_ingresso)))
            , 1)
            FROM servidores
            WHERE situacao = 'ativo'
        ")->fetchColumn();

        $this->jsonResponse([
            'data' => [
                'total_servidores'         => (int)   $total,
                'crescimento_percentual'   => (float) ($crescimento ?? 0),
                'proximos_aposentadoria'   => (int)   $aposentadoria,
                'capacitacoes_ano'         => (int)   $capacitacoesAno,
                'crescimento_capacitacoes' => (float) $crescimentoCap,
                'tempo_medio_servico'      => (float) ($tempoMedio ?? 0),
            ]
        ]);
    }

    public function servidoresPorUnidade(): void
    {
        AuthMiddleware::handle();

        $stmt = $this->conn->query("
            SELECT u.nome AS unidade, COUNT(s.id) AS total
            FROM unidades u
            LEFT JOIN servidores s ON s.unidade_id = u.id AND s.situacao = 'ativo'
            WHERE u.ativa = true
            GROUP BY u.id, u.nome
            ORDER BY total DESC
        ");

        $this->jsonResponse([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    public function distribuicaoStatus(): void
    {
        AuthMiddleware::handle();

        $stmt = $this->conn->query("
            SELECT
                COALESCE(situacao::text, 'Não informado') AS status,
                COUNT(*) AS total
            FROM servidores
            GROUP BY situacao
            ORDER BY total DESC
        ");

        $this->jsonResponse([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }
}