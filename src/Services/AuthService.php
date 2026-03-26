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

    public function register(string $name, string $email, string $role): array {

    // 🔹 Validação de domínio
    if (!str_ends_with($email, '@prf.govmg.com.br')) {
        return ['error' => 'Domínio de email inválido'];
    }

    // 🔹 Verifica se email já existe
    $emailExistente = $this->userModel->findByEmail($email);
    if ($emailExistente) {
        return ['error' => 'Email já cadastrado'];
    }

    // 🔐 Segurança: só admin pode criar admin
    if ($_SESSION['user']['role'] !== 'admin') {
        $role = 'user';
    }

    $ra = $this->servidorModel->getNextRa();

    // 🔥 CPF único (não usar rand)
    $cpf = uniqid();

    // 🔹 Cria servidor
    $servidorId = $this->servidorModel->create([
        'ra' => $ra,
        'nome' => $name,
        'cpf' => $cpf,
        'data_nascimento' => '2000-01-01',
        'data_ingresso' => date('Y-m-d'),
        'cargo' => 'Não informado',
        'unidade_id' => 1
    ]);

    // 🔐 Senha padrão
    $senhaPadrao = 'Teste123';
    $hash = password_hash($senhaPadrao, PASSWORD_DEFAULT);

    // 🔹 Cria usuário
    $this->userModel->create([
        'servidor_id' => $servidorId,
        'nome' => $name,
        'email' => $email,
        'password' => $hash,
        'role' => $role
    ]);

    return [
        'success' => true,
        'ra' => $ra,
        'senha' => $senhaPadrao
    ];
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

        if ((bool)$user['ativo'] === false) {
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