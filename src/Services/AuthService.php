<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\ServidorModel;

class AuthService {
    private UserModel $userModel;
    private ServidorModel $servidorModel;

    public function __construct(UserModel $userModel, ServidorModel $servidorModel) {
        $this->userModel = $userModel;
        $this->servidorModel = $servidorModel;
    }

    public function register(string $ra, string $email, string $password): array {
        if (!str_ends_with($email, '@prf.govmg.com.br')) {
            return ['error' => 'Domínio inválido'];
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            return ['error' => 'Senha deve ter 8 caracteres, maiúscula, minúscula e número'];
        }

        $servidor = $this->servidorModel->findByRA($ra);

        if (!$servidor) {
            return ['error' => 'RA não encontrado'];
        }

        $userExistente = $this->userModel->findByServidor($servidor['id']);

        if ($userExistente) {
            return ['error' => 'Usuário já cadastrado'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->userModel->create([
            'servidor_id' => $servidor['id'],
            'nome' => $servidor['nome'],
            'email' => $email,
            'password' => $hash,
            'role' => 'user'
        ]);

        return ['success' => true];
    }
}