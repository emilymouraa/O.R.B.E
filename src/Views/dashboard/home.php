<!DOCTYPE html>
<html lang="pt-br">
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

        /* Header */
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .title-group h1 { font-size: 1.5rem; color: var(--text); }
        .title-group p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        .counter { font-weight: 600; color: var(--primary); margin-top: 8px; display: block; }
        .btn-new { background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: opacity 0.2s; }
        .btn-new:hover { opacity: 0.9; }

        /* Filtros */
        .filters-container { background: var(--white); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .filter-group input, .filter-group select { width: 100%; padding: 0.6rem; border: 1px solid var(--border); border-radius: 4px; outline: none; }

        /* Tabela */
        .table-wrapper { background: var(--white); border-radius: 8px; border: 1px solid var(--border); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
        th { background: #f1f5f9; padding: 1rem; font-size: 0.85rem; text-transform: uppercase; color: #64748b; }
        td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; }

        /* Estado vazio e loading */
        .table-feedback { text-align: center; padding: 3rem 1rem; color: #64748b; font-size: 0.95rem; }
        .table-feedback i { font-size: 2rem; margin-bottom: 0.75rem; display: block; color: #cbd5e1; }

        /* Badges e Pills */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-admin  { background: #dbeafe; color: #1e40af; }
        .badge-gestor { background: #fef3c7; color: #92400e; }
        .badge-user   { background: #f1f5f9; color: #475569; }
        .pill { padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 500; }
        .pill-active   { background: #dcfce7; color: #166534; }
        .pill-inactive { background: #fee2e2; color: #991b1b; }

        /* Ações */
        .actions { display: flex; gap: 12px; }
        .btn-icon { border: none; background: none; cursor: pointer; font-size: 1.1rem; transition: transform 0.1s; }
        .btn-edit   { color: var(--primary); }
        .btn-delete { color: var(--danger); }
        .btn-icon:hover { transform: scale(1.1); }

        /* Paginação */
        .pagination { display: flex; justify-content: flex-end; padding: 1rem; gap: 5px; }
        .page-link { padding: 6px 12px; border: 1px solid var(--border); border-radius: 4px; background: white; cursor: pointer; color: var(--text); font-size: 0.85rem; }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }
        .page-link:disabled { opacity: 0.4; cursor: not-allowed; }

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
            <span class="counter" id="contador">Carregando...</span>
        </div>
        <button class="btn-new">
            <i class="fas fa-plus"></i> Novo Usuário
        </button>
    </header>

    <section class="filters-container">
        <div class="filter-group">
            <input type="text" id="filtro-search" placeholder="Buscar por nome ou e-mail...">
        </div>
        <div class="filter-group">
            <select id="filtro-role">
                <option value="">Todos os Perfis</option>
                <option value="admin">Administrador</option>
                <option value="gestor">Gestor</option>
                <option value="user">Usuário</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="filtro-ativo">
                <option value="">Todos os Status</option>
                <option value="true">Ativo</option>
                <option value="false">Inativo</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="filtro-unidade">
                <option value="">Todas as Unidades</option>
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
            <tbody id="tabela-body">
                <tr>
                    <td colspan="7" class="table-feedback">
                        <i class="fas fa-spinner fa-spin"></i>
                        Carregando usuários...
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="pagination" id="paginacao"></div>
    </div>

    <script src="/assets/js/app.js"></script>

</body>
</html>