<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Router;
use App\Controllers\AuthController;
// use App\Models\User;

$conn = Database::getConnection();

// $userModel = new User($conn);

// $authController = new AuthController($userModel);

$router = new Router();

$router->add('GET', '/', function () {
    echo json_encode(['message' => 'ORBE iniciado com sucesso!']);
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);