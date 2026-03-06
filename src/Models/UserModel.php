<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class UserModel extends Model {
    protected string $table = 'users';

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByServidor(int $servidor_id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE servidor_id = :id LIMIT 1");
        $stmt->execute(['id' => $servidor_id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (servidor_id, nome, email, password, role)
            VALUES
            (:servidor_id, :nome, :email, :password, :role)
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