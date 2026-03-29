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

    // Senha padrão definida para todos os novos servidores cadastrados pelo admin.
    private const SENHA_PADRAO = 'Teste123';

    public function __construct(UserModel $userModel, ServidorModel $servidorModel) {
        $this->userModel = $userModel;
        $this->servidorModel = $servidorModel;
    }

    public function register(
        string $name,
        string $email,
        string $role,
        string $cpf            = '',
        string $cargo          = '',
        ?int   $unidadeId      = null,
        string $status         = 'ativo',
        ?string $dataNascimento = null,
        ?string $dataAdmissao   = null
    ): array {

        // Validação de domínio
        if (!str_ends_with($email, '@prf.govmg.com.br')) {
            return ['error' => 'Domínio de email inválido'];
        }

        // Verifica se email já existe
        $emailExistente = $this->userModel->findByEmail($email);
        if ($emailExistente) {
            return ['error' => 'Email já cadastrado'];
        }

        // Segurança: só admin pode criar admin
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            $role = 'user';
        }

        $ra = $this->servidorModel->getNextRa();

        // Cria servidor com os dados recebidos
        $servidorId = $this->servidorModel->create([
            'ra'              => $ra,
            'nome'            => $name,
            'cpf'             => $cpf ?: uniqid(),
            'data_nascimento' => $dataNascimento ?? '2000-01-01',
            'data_ingresso'   => $dataAdmissao   ?? date('Y-m-d'),
            'cargo'           => $cargo ?: 'Não informado',
            'unidade_id'      => $unidadeId ?? 1,
        ]);

        // Hash da senha padrão
        $hash = password_hash(self::SENHA_PADRAO, PASSWORD_DEFAULT);

        // Cria usuário vinculado ao servidor
        $this->userModel->create([
            'servidor_id' => $servidorId,
            'nome'        => $name,
            'email'       => $email,
            'password'    => $hash,
            'role'        => $role,
        ]);

        return [
            'success' => true,
            'ra'      => $ra,
            'senha'   => self::SENHA_PADRAO,
        ];
    }

    public function login(string $ra, string $password): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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

        // ── Detecção de primeiro acesso ───────────────────────────────
        // Se a senha informada ainda é a senha padrão, o fluxo normal
        // de login é interrompido. Salvamos apenas o RA na sessão como
        // "pendente de validação" e sinalizamos o primeiro acesso.
        if (password_verify(self::SENHA_PADRAO, $user['password'])) {
            $_SESSION['primeiro_acesso_ra'] = $ra;
            return ['first_access' => true];
        }

        // Cria os dados completos da sessão do usuário autenticado
        $_SESSION['user'] = [
            'id'         => $user['id'],
            'nome'       => $user['nome'],
            'role'       => $user['role'],
            'servidor_id'=> $servidor['id'],
        ];

        return ['success' => true];
    }

    /*
     * Valida a identidade do servidor no fluxo de primeiro acesso.
     *
     * Recebe e-mail corporativo, data de nascimento e CPF.
     * O RA é lido diretamente da sessão (gravado em login()).
     *
     * Retorna ['success' => true] se os dados conferem com o banco,
     * ou ['error' => '...'] caso haja inconsistência.
     */
    public function validateIdentity(
        string $email,
        string $cpf,
        string $dataNascimento
    ): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Garante que existe um RA pendente na sessão
        $ra = $_SESSION['primeiro_acesso_ra'] ?? null;
        if (!$ra) {
            return ['error' => 'Sessão expirada. Faça o login novamente.'];
        }

        $servidor = $this->servidorModel->findByRa($ra);
        if (!$servidor) {
            return ['error' => 'Dados inconsistentes.'];
        }

        $user = $this->userModel->findByServidorId($servidor['id']);
        if (!$user) {
            return ['error' => 'Dados inconsistentes.'];
        }

        // Normaliza CPF: remove pontos e traços para comparação
        $cpfInformado  = preg_replace('/\D/', '', $cpf);
        $cpfCadastrado = preg_replace('/\D/', '', $servidor['cpf']);

        // Compara os três campos com o banco
        $emailOk     = strtolower(trim($email))  === strtolower(trim($user['email']));
        $cpfOk       = $cpfInformado             === $cpfCadastrado;
        $nascimentoOk = trim($dataNascimento)    === trim($servidor['data_nascimento']);

        if (!$emailOk || !$cpfOk || !$nascimentoOk) {
            return ['error' => 'Dados inconsistentes. Verifique as informações e tente novamente.'];
        }

        // Dados válidos: marca na sessão que a identidade foi confirmada
        $_SESSION['identidade_validada'] = true;

        return ['success' => true];
    }
}