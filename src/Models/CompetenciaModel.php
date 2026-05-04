<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class CompetenciaModel extends Model
{
    protected string $table = 'servidor_competencias';

    public function listar(): array
    {
        $stmt = $this->db->query("
            SELECT
                sc.id,
                c.nome,
                c.tipo,
                s.nome AS servidor,
                sc.data_conclusao,
                sc.validade
            FROM servidor_competencias sc
            INNER JOIN competencias c ON c.id = sc.competencia_id
            INNER JOIN servidores s ON s.id = sc.servidor_id
            ORDER BY sc.data_conclusao DESC NULLS LAST
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}