<?php
/*Model responsável por gerar notificações derivadas de dados
 * já existentes no banco. Não possui tabela própria.
 * Fontes: servidor_competencias, pdi_acoes, pdis.*/
namespace App\Models;

use App\Core\Model;
use PDO;

class NotificacaoModel extends Model
{
    protected string $table = 'servidor_competencias';
    public function getNotificacoes(int $servidorId): array
    {
        $notifs = [];

        $stmt = $this->db->prepare("
            SELECT c.nome, TO_CHAR(sc.validade, 'DD/MM/YYYY') AS validade
            FROM   servidor_competencias sc
            JOIN   competencias c ON c.id = sc.competencia_id
            WHERE  sc.servidor_id = :id
              AND  sc.validade < CURRENT_DATE
            ORDER  BY sc.validade ASC
        ");
        $stmt->execute(['id' => $servidorId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $notifs[] = [
                'nivel'     => 'danger',
                'titulo'    => 'Competência vencida',
                'descricao' => "{$row['nome']} — venceu em {$row['validade']}",
                'lida'      => false,
            ];
        }

        $stmt = $this->db->prepare("
            SELECT c.nome, TO_CHAR(sc.validade, 'DD/MM/YYYY') AS validade
            FROM   servidor_competencias sc
            JOIN   competencias c ON c.id = sc.competencia_id
            WHERE  sc.servidor_id = :id
              AND  sc.validade >= CURRENT_DATE
              AND  sc.validade <= CURRENT_DATE + INTERVAL '90 days'
            ORDER  BY sc.validade ASC
        ");
        $stmt->execute(['id' => $servidorId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $notifs[] = [
                'nivel'     => 'warn',
                'titulo'    => 'Competência a vencer',
                'descricao' => "{$row['nome']} — vence em {$row['validade']}",
                'lida'      => false,
            ];
        }

        $stmt = $this->db->prepare("
            SELECT pa.descricao, TO_CHAR(pa.prazo, 'DD/MM/YYYY') AS prazo
            FROM   pdi_acoes pa
            JOIN   pdis p ON p.id = pa.pdi_id
            WHERE  p.servidor_id = :id
              AND  pa.concluida  = false
              AND  pa.prazo < CURRENT_DATE
            ORDER  BY pa.prazo ASC
        ");
        $stmt->execute(['id' => $servidorId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $notifs[] = [
                'nivel'     => 'warn',
                'titulo'    => 'Ação de PDI em atraso',
                'descricao' => "{$row['descricao']} — prazo era {$row['prazo']}",
                'lida'      => false,
            ];
        }

        return $notifs;
    }
}