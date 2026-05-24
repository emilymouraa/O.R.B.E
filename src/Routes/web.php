<?php
/*Arquivo responsável por registrar as rotas web da aplicação.
 * Aqui são associadas URLs e métodos HTTP aos métodos do AuthController,
 * além de conter uma verificação simples de sessão para acesso ao dashboard
 * e as rotas protegidas da API. */

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Models\UserModel;
use App\Models\ServidorModel;
use App\Services\AuthService;
use App\Controllers\UnidadeController;
use App\Controllers\OrganogramaController;
use App\Controllers\BancoTalentosController;
use App\Controllers\PainelController;

$userModel     = new UserModel($conn);
$servidorModel = new ServidorModel($conn);
$authService   = new AuthService($userModel, $servidorModel);
$authController = new AuthController($authService);
$router->add('GET', '/login', function () use ($authController) {
    $authController->loginForm();
});
$router->add('GET', '/register', function () use ($authController) {
    $authController->showRegister();
});
$router->add('POST', '/register', function () use ($authController) {
    $authController->register();
});
$router->add('POST', '/login', function () use ($authController) {
    $authController->login();
});

$router->add('GET', '/validar-identidade', function () use ($authController) {
    $authController->showValidateIdentity();
});

$router->add('POST', '/validar-identidade', function () use ($authController) {
    $authController->validateIdentity();
});

$router->add('GET', '/redefinir-senha', function () use ($authController) {
    $authController->showResetPassword();
});

$router->add('POST', '/redefinir-senha', function () use ($authController) {
    $authController->resetPassword();
});

$router->add('POST', '/logout', function () {
    $controller = new App\Controllers\AuthController();
    $controller->logout();
});

$router->add('GET', '/dashboard', function () {
    \App\Middleware\AuthMiddleware::handle();
    $role = $_SESSION['user']['role'] ?? 'user';
    if ($role === 'user') {
        header('Location: /perfil');
        exit;
    }
    require __DIR__ . '/../Views/dashboard/home.php';
});

$router->add('GET', '/api/users', function () use ($conn) {
    (new UserController($conn))->index();
});

$router->add('GET', '/api/users/{id}', function () use ($conn) {
    (new UserController($conn))->show((int) $_GET['id']);
});

$router->add('POST', '/api/users', function () use ($conn) {
    (new UserController($conn))->store();
});

$router->add('PUT', '/api/users/{id}', function () use ($conn) {
    (new UserController($conn))->update((int) $_GET['id']);
});

$router->add('GET', '/api/unidades', function () use ($conn) {
    (new \App\Controllers\UnidadeController($conn))->index();
});
$router->add('GET', '/api/organograma/hierarquia-usuarios', function () use ($conn) {
    (new OrganogramaController($conn))->index();
});

$router->add('GET', '/api/organograma/hierarquia-usuarios/gestor', function () use ($conn) {
    (new OrganogramaController($conn))->usuariosPorGestor();
});

$router->add('POST', '/api/perfil/foto', function () use ($conn) {
    (new UserController($conn))->uploadFoto();
});

$router->add('PUT',  '/api/perfil',      function () use ($conn) {
    (new UserController($conn))->updatePerfil();
});

