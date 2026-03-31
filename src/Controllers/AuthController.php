<?php
/*
 * Controller responsável pela autenticação da aplicação.
 * Gerencia exibição das telas de login e registro, além de processar
 * as requisições de cadastro, login e logout utilizando o AuthService.
 *
 * O método register() aceita requisições AJAX (fetch) vindas do modal
 * do dashboard e retorna JSON. Também continua funcionando para o
 * formulário clássico da página /register.
 *
 * O método login() agora detecta primeiro acesso e redireciona para
 * a tela de validação de identidade quando necessário.
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Models\UserModel;
use App\Models\ServidorModel;
use App\Core\Database;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $db            = Database::getConnection();
        $userModel     = new UserModel($db);
        $servidorModel = new ServidorModel($db);
        $this->authService = new AuthService($userModel, $servidorModel);
    }

    public function loginForm(): void
    {
        $this->view('auth/login');
    }

    public function showRegister(): void
    {
        $this->view('auth/register');
    }

    /*
     * Exibe a tela de validação de identidade (primeiro acesso).
     * Redireciona para o login se não houver RA pendente na sessão.
     */
    public function showValidateIdentity(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['primeiro_acesso_ra'])) {
            header('Location: /login');
            exit;
        }

        $this->view('auth/validate_identity');
    }

    /*
     * Processa o cadastro de novo usuário.
     *
     * Aceita tanto requisições AJAX (fetch do modal, espera JSON de volta)
     * quanto o POST clássico da página /register.
     *
     * Campos recebidos via POST:
     *   name            string  obrigatório
     *   email           string  obrigatório
     *   role            string  admin | gestor | user  (sobrescrito se não for admin)
     *   cpf             string  opcional
     *   cargo           string  opcional
     *   unidade_id      int     opcional
     *   status          string  ativo | inativo
     *   data_nascimento date    opcional (YYYY-MM-DD)
     *   data_admissao   date    opcional (YYYY-MM-DD)
     */
    public function register(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Detecta se a requisição é AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Coleta e sanitiza os dados do POST
        $name           = trim($_POST['name']            ?? '');
        $email          = trim($_POST['email']           ?? '');
        $role           = trim($_POST['role']            ?? 'user');
        $cpf            = trim($_POST['cpf']             ?? '');
        $cargo          = trim($_POST['cargo']           ?? '');
        $unidadeId      = (int) ($_POST['unidade_id']   ?? 0) ?: null;
        $status         = trim($_POST['status']          ?? 'ativo');
        $dataNascimento = trim($_POST['data_nascimento'] ?? '') ?: null;
        $dataAdmissao   = trim($_POST['data_admissao']   ?? '') ?: null;

        // Segurança: apenas admin pode definir roles elevadas
        $sessionRole = $_SESSION['user']['role'] ?? 'guest';
        if ($sessionRole !== 'admin') {
            $role = 'user';
        }

        // Chama o serviço de autenticação
        $result = $this->authService->register(
            $name,
            $email,
            $role,
            $cpf,
            $cargo,
            $unidadeId,
            $status,
            $dataNascimento,
            $dataAdmissao
        );

        // Resposta de erro
        if (isset($result['error'])) {
            if ($isAjax) {
                http_response_code(422);
                $this->jsonResponse(['error' => $result['error']]);
                return;
            }
            $error = $result['error'];
            require __DIR__ . '/../Views/auth/register.php';
            return;
        }

        // Resposta de sucesso
        if ($isAjax) {
            $this->jsonResponse([
                'mensagem' => "Usuário criado com sucesso! RA: {$result['ra']} | Senha padrão: {$result['senha']}",
                'ra'       => $result['ra'],
                'senha'    => $result['senha'],
            ]);
            return;
        }

        $success = "Usuário criado com sucesso! RA: {$result['ra']} | Senha padrão: {$result['senha']}";
        require __DIR__ . '/../Views/auth/register.php';
    }

    /*
     * Processa o login.
     *
     * Caso seja primeiro acesso (senha padrão), interrompe o fluxo normal
     * e redireciona para a tela de validação de identidade.
     */
    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $ra       = trim($_POST['ra']       ?? '');
        $password = trim($_POST['password'] ?? '');

        $result = $this->authService->login($ra, $password);

        // ── Primeiro acesso detectado ─────────────────────────────────
        if (isset($result['first_access'])) {
            header('Location: /validar-identidade');
            exit;
        }

        if (isset($result['error'])) {
            $error = $result['error'];
            require __DIR__ . '/../Views/auth/login.php';
            return;
        }

        header('Location: /dashboard');
        exit;
    }

    /*
     * Processa a validação de identidade no fluxo de primeiro acesso.
     *
     * Requisição AJAX (fetch) — responde sempre em JSON.
     * Em caso de sucesso, o frontend redireciona para /redefinir-senha
     * (responsabilidade do outro dev).
     */
    public function validateIdentity(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        $email          = trim($_POST['email']           ?? '');
        $cpf            = trim($_POST['cpf']             ?? '');
        $dataNascimento = trim($_POST['data_nascimento'] ?? '');

        if (!$email || !$cpf || !$dataNascimento) {
            http_response_code(422);
            echo json_encode(['error' => 'Preencha todos os campos.']);
            return;
        }

        $result = $this->authService->validateIdentity($email, $cpf, $dataNascimento);

        if (isset($result['error'])) {
            http_response_code(422);
            echo json_encode(['error' => $result['error']]);
            return;
        }

        // Sucesso: frontend redirecionará para a tela de redefinição de senha
        echo json_encode(['success' => true, 'redirect' => '/redefinir-senha']);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: /login');
        exit;
    }
}