<?php session_start(); ?>
<!DOCTYPE html>
    <html lang="pt-br">
    <head>
    <meta charset="UTF-8">
    <title>Dashboard - ORBE</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            background:#f4f6f9;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }

        .card{
            background:white;
            padding:40px;
            border-radius:10px;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
            text-align:center;
        }

        button{
            margin-top:20px;
            padding:10px 20px;
            border:none;
            background:#4CAF50;
            color:white;
            border-radius:5px;
            cursor:pointer;
        }

        button:hover{
            background:#43a047;
        }
    </style>
    </head>

    <body>
        <div class="card">
        <h2>Bem vindo ao ORBE</h2>
        <p>Login realizado com sucesso.</p>

        <?php if($_SESSION['user']['role'] === 'admin'): ?>

        <a href="/register">
            <button>Cadastrar Usuário</button>
        </a>

        <?php endif; ?>
        <form method="POST" action="/logout">
        <button type="submit">Sair</button>
        </form>
        </div>
    </body>
</html>