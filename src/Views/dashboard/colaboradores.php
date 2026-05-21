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

        <div class="filter-group">
            <select id="filtro-status">
                <option value="">Todos os status</option>
                <option value="Ativo">Ativo</option>
                <option value="Afastado">Afastado</option>
                <option value="Aposentado">Aposentado</option>
            </select>
        </div>

        <div class="filter-group">
            <select id="filtro-cargo">
                <option value="">Todos os cargos</option>
                <option value="1a_Classe">1ª Classe</option>
                <option value="2a_Classe">2ª Classe</option>
                <option value="3a_Classe">3ª Classe</option>
                <option value="Classe_Especial">Classe Especial</option>
                <option value="Chefe_Divisao">Chefe de Divisão</option>
            </select>
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
                    <?php if (($_SESSION['user']['role'] ?? '') === 'gestor'): ?>
                    <th>Ações</th>
                    <?php endif; ?>
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

<?php require __DIR__ . '/../layout/modal-edicao-colaborador.php'; ?>
<?php require __DIR__ . '/../layout/partials/modal-perfil-servidor.php'; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
<script>
(function () {
    const tbody    = document.getElementById('tabela-body');
    const contador = document.getElementById('contador');
    const search   = document.getElementById('filtro-search');
    const statusFilter = document.getElementById('filtro-status');
    const cargoFilter  = document.getElementById('filtro-cargo');
    const isGestor = '<?= $_SESSION['user']['role'] ?>' === 'gestor';
    const situacaoBadge = {
        'Ativo'     : 'badge-ativo',
        'Afastado'  : 'badge-afastado',
        'Aposentado': 'badge-aposentado',
    };

    // ── StateManager — salva e restaura filtros ───────────────
    function salvarEstado() {
        StateManager.save('/colaboradores', {
            search:  search.value.trim(),
            status:  statusFilter.value,
            cargo:   cargoFilter.value,
        });
    }

    function restaurarEstado() {
        const est = StateManager.load('/colaboradores');
        if (!est) return;
        if (est.search) search.value          = est.search;
        if (est.status) statusFilter.value    = est.status;
        if (est.cargo)  cargoFilter.value     = est.cargo;
    }

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
                ${isGestor ? `
                <td>
                    <button class="btn-acao btn-acao--editar" title="Editar colaborador"
                        onclick='editarColaborador(${JSON.stringify(c)})'>
                        Editar
                    </button>
                </td>
                ` : ''}
            </tr>
        `).join('');
    }

    window.editarColaborador = function(colaborador) {
        editarUsuario(colaborador.id, true);
    };

    window.recarregarColaboradores = function() {
        load(search.value.trim());
    };

    async function load(searchVal = '') {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="table-feedback">
                    <i class="fas fa-spinner fa-spin"></i> Carregando...
                </td>
            </tr>`;
        try {
            const params = new URLSearchParams();
            if (searchVal)          params.set('search',   searchVal);
            if (statusFilter.value) params.set('situacao', statusFilter.value);
            if (cargoFilter.value)  params.set('cargo',    cargoFilter.value);

            const res = await fetch(`/api/colaboradores?${params}`);
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

    // ── Listeners com save ────────────────────────────────────
    let debounceTimer;
    search.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            salvarEstado();
            load(search.value.trim());
        }, 350);
    });

    statusFilter?.addEventListener('change', () => {
        salvarEstado();
        load(search.value.trim());
    });

    cargoFilter?.addEventListener('change', () => {
        salvarEstado();
        load(search.value.trim());
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

    // ── Init: restaura estado e carrega ───────────────────────
    restaurarEstado();
    load(search.value.trim());
})();
</script>