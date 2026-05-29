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

    public function getDb(): \PDO
    {
        return $this->db;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (ra, nome, cpf, data_nascimento, data_ingresso, cargo, unidade_id, situacao)
            VALUES
            (:ra, :nome, :cpf, :data_nascimento, :data_ingresso, :cargo, :unidade_id, :situacao)
            RETURNING id
        ");
        $stmt->execute([
            'ra'              => $data['ra'],
            'nome'            => $data['nome'],
            'cpf'             => $data['cpf'],
            'data_nascimento' => $data['data_nascimento'],
            'data_ingresso'   => $data['data_ingresso'],
            'cargo'           => $data['cargo'],
            'unidade_id'      => $data['unidade_id'],
            'situacao'        => $data['situacao'] ?? 'ativo',
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) $row['id'];
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

    public function update(int $servidorId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET nome             = :nome,
                cpf              = :cpf,
                cargo            = :cargo,
                unidade_id       = :unidade_id,
                situacao         = :situacao,
                data_nascimento  = :data_nascimento,
                data_ingresso    = :data_ingresso,
                updated_at       = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'nome'            => $data['nome'],
            'cpf'             => $data['cpf'],
            'cargo'           => $data['cargo'],
            'unidade_id'      => $data['unidade_id'],
            'situacao'        => $data['situacao'],
            'data_nascimento' => $data['data_nascimento'] ?: null,
            'data_ingresso'   => $data['data_ingresso']   ?: null,
            'id'              => $servidorId,
        ]);
    }

    public function updateDataNascimento(int $servidorId, string $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE servidores
            SET data_nascimento = :data_nascimento,
                updated_at      = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'data_nascimento' => $data,
            'id'              => $servidorId,
        ]);
    }
}