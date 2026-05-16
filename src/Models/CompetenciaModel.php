<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class CompetenciaModel extends Model
{
    protected string $table = 'servidor_competencias';

    // Lista competências do servidor logado
    public function listarPorServidor(int $servidorId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sc.id,
                c.nome,
                c.tipo,
                sc.instituicao,
                sc.descricao,
                sc.carga_horaria,
                sc.data_conclusao,
                sc.validade,
                sc.visibilidade,
                sc.anexo_url,
                sc.anexo_nome,
                sc.created_at
            FROM servidor_competencias sc
            INNER JOIN competencias c ON c.id = sc.competencia_id
            WHERE sc.servidor_id = :servidor_id
            ORDER BY sc.data_conclusao DESC NULLS LAST
        ");
        $stmt->execute(['servidor_id' => $servidorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorUnidade(int $unidadeId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sc.id,
                c.nome,
                c.tipo,
                sc.instituicao,
                sc.descricao,
                sc.carga_horaria,
                sc.data_conclusao,
                sc.validade,
                sc.visibilidade,
                sc.anexo_url,
                sc.anexo_nome,
                sc.created_at,
                s.nome AS servidor
            FROM servidor_competencias sc
            INNER JOIN competencias c ON c.id = sc.competencia_id
            INNER JOIN servidores s   ON s.id = sc.servidor_id
            INNER JOIN users u        ON u.servidor_id = s.id
            WHERE s.unidade_id = :unidade_id
            AND u.role != 'admin'
            ORDER BY s.nome ASC, sc.data_conclusao DESC NULLS LAST
        ");
        $stmt->execute(['unidade_id' => $unidadeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodas(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sc.id,
                c.nome,
                c.tipo,
                sc.instituicao,
                sc.descricao,
                sc.carga_horaria,
                sc.data_conclusao,
                sc.validade,
                sc.visibilidade,
                sc.anexo_url,
                sc.anexo_nome,
                sc.created_at,
                s.nome AS servidor
            FROM servidor_competencias sc
            INNER JOIN competencias c ON c.id = sc.competencia_id
            INNER JOIN servidores s   ON s.id = sc.servidor_id
            INNER JOIN users u        ON u.servidor_id = s.id
            WHERE u.role != 'admin'
            ORDER BY s.nome ASC, sc.data_conclusao DESC NULLS LAST
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cria nova entrada em competencias + servidor_competencias
    public function criar(array $dados): ?int
    {
        try {
            $this->db->beginTransaction();

            // 1. Busca ou cria a competência base
            $stmt = $this->db->prepare("
                SELECT id FROM competencias
                WHERE nome = :nome AND tipo = :tipo
                LIMIT 1
            ");
            $stmt->execute(['nome' => $dados['nome'], 'tipo' => $dados['tipo']]);
            $competencia = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($competencia) {
                $competenciaId = (int) $competencia['id'];
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO competencias (nome, tipo)
                    VALUES (:nome, :tipo)
                    RETURNING id
                ");
                $stmt->execute(['nome' => $dados['nome'], 'tipo' => $dados['tipo']]);
                $competenciaId = (int) $stmt->fetchColumn();
            }

            // 2. Insere em servidor_competencias
            $stmt = $this->db->prepare("
                INSERT INTO servidor_competencias
                    (servidor_id, competencia_id, instituicao, descricao,
                     carga_horaria, data_conclusao, validade, visibilidade)
                VALUES
                    (:servidor_id, :competencia_id, :instituicao, :descricao,
                     :carga_horaria, :data_conclusao, :validade, :visibilidade)
                RETURNING id
            ");
            $stmt->execute([
                'servidor_id'    => $dados['servidor_id'],
                'competencia_id' => $competenciaId,
                'instituicao'    => $dados['instituicao'],
                'descricao'      => $dados['descricao'],
                'carga_horaria'  => $dados['carga_horaria'],
                'data_conclusao' => $dados['data_conclusao'],
                'validade'       => $dados['validade'],
                'visibilidade'   => $dados['visibilidade'],
            ]);
            $id = (int) $stmt->fetchColumn();

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }

    // Verifica se a competência pertence ao servidor
    public function pertenceAoServidor(int $competenciaId, int $servidorId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM servidor_competencias
            WHERE id = :id AND servidor_id = :servidor_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $competenciaId, 'servidor_id' => $servidorId]);
        return (bool) $stmt->fetch();
    }

    // Salva o anexo na entrada existente
    public function salvarAnexo(int $id, string $url, string $nome): void
    {
        $stmt = $this->db->prepare("
            UPDATE servidor_competencias
            SET anexo_url = :url, anexo_nome = :nome
            WHERE id = :id
        ");
        $stmt->execute(['url' => $url, 'nome' => $nome, 'id' => $id]);
    }
}