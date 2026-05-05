<?php
/*
 * Views/auth/register.php
 * Tela de cadastro de usuário — acessível apenas por admins.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: /dashboard');
    exit;
}

$pageTitle = 'Cadastro';
$bodyClass = 'auth-page';
require __DIR__ . '/../layout/header.php';
?>

<div class="login-container">
    <div class="login-box">
        <div class="logo-container">
            <img src="/assets/images/orbe_logo.jpeg" alt="ORBE Logo" class="logo">
        </div>
        <h2>Criar Conta</h2>

        <form method="POST" action="/register">
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="role">Tipo de usuário</label>
                <select id="role" name="role" required>
                    <option value="user">Usuário</option>
                    <option value="gestor">Gestor</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Criar conta</button>
        </form>

        <p class="register-link">
            Já possui conta? <a href="/login">Fazer login</a>
        </p>
    </div>
</div>

<button class="theme-toggle" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>

<?php require __DIR__ . '/../layout/footer.php'; ?>