<?php
/*Tela de colaboradores da unidade — visão do usuário (role: user).
 * Somente leitura. Sem dados sensíveis. Sem ações administrativas.*/
$pageTitle = 'Minha Equipe · ORBE';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<div class="main-content">

    <header class="header-section">
        <div class="title-group">
            <h1>Minha Equipe</h1>
            <p>Abaixo, estão os colaboradores de sua unidade</p>
            <span class="counter" id="contador">Carregando...</span>
        </div>
    </header>

    <section class="filters-container">
        <div class="filter-group">
            <input
                type="text"
                id="filtro-search"
                placeholder="Buscar por nome..."
                autocomplete="off"
            >
        </div>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Cargo</th>
                    <th>Unidade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="tabela-body">
                <tr>
                    <td colspan="5" class="table-feedback">
                        <i class="fas fa-spinner fa-spin"></i>
                        Carregando colaboradores...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <button class="btn-theme-fixed" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>

</div>

<script>
(function () {
    const tbody    = document.getElementById('tabela-body');
    const contador = document.getElementById('contador');
    const search   = document.getElementById('filtro-search');

    const situacaoBadge = {
        'Ativo'     : 'badge-ativo',
        'Afastado'  : 'badge-afastado',
        'Aposentado': 'badge-aposentado',
    };

    function render(data, total) {
        contador.textContent = `${total} colaborador${total !== 1 ? 'es' : ''} na sua unidade`;

        if (!data.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="table-feedback">
                        <i class="fas fa-users-slash"></i>
                        Nenhum colaborador encontrado.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = data.map(c => `
            <tr>
                <td>
                    <div class="user-name-cell">
                        <div class="avatar-initials">
                            ${initialsOf(c.nome)}
                        </div>
                        <span>${escHtml(c.nome)}</span>
                    </div>
                </td>
                <td>${escHtml(c.email)}</td>
                <td>${escHtml(c.cargo)}</td>
                <td>
                    <span title="${escHtml(c.unidade)}">
                        ${escHtml(c.unidade_sigla)}
                    </span>
                </td>
                <td>
                    <span class="badge ${situacaoBadge[c.situacao] ?? 'badge-outros'}">
                        ${escHtml(c.situacao)}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    async function load(searchVal = '') {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="table-feedback">
                    <i class="fas fa-spinner fa-spin"></i> Carregando...
                </td>
            </tr>`;

        try {
            const params = new URLSearchParams();
            if (searchVal) params.set('search', searchVal);

            const res  = await fetch(`/api/colaboradores?${params}`);

            if (res.status === 403) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="table-feedback">
                            <i class="fas fa-lock"></i> Acesso não autorizado.
                        </td>
                    </tr>`;
                return;
            }

            const json = await res.json();
            render(json.data ?? [], json.total ?? 0);

        } catch {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="table-feedback">
                        <i class="fas fa-circle-exclamation"></i> Erro ao carregar dados.
                    </td>
                </tr>`;
        }
    }

    let debounceTimer;
    search.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => load(search.value.trim()), 350);
    });

    function initialsOf(nome) {
        const parts = nome.trim().split(' ').filter(Boolean);
        if (parts.length === 1) return parts[0][0].toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    load();

})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>