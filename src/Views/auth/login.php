    <!--
        View responsável pela tela de login do sistema ORBE.
        Exibe o formulário de autenticação, mostra mensagens de erro quando necessário
        e inclui o botão de alternância de tema (light/dark).
    -->

    <!DOCTYPE html>
    <html lang="pt-br" data-theme="light">

    <head>
        <meta charset="UTF-8">
        <title>Login - ORBE</title>

        <link rel="stylesheet" href="/assets/css/style.css">
    </head>

    <body>

    <div class="login-container">

        <div class="login-box">

            <div class="logo-container">
                <img src="/assets/images/orbe_logo.jpeg" alt="ORBE Logo" class="logo">
            </div>

            <h2>Login</h2>

            <?php if (!empty($error)): ?>
                <div class="error">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login">

                <div class="form-group">
                    <label>RA</label>
                    <input 
                        type="text" 
                        name="ra" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <input 
                        type="password" 
                        name="password" 
                        required
                    >
                </div>

                <button type="submit" class="btn-primary">
                    Entrar
                </button>

            </form>

            <p class="register-link">
                Não tem conta?
                <a href="/register">Criar conta</a>
            </p>

        </div>

    </div>

    <button class="theme-toggle" onclick="toggleTheme()">
        🌙
    </button>

    <script src="/assets/js/app.js"></script>

    </body>
    </html>