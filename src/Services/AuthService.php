<?php

/*
 * Serviço responsável pela lógica de autenticação da aplicação.
 * Centraliza regras de negócio de cadastro e login (validações,
 * verificação de servidor, criação de usuário e abertura de sessão).
 */

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

        // Validação de domínio institucional
        if (!str_ends_with($email, '@prf.govmg.com.br')) {
            return ['error' => 'Domínio de email inválido'];
        }

        // Regra mínima de segurança da senha
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            return ['error' => 'Senha deve ter 8 caracteres, com maiúscula, minúscula e número'];
        }

        $servidor = $this->servidorModel->findByRa($ra);

        if (!$servidor) {
            return ['error' => 'RA não encontrado'];
        }

        $userExistente = $this->userModel->findByServidorId($servidor['id']);

        if ($userExistente) {
            return ['error' => 'Usuário já cadastrado'];
        }

        $emailExistente = $this->userModel->findByEmail($email);

        if ($emailExistente) {
            return ['error' => 'Email já cadastrado'];
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

    public function login(string $ra, string $password): array {

        $servidor = $this->servidorModel->findByRa($ra);

        if (!$servidor) {
            return ['error' => 'RA não encontrado'];
        }

        $user = $this->userModel->findByServidorId($servidor['id']);

        if (!$user) {
            return ['error' => 'Usuário não possui acesso ao sistema'];
        }

        if (!$user['ativo']) {
            return ['error' => 'Usuário inativo'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['error' => 'Senha inválida'];
        }

        // Cria os dados básicos da sessão do usuário autenticado
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'role' => $user['role'],
            'servidor_id' => $servidor['id']
        ];

        return ['success' => true];
    }
}