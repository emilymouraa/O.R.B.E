<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>
<html 
lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --bg: #f8fafc;
            --text: #1e293b;
            --white: #ffffff;
            --border: #e2e8f0;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg); color: var(--text); padding: 2rem; }

        /* Header & Info */
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .title-group h1 { font-size: 1.5rem; color: var(--text); }
        .title-group p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        .counter { font-weight: 600; color: var(--primary); margin-top: 8px; display: block; }

        .btn-new { background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: opacity 0.2s; }
        .btn-new:hover { opacity: 0.9; }

        /* Filtros */
        .filters-container { background: var(--white); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .filter-group input, .filter-group select { width: 100%; padding: 0.6rem; border: 1px solid var(--border); border-radius: 4px; outline: none; }

        /* Tabela Responsiva */
        .table-wrapper { background: var(--white); border-radius: 8px; border: 1px solid var(--border); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
        th { background: #f1f5f9; padding: 1rem; font-size: 0.85rem; text-transform: uppercase; color: #64748b; }
        td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; }

        /* Badges e Pills */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-admin { background: #dbeafe; color: #1e40af; }
        .badge-gestor { background: #fef3c7; color: #92400e; }
        .badge-user { background: #f1f5f9; color: #475569; }

        .pill { padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 500; }
        .pill-active { background: #dcfce7; color: #166534; }
        .pill-inactive { background: #fee2e2; color: #991b1b; }

        /* Ações */
        .actions { display: flex; gap: 12px; }
        .btn-icon { border: none; background: none; cursor: pointer; font-size: 1.1rem; transition: transform 0.1s; }
        .btn-edit { color: var(--primary); }
        .btn-delete { color: var(--danger); }
        .btn-icon:hover { transform: scale(1.1); }

        /* Paginação */
        .pagination { display: flex; justify-content: flex-end; padding: 1rem; gap: 5px; }
        .page-link { padding: 6px 12px; border: 1px solid var(--border); border-radius: 4px; background: white; cursor: pointer; text-decoration: none; color: var(--text); font-size: 0.85rem; }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }

        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header-section { flex-direction: column; }
            .btn-new { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <header class="header-section">
        <div class="title-group">
            <h1>Gestão de Usuários</h1>
            <p>Cadastre e gerencie os usuários do sistema</p>
            <span class="counter">Total de 42 usuários no sistema</span>
        </div>
        <?php if($_SESSION['user']['role'] === 'admin'): ?>

        <a href="/register">
            <button class="btn-new">
                <i class="fas fa-plus"></i> Novo Usuário
            </button>
        </a>

        <?php endif; ?>
    </header>

    <section class="filters-container">
        <div class="filter-group">
            <input type="text" placeholder="Buscar por nome ou e-mail...">
        </div>
        <div class="filter-group">
            <select>
                <option value="">Todos os Perfis</option>
                <option>Administrador</option>
                <option>Gestor</option>
                <option>Usuário</option>
            </select>
        </div>
        <div class="filter-group">
            <select>
                <option value="">Status</option>
                <option>Ativo</option>
                <option>Inativo</option>
            </select>
        </div>
        <div class="filter-group">
            <select>
                <option value="">Todas as Unidades</option>
                <option>Matriz</option>
                <option>Filial SP</option>
                <option>Filial RJ</option>
            </select>
        </div>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Unidade</th>
                    <th>Status</th>
                    <th>Data Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Ana Silva</strong></td>
                    <td>ana.silva@empresa.com</td>
                    <td><span class="badge badge-admin">Administrador</span></td>
                    <td>Matriz</td>
                    <td><span class="pill pill-active">Ativo</span></td>
                    <td>10/02/2026</td>
                    <td class="actions">
                        <button class="btn-icon btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <button class="btn-icon btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Carlos Souza</strong></td>
                    <td>carlos.s@empresa.com</td>
                    <td><span class="badge badge-gestor">Gestor</span></td>
                    <td>Filial SP</td>
                    <td><span class="pill pill-active">Ativo</span></td>
                    <td>15/01/2026</td>
                    <td class="actions">
                        <button class="btn-icon btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <button class="btn-icon btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Mariana Luz</strong></td>
                    <td>mariana.luz@empresa.com</td>
                    <td><span class="badge badge-user">Usuário</span></td>
                    <td>Filial RJ</td>
                    <td><span class="pill pill-inactive">Inativo</span></td>
                    <td>05/03/2026</td>
                    <td class="actions">
                        <button class="btn-icon btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <button class="btn-icon btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="pagination">
            <button class="page-link"><i class="fas fa-chevron-left"></i></button>
            <button class="page-link active">1</button>
            <button class="page-link">2</button>
            <button class="page-link">3</button>
            <button class="page-link"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

</body>
    </head>

    <body>
        <div class="card">
        <h2>Bem vindo ao ORBE</h2>
        <p>Login realizado com sucesso.</p>
        <form method="POST" action="/logout">
        <button type="submit">Sair</button>
        </form>
        </div>
    </body>
</html>