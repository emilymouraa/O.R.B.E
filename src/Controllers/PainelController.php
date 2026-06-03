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

        $admissoesAno = $this->conn->query("
            SELECT COUNT(*)
            FROM servidores
            WHERE situacao = 'ativo'
            AND DATE_TRUNC('year', data_ingresso) =
                DATE_TRUNC('year', CURRENT_DATE)
        ")->fetchColumn();

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

        $capacitacoesAno = $this->conn->query("
            SELECT COUNT(*)
            FROM servidor_competencias
            WHERE data_conclusao >= DATE_TRUNC('year', CURRENT_DATE)
        ")->fetchColumn();
        
        $crescimentoCap = $this->conn->query("
            WITH dados AS (
                SELECT
                    COUNT(*) FILTER (
                        WHERE DATE_TRUNC('year', data_conclusao) =
                            DATE_TRUNC('year', CURRENT_DATE)
                    ) AS atual,

                    COUNT(*) FILTER (
                        WHERE DATE_TRUNC('year', data_conclusao) =
                            DATE_TRUNC('year', CURRENT_DATE - INTERVAL '1 year')
                    ) AS anterior
                FROM servidor_competencias
            )

            SELECT
                CASE
                    WHEN anterior = 0 AND atual = 0 THEN 0
                    WHEN anterior = 0 THEN 100
                    ELSE ROUND(((atual - anterior)::numeric / anterior) * 100, 1)
                END
            FROM dados
        ")->fetchColumn();

        
        $tempoMedio = $this->conn->query("
            SELECT ROUND(
                AVG(
                    EXTRACT(YEAR FROM AGE(CURRENT_DATE, data_ingresso))
                )
            , 1)
            FROM servidores
            WHERE situacao = 'ativo'
            AND data_ingresso IS NOT NULL
            AND data_ingresso <= CURRENT_DATE
        ")->fetchColumn();

        $this->jsonResponse([
            'data' => [
                'total_servidores'       => (int) $total,
                'admissoes_ano'          => (int) $admissoesAno,
                'proximos_aposentadoria' => (int) $aposentadoria,
                'capacitacoes_ano'       => (int) $capacitacoesAno,
                'tempo_medio_servico'    => (float) ($tempoMedio ?? 0),
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

    public function mapa(): void
    {
        AuthMiddleware::handle();

        $role = $_SESSION['user']['role'];
        $unidadeId = $_SESSION['user']['unidade_id'] ?? null;
        $usuarioServidorId = $_SESSION['user']['servidor_id'] ?? null;

        $sql = "
            SELECT
                s.id,
                s.nome,
                s.patente,
                s.cargo,
                s.unidade_id,
                u.nome AS unidade,
                u.sigla,
                u.id AS unidade_base_id,
                p.cidade,
                p.latitude,
                p.longitude,
                p.status,
                us.role
            FROM plantoes p

            INNER JOIN servidores s
                ON s.id = p.servidor_id

            INNER JOIN unidades u
                ON u.id = s.unidade_id

            INNER JOIN users us
                ON us.servidor_id = s.id

            WHERE p.status = 'ativo'
        ";

        $params = [];

        // ADMIN:
        // vê somente:
        // - ele mesmo
        // - gestores
        if ($role === 'admin') {

            $sql .= "
                AND (
                    us.role IN ('admin', 'gestor', 'user')
                )
            ";
        }

        // GESTOR:
        // vê:
        // - ele mesmo
        // - colaboradores da mesma unidade
        if ($role === 'gestor') {

            $sql .= "
                AND (
                    s.unidade_id = :unidade_id
                )
            ";

            $params['unidade_id'] = $unidadeId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dados as &$item) {

        $numeroImagem = $item['unidade_base_id'];

        $item['imagem_base'] =
            "/assets/images/unidade{$numeroImagem}.png";
    }

        $this->jsonResponse([
            'data' => $dados
        ]);
    }
}