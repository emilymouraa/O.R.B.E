<?php
/*
 * Views/auth/reset_password.php
 * Tela de redefinição de senha — primeiro acesso.
 */
$pageTitle = 'Redefinir Senha';
$bodyClass = 'auth-page';
require __DIR__ . '/../layout/header.php';
?>

<div class="login-container">
    <div class="login-box">
        <div class="logo-container">
            <img src="/assets/images/orbe_logo.png" alt="ORBE Logo" class="logo">
        </div>

        <h2>Redefinir Senha</h2>
        <p class="auth-subtitle">
            Defina uma nova senha segura para acessar o sistema.
        </p>

        <br>

        <form method="POST" action="/redefinir-senha">

            <div class="form-group">
                <label for="password">Nova senha</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required style="padding-right: 2.5rem;">
                    <button type="button" onclick="toggleSenha()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); font-size: 1rem;">
                        <i class="fas fa-eye" id="iconeSenha"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar senha</label>
                <div style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" required style="padding-right: 2.5rem;">
                    <button type="button" onclick="toggleConfirmSenha()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); font-size: 1rem;">
                        <i class="fas fa-eye" id="iconeConfirmSenha"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Atualizar senha
            </button>

        </form>

        <p class="register-link">
            <a href="/login">← Voltar ao login</a>
        </p>
    </div>
</div>

<button class="theme-toggle" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>

<?php require __DIR__ . '/../layout/footer.php'; ?>