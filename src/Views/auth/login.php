<?php
/*
 * Views/auth/login.php
 * Tela de autenticação do sistema ORBE.
 */
$pageTitle = 'Login';
$bodyClass = 'auth-page';
require __DIR__ . '/../layout/header.php';
?>

<div class="login-container">
    <div class="login-box">
        <div class="logo-container">
            <img src="/assets/images/orbe_logo.jpeg" alt="ORBE Logo" class="logo">
        </div>
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <div class="form-group">
                <label for="ra">RA</label>
                <input type="text" id="ra" name="ra" required>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required style="padding-right: 2.5rem;">
                    <button type="button" onclick="toggleSenha()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); font-size: 1rem;">
                        <i class="fas fa-eye" id="iconeSenha"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary">Entrar</button>
        </form>
    </div>
</div>

<button class="theme-toggle" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>

<?php require __DIR__ . '/../layout/footer.php'; ?>