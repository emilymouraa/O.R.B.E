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
    private ?int $unidadeId = null;
    private ?string $role = null;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->role = $_SESSION['user']['role'] ?? null;

        if ($this->role === 'gestor') {
            $this->unidadeId = $_SESSION['user']['unidade_id'] ?? null;
        }
    }

    public function indicadores(): void
    {
        AuthMiddleware::handle();

        $unidadeId = $_SESSION['user']['unidade_id'];
        $role      = $_SESSION['user']['role'];

        $sql = "
            SELECT COUNT(*)
            FROM servidores
            WHERE situacao = 'ativo'
        ";

        $params = [];

        if ($role === 'gestor') {
            $sql .= " AND unidade_id = :unidade_id";
            $params['unidade_id'] = $unidadeId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $total = $stmt->fetchColumn();

        $sql = "
            SELECT ROUND(
                (
                    COUNT(*) FILTER (
                        WHERE DATE_TRUNC('month', data_ingresso) = DATE_TRUNC('month', CURRENT_DATE)
                    )
                    -
                    COUNT(*) FILTER (
                        WHERE DATE_TRUNC('month', data_ingresso) = DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month')
                    )
                )::NUMERIC
                /
                NULLIF(
                    COUNT(*) FILTER (
                        WHERE DATE_TRUNC('month', data_ingresso) = DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month')
                    ),
                    0
                ) * 100
            , 2)
            FROM servidores
            WHERE 1=1
        ";

        $params = [];

        if ($role === 'gestor') {
            $sql .= " AND unidade_id = :unidade_id";
            $params['unidade_id'] = $unidadeId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $crescimento = $stmt->fetchColumn();

        $unidadeId = $_SESSION['user']['unidade_id'];
        $role      = $_SESSION['user']['role'];

        $sql = "
            SELECT COUNT(*)
            FROM servidores
            WHERE situacao = 'ativo'
            AND previsao_aposentadoria IS NOT NULL
            AND previsao_aposentadoria BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '2 years'
        ";

        $params = [];

        if ($role === 'gestor') {
            $sql .= " AND unidade_id = :unidade_id";
            $params['unidade_id'] = $unidadeId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $aposentadoria = $stmt->fetchColumn();

        $capacitacoesAno = 0;
        $crescimentoCap  = 0;

        
        $sql = "
            SELECT ROUND(
                AVG(EXTRACT(YEAR FROM AGE(CURRENT_DATE, data_ingresso)))
            , 1)
            FROM servidores
            WHERE situacao = 'ativo'
        ";

        $params = [];

        if ($role === 'gestor') {
            $sql .= " AND unidade_id = :unidade_id";
            $params['unidade_id'] = $unidadeId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $tempoMedio = $stmt->fetchColumn();

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

        $sql = "
            SELECT u.nome AS unidade, COUNT(s.id) AS total
            FROM unidades u
            LEFT JOIN servidores s
                ON s.unidade_id = u.id
            AND s.situacao = 'ativo'
            WHERE u.ativa = true
        ";

        $params = [];

        if ($this->role === 'gestor') {
            $sql .= " AND u.id = :unidade_id";
            $params['unidade_id'] = $this->unidadeId;
        }

        $sql .= "
            GROUP BY u.id, u.nome
            ORDER BY total DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $this->jsonResponse([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    public function distribuicaoStatus(): void
    {
        AuthMiddleware::handle();

        $sql = "
            SELECT
                COALESCE(situacao::text, 'Não informado') AS status,
                COUNT(*) AS total
            FROM servidores
        ";

        $params = [];

        if ($this->role === 'gestor') {
            $sql .= " WHERE unidade_id = :unidade_id";
            $params['unidade_id'] = $this->unidadeId;
        }

        $sql .= "
            GROUP BY situacao
            ORDER BY total DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $this->jsonResponse([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }
}