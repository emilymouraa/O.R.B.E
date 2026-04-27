<?php

/*
 * Model responsável por acessar a tabela de servidores no banco de dados.
 * Estende o Model base e adiciona uma busca específica por RA.
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class ServidorModel extends Model {

    protected string $table = 'servidores';

    public function findByRa(string $ra): ?array {

        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE ra = :ra
            LIMIT 1
        ");

        $stmt->execute([
            'ra' => $ra
        ]);

        $servidor = $stmt->fetch(PDO::FETCH_ASSOC);

        return $servidor ?: null;
    }

    public function create(array $data): int {

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (ra, nome, cpf, data_nascimento, data_ingresso, cargo, unidade_id)
            VALUES
            (:ra, :nome, :cpf, :data_nascimento, :data_ingresso, :cargo, :unidade_id)
        ");

        $stmt->execute([
            'ra' => $data['ra'],
            'nome' => $data['nome'],
            'cpf' => $data['cpf'],
            'data_nascimento' => $data['data_nascimento'],
            'data_ingresso' => $data['data_ingresso'],
            'cargo' => $data['cargo'],
            'unidade_id' => $data['unidade_id']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getNextRa(): string {

    $stmt = $this->db->query("
        SELECT MAX(CAST(SUBSTRING(ra, 4) AS INTEGER)) as max_ra
        FROM {$this->table}
        WHERE ra LIKE 'PRF%'
    ");

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $numero = $result['max_ra'] ?? 0;

    $novoNumero = $numero + 1;

    return 'PRF' . str_pad($novoNumero, 5, '0', STR_PAD_LEFT);
}

    
}