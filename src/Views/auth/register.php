<!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Cadastro - ORBE</title>
        <style>
            body{
                font-family: Arial;
                background: linear-gradient(135deg,#667eea,#764ba2);
                height:100vh;
                display:flex;
                justify-content:center;
                align-items:center;
            }
            .container{
                background:white;
                padding:40px;
                border-radius:10px;
                width:320px;
                box-shadow:0 8px 20px rgba(0,0,0,0.2);
            }
            h2{
                text-align:center;
                margin-bottom:20px;
            }
            input{
                width:100%;
                padding:10px;
                margin-bottom:15px;
                border-radius:5px;
                border:1px solid #ccc;
            }
            button{
                width:100%;
                padding:10px;
                background:#667eea;
                border:none;
                color:white;
                font-weight:bold;
                border-radius:5px;
                cursor:pointer;
            }
            button:hover{
                background:#5a67d8;
            }
            .error{
                color:red;
                margin-bottom:10px;
                text-align:center;
            }
            .link{
                text-align:center;
                margin-top:15px;
            }
            .link a{
                color:#667eea;
                text-decoration:none;
                font-size:14px;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h2>Criar Conta</h2>
            <?php if(isset($error)): ?>
            <div class="error"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST" action="/register">
                <input type="text" name="ra" placeholder="RA" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Senha" required>
                <button type="submit">Cadastrar</button>
            </form>
            <div class="link">
                <a href="/login">Já possui conta? Fazer login</a>
            </div>
        </div>
    </body>
</html>