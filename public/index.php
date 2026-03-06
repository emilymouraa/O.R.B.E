<?php

session_start();

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Router;
use App\Core\Env;

Env::load(__DIR__ . '/../.env');

$conn = Database::getConnection();

$router = new Router();

require __DIR__ . '/../src/Routes/web.php';

$router->add('GET', '/', function () {
    header('Location: /login');
    exit;
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);