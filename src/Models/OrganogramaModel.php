<?php
/*
 * Model responsável por consultar os dados do organograma funcional.
 * Ele separa as consultas por nível hierárquico (admin, gestores e usuários),
 * ordena os resultados e prepara os dados que serão consumidos pela API.
 */
namespace App\Models;

use App\Core\Model;
use PDO;

class OrganogramaModel extends Model
{
    protected string $table = 'users';

    public function getOrganogramaBase(): array
    {
        $admin = $this->getAdminRaiz();

        if ($admin === null) {
            return [];
        }

        $gestores = $this->getGestores();
        $gestoresFormatados = [];

        foreach ($gestores as $gestor) {
            $unidadeId = (int) $gestor['unidade_gestor_id'];

            $gestoresFormatados[] = [
                'id' => (int) $gestor['id'],
                'tipo' => 'gestor',
                'nome' => $gestor['nome'],
                'nome_exibicao' => $this->getNomeExibicao($gestor['nome']),
                'cargo' => 'Gestor',
                'cargo_label' => 'Gestor',
                'unidade_id' => $unidadeId,
                'unidade_nome' => $gestor['unidade_nome'] ?? null,
                'avatar' => null,
                'cor' => $this->getCorUnidade($unidadeId),
                'expandido' => false,
                'quantidade_usuarios' => $this->countUsuariosPorUnidade($unidadeId),
                'children' => []
            ];
        }

        return [
            'id' => (int) $admin['id'],
            'tipo' => 'admin',
            'nome' => $admin['nome'],
            'nome_exibicao' => $this->getNomeExibicao($admin['nome']),
            'cargo' => 'Administrador',
            'cargo_label' => 'Administrador',
            'avatar' => null,
            'cor' => '#1E3A8A',
            'expandido' => false,
            'children' => $gestoresFormatados
        ];
    }

    public function getUsuariosPorGestor(int $gestorId): ?array
    {
        $gestor = $this->getGestorPorId($gestorId);

        if ($gestor === null) {
            return null;
        }

        $unidadeId = (int) $gestor['unidade_gestor_id'];
        $usuarios = $this->getUsuariosPorUnidade($unidadeId);
        $usuariosFormatados = [];

        foreach ($usuarios as $usuario) {
            $usuariosFormatados[] = [
                'id' => (int) $usuario['id'],
                'tipo' => 'user',
                'nome' => $usuario['nome'],
                'nome_exibicao' => $this->getNomeExibicao($usuario['nome']),
                'cargo' => $this->getCargoLabel($usuario['cargo'] ?? ''),
                'cargo_label' => $this->getCargoLabel($usuario['cargo'] ?? ''),
                'avatar' => null,
                'cor' => $this->getCorUnidade($unidadeId),
                'expandido' => false,
                'children' => []
            ];
        }

        return [
            'gestor' => [
                'id' => (int) $gestor['id'],
                'tipo' => 'gestor',
                'nome' => $gestor['nome'],
                'nome_exibicao' => $this->getNomeExibicao($gestor['nome']),
                'cargo' => 'Gestor',
                'cargo_label' => 'Gestor',
                'unidade_id' => $unidadeId,
                'unidade_nome' => $gestor['unidade_nome'] ?? null,
                'avatar' => null,
                'cor' => $this->getCorUnidade($unidadeId),
                'expandido' => false
            ],
            'children' => $usuariosFormatados
        ];
    }

    private function getAdminRaiz(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.servidor_id,
                u.unidade_gestor_id,
                u.nome,
                u.email,
                u.role,
                u.ativo
            FROM users u
            WHERE u.role = 'admin'
              AND u.ativo = true
            ORDER BY u.id ASC
            LIMIT 1
        ");

        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $admin ?: null;
    }

    private function getGestores(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.servidor_id,
                u.unidade_gestor_id,
                u.nome,
                u.email,
                u.role,
                u.ativo,
                un.nome AS unidade_nome,
                un.sigla AS unidade_sigla
            FROM users u
            LEFT JOIN unidades un ON un.id = u.unidade_gestor_id
            WHERE u.role = 'gestor'
              AND u.ativo = true
            ORDER BY u.nome ASC
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getGestorPorId(int $gestorId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.servidor_id,
                u.unidade_gestor_id,
                u.nome,
                u.email,
                u.role,
                u.ativo,
                un.nome AS unidade_nome,
                un.sigla AS unidade_sigla
            FROM users u
            LEFT JOIN unidades un ON un.id = u.unidade_gestor_id
            WHERE u.id = :gestor_id
              AND u.role = 'gestor'
              AND u.ativo = true
            LIMIT 1
        ");

        $stmt->execute([
            'gestor_id' => $gestorId
        ]);

        $gestor = $stmt->fetch(PDO::FETCH_ASSOC);

        return $gestor ?: null;
    }

    private function getUsuariosPorUnidade(int $unidadeId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.servidor_id,
                u.unidade_gestor_id,
                u.nome,
                u.email,
                u.role,
                u.ativo,
                s.cargo,
                s.patente
            FROM users u
            INNER JOIN servidores s ON s.id = u.servidor_id
            WHERE u.role = 'user'
              AND u.ativo = true
              AND u.unidade_gestor_id = :unidade_gestor_id
            ORDER BY
                CASE s.cargo
                    WHEN 'classe_especial' THEN 1
                    WHEN '1a_classe' THEN 2
                    WHEN '2a_classe' THEN 3
                    WHEN '3a_classe' THEN 4
                    ELSE 99
                END ASC,
                u.nome ASC
        ");

        $stmt->execute([
            'unidade_gestor_id' => $unidadeId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function countUsuariosPorUnidade(int $unidadeId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM users
            WHERE role = 'user'
              AND ativo = true
              AND unidade_gestor_id = :unidade_gestor_id
        ");

        $stmt->execute([
            'unidade_gestor_id' => $unidadeId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['total'] ?? 0);
    }

    private function getNomeExibicao(string $nome): string
    {
        $nome = trim($nome);

        if ($nome === '') {
            return '';
        }

        $partes = preg_split('/\s+/', $nome);

        if (!$partes || count($partes) === 1) {
            return $nome;
        }

        return $partes[0] . ' ' . $partes[count($partes) - 1];
    }

    private function getCargoLabel(string $cargo): string
    {
        return match ($cargo) {
            'classe_especial' => 'Divisão Especial',
            '1a_classe' => '1ª Classe',
            '2a_classe' => '2ª Classe',
            '3a_classe' => '3ª Classe',
            default => ucfirst(str_replace('_', ' ', $cargo)),
        };
    }

    private function getCorUnidade(int $unidadeId): string
    {
        $cores = [
            1 => '#2563EB',
            2 => '#16A34A',
            3 => '#EA580C',
            4 => '#9333EA',
            5 => '#DC2626',
        ];

        return $cores[$unidadeId] ?? '#6B7280';
    }
}