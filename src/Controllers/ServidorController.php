<?php
/*
 * Controller responsável pelo endpoint de perfil do servidor.
 * Acessível apenas por admin e gestor (via RoleMiddleware).
 * Responde com JSON contendo todas as chaves consumidas pelo modal
 * modal-perfil-servidor no app.js.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ServidorPerfilModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

class ServidorController extends Controller
{
    private ServidorPerfilModel $model;

    public function __construct(\PDO $conn)
    {
        $this->model = new ServidorPerfilModel($conn);
    }

    public function perfil(int $servidorId): void
    {
        AuthMiddleware::handle();

        $userSession  = $_SESSION['user'];
        $role         = $userSession['role'] ?? '';
        $meuServidorId = (int) ($userSession['servidor_id'] ?? 0);

        // user só pode ver o próprio perfil
        if ($role === 'user' && $servidorId !== $meuServidorId) {
            $this->jsonResponse(['error' => 'Acesso negado.'], 403);
            return;
        }

        // admin e gestor bloqueados normalmente
        if (!in_array($role, ['admin', 'gestor', 'user'], true)) {
            $this->jsonResponse(['error' => 'Acesso negado.'], 403);
            return;
        }

        if ($servidorId <= 0) {
            $this->jsonResponse(['error' => 'ID de servidor inválido.'], 400);
            return;
        }

        $perfil = $this->model->findPerfilById($servidorId);
        if (!$perfil) {
            $this->jsonResponse(['error' => 'Servidor não encontrado.'], 404);
            return;
        }

        if (
            $role === 'gestor'
            && ($perfil['role'] ?? '') === 'admin'
        ) {
            $this->jsonResponse(['error' => 'Acesso negado.'], 403);
            return;
        }

        $beneficios         = $this->model->getBeneficios($servidorId);
        $movimentacoes      = $this->model->getMovimentacoes($servidorId);
        $avaliacoes         = $this->model->getAvaliacoes($servidorId);
        $competencias       = $this->model->getCompetencias($servidorId);
        $feriasAfastamentos = $this->model->getFeriasAfastamentos($servidorId);
        $pdis               = $this->model->getPdis($servidorId);

        $this->jsonResponse([
            'data' => [
                'ra'                     => $perfil['ra'],
                'nome'                   => $perfil['nome'],
                'cargo'                  => $perfil['cargo'],
                'patente'                => $perfil['patente'],
                'situacao'               => $perfil['situacao'],
                'foto_url'               => $perfil['foto_url'],
                'data_ingresso'          => $perfil['data_ingresso'],
                'previsao_aposentadoria' => $perfil['previsao_aposentadoria'],
                'unidade'                => $perfil['unidade'],
                'unidade_sigla'          => $perfil['unidade_sigla'],
                'estado'                 => $perfil['estado'],
                'unidade_tipo'           => $perfil['unidade_tipo'],
                'email'                  => $perfil['email'],
                'salario_base'           => $perfil['salario_base'],
                'salario_desde'          => $perfil['salario_desde'],
                'salario_motivo'         => $perfil['salario_motivo'],
                'beneficios'             => $beneficios,
                'movimentacoes'          => $movimentacoes,
                'avaliacoes'             => $avaliacoes,
                'competencias'           => $competencias,
                'ferias_afastamentos'    => $feriasAfastamentos,
                'pdis'                   => $pdis,
            ],
        ]);
    }
}