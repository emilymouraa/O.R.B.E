<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Models\UserModel;
use App\Models\ServidorModel;
use App\Core\Database;

class AuthController extends Controller {

    private AuthService $authService;

    public function __construct() {

        $db = Database::getConnection();

        $userModel = new UserModel($db);
        $servidorModel = new ServidorModel($db);

        $this->authService = new AuthService($userModel, $servidorModel);
    }

    public function loginForm() {
        $this->view('auth/login');
    }

    public function showRegister() {

        session_start();

        if ($_SESSION['user']['role'] !== 'admin') {
            header('Location: /dashboard');
            exit;
        }

        $this->view('auth/register');
    }

    public function register() {

        $ra = $_POST['ra'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->authService->register($ra, $email, $password);

        if (isset($result['error'])) {

            $error = $result['error'];

            require __DIR__ . '/../Views/auth/register.php';
            return;
        }

        header('Location: /login');
        exit;
    }

    public function login() {

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->authService->login($email, $password);

        if (isset($result['error'])) {

            $error = $result['error'];

            require __DIR__ . '/../Views/auth/login.php';
            return;
        }
        session_start();

        $_SESSION['user'] = [
            'id' => $result['id'],
            'name' => $result['name'],
            'role' => $result['role']
        ];
        header('Location: /dashboard');
        exit;
    }

    public function logout() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();

        header('Location: /login');
        exit;
    }
}