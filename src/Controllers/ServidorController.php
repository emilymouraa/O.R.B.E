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
        // Apenas usuários autenticados com perfil admin ou gestor
        AuthMiddleware::handle();
        RoleMiddleware::handle(['admin', 'gestor']);

        if ($servidorId <= 0) {
            http_response_code(400);
            $this->jsonResponse(['error' => 'ID de servidor inválido.']);
            return;
        }

        // Dados principais
        $perfil = $this->model->findPerfilById($servidorId);

        if (!$perfil) {
            http_response_code(404);
            $this->jsonResponse(['error' => 'Servidor não encontrado.']);
            return;
        }

        // Dados relacionados (queries separadas para clareza e manutenção)
        $beneficios        = $this->model->getBeneficios($servidorId);
        $movimentacoes     = $this->model->getMovimentacoes($servidorId);
        $avaliacoes        = $this->model->getAvaliacoes($servidorId);
        $competencias      = $this->model->getCompetencias($servidorId);
        $feriasAfastamentos = $this->model->getFeriasAfastamentos($servidorId);
        $pdis              = $this->model->getPdis($servidorId);

        // Monta o payload com exatamente as chaves esperadas pelo app.js
        $this->jsonResponse([
            'data' => [
                // ── Cabeçalho ──────────────────────────────────────
                'ra'                    => $perfil['ra'],
                'nome'                  => $perfil['nome'],
                'cargo'                 => $perfil['cargo'],
                'patente'               => $perfil['patente'],
                'situacao'              => $perfil['situacao'],
                'foto_url'              => $perfil['foto_url'],

                // ── Aba Funcional — Dados do Cargo ──────────────────
                'data_ingresso'         => $perfil['data_ingresso'],
                'previsao_aposentadoria'=> $perfil['previsao_aposentadoria'],

                // ── Aba Funcional — Lotação ─────────────────────────
                'unidade'               => $perfil['unidade'],
                'unidade_sigla'         => $perfil['unidade_sigla'],
                'estado'                => $perfil['estado'],
                'unidade_tipo'          => $perfil['unidade_tipo'],
                'email'                 => $perfil['email'],

                // ── Aba Funcional — Remuneração ─────────────────────
                'salario_base'          => $perfil['salario_base'],
                'salario_desde'         => $perfil['salario_desde'],
                'salario_motivo'        => $perfil['salario_motivo'],

                // ── Aba Funcional — Benefícios ──────────────────────
                'beneficios'            => $beneficios,

                // ── Aba Trajetória ──────────────────────────────────
                'movimentacoes'         => $movimentacoes,
                'avaliacoes'            => $avaliacoes,
                'competencias'          => $competencias,
                'ferias_afastamentos'   => $feriasAfastamentos,
                'pdis'                  => $pdis,
            ],
        ]);
    }
}