<?php

/*
 * Controller responsável pela autenticação da aplicação.
 * Gerencia exibição das telas de login e registro, além de processar
 * as requisições de cadastro, login e logout utilizando o AuthService.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Models\UserModel;
use App\Models\ServidorModel;
use App\Core\Database;

class AuthController extends Controller {

    private AuthService $authService;

    public function __construct() {

        // Cria a conexão com o banco e instancia os models necessários
        // para o serviço de autenticação
        $db = Database::getConnection();

        $userModel = new UserModel($db);
        $servidorModel = new ServidorModel($db);

        // Inicializa o serviço responsável pela lógica de autenticação
        $this->authService = new AuthService($userModel, $servidorModel);
    }

    public function loginForm() {
        // Exibe a página de login
        $this->view('auth/login');
    }

    public function showRegister() {
        // Exibe a página de cadastro
        $this->view('auth/register');
    }

    public function register() {

        // Obtém os dados enviados pelo formulário de cadastro
        $ra = $_POST['ra'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Envia os dados para o serviço responsável pelo cadastro
        $result = $this->authService->register($ra, $email, $password);

        // Caso ocorra algum erro no cadastro, retorna para a tela
        // de registro exibindo a mensagem
        if (isset($result['error'])) {

            $error = $result['error'];

            require __DIR__ . '/../Views/auth/register.php';
            return;
        }

        // Após cadastro bem sucedido redireciona para o login
        header('Location: /login');
        exit;
    }

    public function login() {

        // Obtém os dados enviados pelo formulário de login
        $ra = $_POST['ra'] ?? '';
        $password = $_POST['password'] ?? '';

        // Processa a autenticação através do AuthService
        $result = $this->authService->login($ra, $password);

        // Caso haja erro na autenticação, retorna para a tela de login
        // exibindo a mensagem correspondente
        if (isset($result['error'])) {

            $error = $result['error'];

            require __DIR__ . '/../Views/auth/login.php';
            return;
        }

        // Login bem sucedido redireciona o usuário para o dashboard
        header('Location: /dashboard');
        exit;
    }

    public function logout() {

        // Garante que a sessão esteja iniciada antes de destruí-la
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Encerra a sessão do usuário
        session_destroy();

        // Redireciona para a tela de login
        header('Location: /login');
        exit;
    }
}