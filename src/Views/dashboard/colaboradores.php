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
                    <button class="btn-edit"
                        onclick='editarColaborador(${JSON.stringify(c)})'>
                        Editar
                    </button>
                </td>
                ` : ''}
            </tr>
        `).join('');
    }

    window.editarColaborador = async function(colaborador) {

        const nome = prompt('Nome:', colaborador.nome);
        if (nome === null) return;

        const email = prompt('E-mail:', colaborador.email);
        if (email === null) return;

        const cargo = prompt('Cargo:', colaborador.cargo);
        if (cargo === null) return;

        const situacao = prompt('Situação:', colaborador.situacao);
        if (situacao === null) return;

        const res = await fetch(`/api/gestor/colaboradores/${colaborador.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                nome,
                email,
                cargo,
                situacao
            })
        });

        const json = await res.json();

        if (!res.ok) {
            alert(json.error || 'Erro ao atualizar');
            return;
        }

        alert('Atualizado com sucesso!');
        load(search.value.trim());
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

            if (searchVal) {
                params.set('search', searchVal);
            }

            if (statusFilter.value) {
                params.set('situacao', statusFilter.value);
            }

            if (cargoFilter.value) {
                params.set('cargo', cargoFilter.value);
            }

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

    statusFilter?.addEventListener('change', () => {
        load(search.value.trim());
    });

    cargoFilter?.addEventListener('change', () => {
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

    load();

})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>