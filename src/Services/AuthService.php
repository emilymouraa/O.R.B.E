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

    public function register(string $ra, string $name, string $email, string $password, string $role): array {

            // Validação de domínio
            if (!str_ends_with($email, '@prf.govmg.com.br')) {
                return ['error' => 'Domínio de email inválido'];
            }

            // Validação de senha
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                return ['error' => 'Senha deve ter 8 caracteres, com maiúscula, minúscula e número'];
            }

            // Verifica se email já existe
            $emailExistente = $this->userModel->findByEmail($email);
            if ($emailExistente) {
                return ['error' => 'Email já cadastrado'];
            }

            // 🔥 NOVO: cria ou busca servidor
            $servidor = $this->servidorModel->findByRa($ra);

            if (!$servidor) {
                $servidorId = $this->servidorModel->create([
                    'ra' => $ra,
                    'nome' => $name,
                    'cpf' => rand(10000000000, 99999999999),
                    'data_nascimento' => '2000-01-01',
                    'data_ingresso' => date('Y-m-d'),
                    'cargo' => 'Não informado',
                    'unidade_id' => 1
                ]);
            } else {
                $servidorId = $servidor['id'];
            }

            // Segurança de role
            if ($_SESSION['user']['role'] !== 'admin') {
                $role = 'user';
            }

            // Criptografia da senha
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Cria usuário
            $this->userModel->create([
                'servidor_id' => $servidorId,
                'nome' => $name,
                'email' => $email,
                'password' => $hash,
                'role' => $role
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