<!--
    View responsável pela tela de cadastro do sistema ORBE.
    Exibe o formulário para criação de conta, mostra mensagens de erro
    quando o cadastro falha e permite alternar entre tema claro e escuro.
-->

<!DOCTYPE html>
<html lang="pt-br" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Cadastro - ORBE</title>

    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

<div class="login-container">

    <div class="login-box">

        <div class="logo-container">
            <img src="/assets/images/orbe_logo.jpeg" alt="ORBE Logo" class="logo">
        </div>

        <h2>Criar Conta</h2>

        <?php if (!empty($error)): ?>
            <div class="error">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register">

            <div class="form-group">
                <label>RA</label>
                <input 
                    type="text"
                    name="ra"
                    required
                >
            </div>

            <div class="form-group">
                <label>Nome</label>
                <input 
                    type="text" 
                    name="name" 
                    required
                >
            </div>

            <div class="form-group">
                <label>Email</label>
                <input 
                    type="email" 
                    name="email" 
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
                Criar conta
            </button>

        </form>

        <p class="register-link">
            Já possui conta?
            <a href="/login">Fazer login</a>
        </p>

    </div>

</div>

<button class="theme-toggle" onclick="toggleTheme()">
    🌙
</button>

<script src="/assets/js/app.js"></script>

</body>
</html>