$rotasProtegidas = [
    '/usuarios'       => [
        'view'  => 'dashboard/home.php',
        'toast' => null,
        'roles' => ['admin'],
    ],
    '/colaboradores'  => [
        'view'  => 'dashboard/colaboradores.php',
        'toast' => null,
        'roles' => ['user', 'gestor'],
    ],
    '/perfil'         => [
        'view'  => 'dashboard/perfil.php',
        'toast' => null,
        'roles' => ['admin', 'gestor', 'user'],
    ],
    '/organograma'    => [
        'view'  => 'dashboard/organograma.php',
        'toast' => ['type' => 'info', 'title' => 'Organograma', 'desc' => 'Exibindo a hierarquia atual.'],
        'roles' => ['admin', 'gestor', 'user'],
    ],
    '/competencias'   => [
        'view'  => 'dashboard/competencias.php',
        'toast' => ['type' => 'info', 'title' => 'Competências', 'desc' => 'Gerencie as competências cadastradas.'],
        'roles' => ['admin', 'gestor', 'user'],
    ],
    '/banco-talentos' => [
        'view'  => 'dashboard/banco-talentos.php',
        'toast' => ['type' => 'info', 'title' => 'Banco de Talentos', 'desc' => 'Visualize e filtre os servidores disponíveis.'],
        'roles' => ['admin', 'gestor'],
    ],
    '/painel'         => [
        'view'  => 'dashboard/painel.php',
        'toast' => ['type' => 'success', 'title' => 'Painel atualizado', 'desc' => 'Dados atualizados em ' . (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('H:i') . '.'],
        'roles' => ['admin', 'gestor'],
    ],
];

foreach ($rotasProtegidas as $uri => $rota) {
    $router->add('GET', $uri, function () use ($rota) {
        \App\Middleware\AuthMiddleware::handle();

        $role = $_SESSION['user']['role'] ?? 'user';

        if (!in_array($role, $rota['roles'], true)) {
            $destino = $role === 'user' ? '/perfil' : '/painel';
            header("Location: {$destino}");
            exit;
        }

        if ($rota['toast'] && empty($_SESSION['toast'])) {
            \App\Helpers\Toast::set(
                $rota['toast']['type'],
                $rota['toast']['title'],
                $rota['toast']['desc']
            );
        }

        require __DIR__ . '/../Views/' . $rota['view'];
    });
}

$router->add('GET', '/api/banco-talentos/indicadores', function () use ($conn) {
    (new BancoTalentosController($conn))->indicadores();
});

$router->add('GET', '/api/banco-talentos/ranking', function () use ($conn) {
    (new BancoTalentosController($conn))->ranking();
});

$router->add('GET', '/api/banco-talentos/busca', function () use ($conn) {
    (new BancoTalentosController($conn))->busca();
});

$router->add('GET', '/api/painel/indicadores', function () use ($conn) {
    (new PainelController($conn))->indicadores();
});
 
$router->add('GET', '/api/painel/servidores-por-unidade', function () use ($conn) {
    (new PainelController($conn))->servidoresPorUnidade();
});
    
$router->add('GET', '/api/painel/distribuicao-status', function () use ($conn) {
    (new PainelController($conn))->distribuicaoStatus();
});

$router->add('GET', '/api/perfil', function () use ($conn) {
    (new UserController($conn))->perfil();
});

$router->add('GET', '/api/colaboradores', function () use ($conn) {
    (new UserController($conn))->colaboradores();
});
$router->add('PUT', '/api/gestor/colaboradores/{id}', function () use ($conn) {
    (new UserController($conn))->updateColaboradorGestor((int) $_GET['id']);
});

$router->add('GET', '/api/notificacoes', function () use ($conn) {
    (new \App\Controllers\NotificacaoController($conn))->index();
});

$router->add('GET', '/api/notificacoes/count', function () use ($conn) {
    (new \App\Controllers\NotificacaoController($conn))->count();
});

$router->add('PATCH', '/api/notificacoes/todas-lidas', function () use ($conn) {
    (new \App\Controllers\NotificacaoController($conn))->marcarTodasLidas();
});

$router->add('POST', '/api/notificacoes/gerar-competencias', function () use ($conn) {
    (new \App\Controllers\NotificacaoController($conn))->gerarCompetencias();
});

$router->add('PATCH', '/api/notificacoes/{id}/lida', function () use ($conn) {
    (new \App\Controllers\NotificacaoController($conn))->marcarLida((int) $_GET['id']);
});

$router->add('GET', '/api/competencias', function () use ($conn) {
    (new \App\Controllers\CompetenciaController($conn))->index();
});
$router->add('POST', '/api/competencias', function () use ($conn) {
    (new \App\Controllers\CompetenciaController($conn))->store();
});
$router->add('POST', '/api/competencias/{id}/anexo', function () use ($conn) {
    (new \App\Controllers\CompetenciaController($conn))->uploadAnexo((int) $_GET['id']);
});

$router->add('GET', '/api/servidores/{id}/perfil', function () use ($conn) {
    (new \App\Controllers\ServidorController($conn))->perfil((int) $_GET['id']);
});

$router->add('GET', '/api/organograma/hierarquia-usuario', function () use ($conn) {
    (new OrganogramaController($conn))->hierarquiaUsuario();
});

$router->add('POST', '/api/relatorio/preview', function () use ($conn) {
    (new \App\Controllers\RelatorioController($conn))->preview();
});

$router->add('POST', '/api/relatorio/exportar', function () use ($conn) {
    (new \App\Controllers\RelatorioController($conn))->exportar();
});

$router->add('POST', '/api/relatorio/exportar-xlsx', function () use ($conn) {
    (new \App\Controllers\RelatorioController($conn))->exportarXlsx();
});

$router->add('GET', '/api/relatorio/holerite', function () use ($conn) {
    (new \App\Controllers\RelatorioController($conn))->gerarHolerite();
});

$router->add('GET', '/api/relatorio/espelho', function () use ($conn) {
    (new \App\Controllers\RelatorioController($conn))->gerarEspelho();
});

$router->add('GET',  '/api/chat/conversas', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->conversas();
});

