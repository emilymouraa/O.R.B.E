<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller {
    private AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function showLogin() {
        $this->view('auth/login');
    }

    public function showRegister() {
        $this->view('auth/register');
    }

    public function register() {
        $ra = $_POST['ra'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->authService->register($ra, $email, $password);

        if (isset($result['error'])) {
            echo $result['error'];
            return;
        }

        $this->redirect('/login');
    }
}