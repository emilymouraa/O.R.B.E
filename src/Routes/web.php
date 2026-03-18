<?php

use App\Controllers\AuthController;
use App\Models\UserModel;
use App\Models\ServidorModel;
use App\Services\AuthService;

$userModel = new UserModel($conn);
$servidorModel = new ServidorModel($conn);

$authService = new AuthService($userModel, $servidorModel);

$authController = new AuthController($authService);

$router->add('GET', '/login', function () use ($authController) {
    $authController->loginForm();
});

$router->add('GET', '/register', function () use ($authController) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        exit;
    }

    if ($_SESSION['user']['role'] !== 'admin') {
        echo "Acesso negado";
        exit;
    }

    $authController->showRegister();
});

$router->add('POST', '/register', function () use ($authController) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        echo "Acesso negado";
        exit;
    }

    $authController->register();
});

$router->add('POST', '/login', function () use ($authController) {
    $authController->login();
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