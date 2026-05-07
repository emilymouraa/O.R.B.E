<?php
/*
 * Arquivo responsável por registrar as rotas web da aplicação.
 * Aqui são associadas URLs e métodos HTTP aos métodos do AuthController,
 * além de conter uma verificação simples de sessão para acesso ao dashboard
 * e as rotas protegidas da API.
 */
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

// GET  exibe a tela de validação.
// POST processa os dados via fetch e responde em JSON.
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
    require __DIR__ . '/../Views/dashboard/home.php';
});

$router->add('GET', '/api/users', function () use ($conn) {
    (new UserController($conn))->index();
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

$rotasProtegidas = [
    '/usuarios'       => ['view' => 'dashboard/home.php',          'toast' => null],
    '/perfil'         => ['view' => 'dashboard/perfil.php',        'toast' => null],
    '/organograma'    => ['view' => 'dashboard/organograma.php',   'toast' => ['type' => 'info',    'title' => 'Organograma',      'desc' => 'Exibindo a hierarquia atual.']],
    '/competencias'   => ['view' => 'dashboard/competencias.php',  'toast' => ['type' => 'info',    'title' => 'Competências',     'desc' => 'Gerencie as competências cadastradas.']],
    '/banco-talentos' => ['view' => 'dashboard/banco-talentos.php','toast' => ['type' => 'info',    'title' => 'Banco de Talentos','desc' => 'Visualize e filtre os servidores disponíveis.']],
    '/painel'         => ['view' => 'dashboard/painel.php',        'toast' => ['type' => 'success', 'title' => 'Painel atualizado','desc' => 'Dados atualizados em ' . (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('H:i') . '.']],
];

foreach ($rotasProtegidas as $uri => $rota) {
    $router->add('GET', $uri, function () use ($rota) {
        \App\Middleware\AuthMiddleware::handle();
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

foreach ($rotasProtegidas as $uri => $view) {
    $router->add('GET', $uri, function () use ($view) {
        \App\Middleware\AuthMiddleware::handle();
        require __DIR__ . '/../Views/' . $view;
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

$router->add('GET', '/api/competencias', function () use ($conn) {
    (new \App\Controllers\CompetenciaController($conn))->index();
});