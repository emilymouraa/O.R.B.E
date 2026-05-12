<?php
/*Controller responsável por retornar as notificações
 * do servidor logado. Acessível por todos os roles autenticados.*/
namespace App\Controllers;

use App\Core\Controller;
use App\Models\NotificacaoModel;
use App\Middleware\AuthMiddleware;

class NotificacaoController extends Controller
{
    private NotificacaoModel $model;

    public function __construct(\PDO $conn)
    {
        $this->model = new NotificacaoModel($conn);
    }

    public function index(): void
    {
        AuthMiddleware::handle();

        $servidorId = (int) ($_SESSION['user']['servidor_id'] ?? 0);

        if (!$servidorId) {
            $this->jsonResponse(['data' => [], 'total' => 0]);
            return;
        }

        $notifs = $this->model->getNotificacoes($servidorId);

        $this->jsonResponse([
            'data'  => $notifs,
            'total' => count($notifs),
        ]);
    }
}