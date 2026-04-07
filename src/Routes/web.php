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
// ── Primeiro acesso — Validação de identidade ────────────────────
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
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        exit;
    }
    require __DIR__ . '/../Views/dashboard/home.php';
});
// ── API de Usuários ──────────────────────────────────────────────
// Protegida por AuthMiddleware + RoleMiddleware (somente admin).
// GET /api/users?page=1&limit=10&search=...&role=...&ativo=...&unidade_id=...
$router->add('GET', '/api/users', function () use ($conn) {
    (new UserController($conn))->index();
});
$router->add('GET', '/api/unidades', function () use ($conn) {
    (new \App\Controllers\UnidadeController($conn))->index();
});

// ── Rotas protegidas — páginas do sistema ────────────────────────
// Adicionadas para suportar a sidebar de navegação.
// Todas verificam sessão e redirecionam para /login se não autenticado.
$rotasProtegidas = [
    '/usuarios'        => 'dashboard/home.php',
    '/perfil'          => 'dashboard/perfil.php',
    '/organograma'     => 'dashboard/organograma.php',
    '/competencias'    => 'dashboard/competencias.php',
    '/banco-talentos'  => 'dashboard/banco-talentos.php',
    '/painel'          => 'dashboard/painel.php',
];

foreach ($rotasProtegidas as $uri => $view) {
    $router->add('GET', $uri, function () use ($view) {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/../Views/' . $view;
    });
}