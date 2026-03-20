<?php

/*
 * Model responsável pelo acesso à tabela de usuários (users).
 * Contém métodos específicos para buscar usuários por email,
 * por servidor vinculado e para criar novos registros.
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class UserModel extends Model {

    protected string $table = 'users';

    public function findByEmail(string $email): ?array {

        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByServidorId(int $servidorId): ?array {

        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE servidor_id = :servidor_id
            LIMIT 1
        ");

        $stmt->execute([
            'servidor_id' => $servidorId
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function create(array $data): bool {

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (servidor_id, nome, email, password, role, ativo)
            VALUES
            (:servidor_id, :nome, :email, :password, :role, 1)
        ");

        return $stmt->execute([
            'servidor_id' => $data['servidor_id'],
            'nome' => $data['nome'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role']
        ]);
    }
}