$router->add('POST', '/api/chat/conversas', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->abrirConversa();
});

$router->add('GET',  '/api/chat/contatos', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->contatos();
});

$router->add('GET', '/api/chat/conversas/{id}/mensagens', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->mensagens((int) $_GET['id']);
});

$router->add('POST', '/api/chat/conversas/{id}/mensagens', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->enviar((int) $_GET['id']);
});

$router->add('GET', '/api/chat/count', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->count();
});

$router->add('GET', '/api/chat/colaboradores', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->colaboradores();
});

$router->add('POST', '/api/chat/solicitacao-transferencia', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->solicitarTransferencia();
});

$router->add('GET',  '/api/chat/solicitacoes', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->solicitacoes();
});

$router->add('POST', '/api/chat/solicitacoes/{id}/aprovar', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->aprovar((int) $_GET['id']);
});

$router->add('POST', '/api/chat/solicitacoes/{id}/rejeitar', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->rejeitar((int) $_GET['id']);
});

$router->add('POST', '/api/chat/solicitacoes/{id}/executar', function () use ($conn) {
    (new \App\Controllers\ChatController($conn))->executar((int) $_GET['id']);
});

$router->add('POST', '/api/notificacoes/aviso', function () use ($conn) {
    (new \App\Controllers\NotificacaoController($conn))->enviarAviso();
});

$router->add('POST', '/api/chamados', function () use ($conn) {
    (new \App\Controllers\ChamadoController($conn))->store();
});
$router->add('GET', '/api/chamados/pendentes', function () use ($conn) {
    (new \App\Controllers\ChamadoController($conn))->pendentes();
});
$router->add('GET', '/api/chamados/meus', function () use ($conn) {
    (new \App\Controllers\ChamadoController($conn))->meus();
});
$router->add('GET', '/api/chamados/{id}', function () use ($conn) {
    (new \App\Controllers\ChamadoController($conn))->show((int) $_GET['id']);
});
$router->add('POST', '/api/chamados/{id}/concluir', function () use ($conn) {
    (new \App\Controllers\ChamadoController($conn))->concluir((int) $_GET['id']);
});
$router->add('POST', '/api/chamados/{id}/rejeitar', function () use ($conn) {
    (new \App\Controllers\ChamadoController($conn))->rejeitar((int) $_GET['id']);
});