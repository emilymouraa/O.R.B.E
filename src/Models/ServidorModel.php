<?php

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
}