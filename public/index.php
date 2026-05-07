<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

/*
 * Arquivo de entrada principal da aplicação (Front Controller).
 * Responsável por iniciar a sessão, carregar dependências do Composer,
 * inicializar variáveis de ambiente, estabelecer a conexão com o banco
 * de dados, registrar as rotas da aplicação e despachar a requisição
 * atual para o Router responsável.
*/

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

$uri = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$base = '/projeto_orbe/O.R.B.E/public';
$uri = str_replace($base, '', $uri) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);