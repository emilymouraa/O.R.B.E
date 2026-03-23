/*
 * app.js
 * Responsável por:
 *  - Alternar tema claro/escuro (toggleTheme)
 *  - Consumir a API /api/users e renderizar a tabela de usuários
 *  - Gerenciar paginação e filtros (busca, perfil, status, unidade)
 *  - Popular o select de unidades via /api/unidades
 */

// ── Base URL dinâmica ─────────────────────────────────────────────
// Detecta o caminho base automaticamente, independente de onde
// o projeto está hospedado (localhost/subpasta ou domínio próprio)
const BASE_URL = window.location.pathname
    .replace(/\/dashboard.*$/, '')
    .replace(/\/$/, '');

// ── Tema ─────────────────────────────────────────────────────────
function toggleTheme() {
    const html  = document.documentElement;
    const next  = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
}

document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('theme');
    if (saved) document.documentElement.setAttribute('data-theme', saved);
});

// ── Estado da tabela ─────────────────────────────────────────────
const state = {
    page:       1,
    limit:      10,
    search:     '',
    role:       '',
    ativo:      '',
    unidade_id: ''
};

// ── Elementos do DOM ─────────────────────────────────────────────
const tbody       = document.getElementById('tabela-body');
const paginacao   = document.getElementById('paginacao');
const contador    = document.getElementById('contador');
const inputSearch = document.getElementById('filtro-search');
const selRole     = document.getElementById('filtro-role');
const selAtivo    = document.getElementById('filtro-ativo');
const selUnidade  = document.getElementById('filtro-unidade');

// ── Helpers de badge e pill ───────────────────────────────────────
function badgePerfil(perfilRaw, perfilLabel) {
    const map = { admin: 'badge-admin', gestor: 'badge-gestor', user: 'badge-user' };
    const cls = map[perfilRaw] ?? 'badge-user';
    return `<span class="badge ${cls}">${perfilLabel}</span>`;
}

function pillStatus(statusRaw) {
    return statusRaw
        ? `<span class="pill pill-active">Ativo</span>`
        : `<span class="pill pill-inactive">Inativo</span>`;
}

// ── Renderiza linhas da tabela ────────────────────────────────────
function renderTabela(users) {
    if (!users.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="table-feedback">
                    <i class="fas fa-users-slash"></i>
                    Nenhum usuário encontrado.
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = users.map(u => `
        <tr>
            <td><strong>${u.nome}</strong></td>
            <td>${u.email}</td>
            <td>${badgePerfil(u.perfil_raw, u.perfil)}</td>
            <td>${u.unidade ?? '—'}</td>
            <td>${pillStatus(u.status_raw)}</td>
            <td>${u.data_cadastro}</td>
            <td class="actions">
                <button class="btn-icon btn-edit"   title="Editar"  onclick="editarUsuario(${u.id})">
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <button class="btn-icon btn-delete" title="Excluir" onclick="excluirUsuario(${u.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// ── Renderiza paginação ───────────────────────────────────────────
function renderPaginacao(page, totalPages) {
    if (totalPages <= 1) { paginacao.innerHTML = ''; return; }

    let html = `
        <button class="page-link" onclick="irParaPagina(${page - 1})" ${page === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>`;

    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="page-link ${i === page ? 'active' : ''}" onclick="irParaPagina(${i})">${i}</button>`;
    }

    html += `
        <button class="page-link" onclick="irParaPagina(${page + 1})" ${page === totalPages ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>`;

    paginacao.innerHTML = html;
}

// ── Busca usuários na API ─────────────────────────────────────────
async function carregarUsuarios() {
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="table-feedback">
                <i class="fas fa-spinner fa-spin"></i>
                Carregando usuários...
            </td>
        </tr>`;

    const params = new URLSearchParams({
        page:  state.page,
        limit: state.limit,
        ...(state.search     && { search:     state.search }),
        ...(state.role       && { role:       state.role }),
        ...(state.ativo      && { ativo:      state.ativo }),
        ...(state.unidade_id && { unidade_id: state.unidade_id }),
    });

    try {
        const res = await fetch(`${BASE_URL}/api/users?${params}`);

        if (res.status === 401) {
            window.location.href = `${BASE_URL}/login`;
            return;
        }
        if (res.status === 403) {
            tbody.innerHTML = `<tr><td colspan="7" class="table-feedback"><i class="fas fa-lock"></i> Acesso negado.</td></tr>`;
            return;
        }
        if (!res.ok) throw new Error('Erro na resposta da API');

        const json = await res.json();

        contador.textContent = json.mensagem;
        renderTabela(json.data);
        renderPaginacao(json.page, json.totalPages);

    } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="7" class="table-feedback"><i class="fas fa-circle-exclamation"></i> Erro ao carregar usuários. Tente novamente.</td></tr>`;
    }
}

// ── Popula select de unidades ─────────────────────────────────────
async function carregarUnidades() {
    try {
        const res = await fetch(`${BASE_URL}/api/unidades`);
        if (!res.ok) return;
        const json = await res.json();

        json.data.forEach(u => {
            const opt = document.createElement('option');
            opt.value       = u.id;
            opt.textContent = u.nome;
            selUnidade.appendChild(opt);
        });
    } catch (err) {
        console.error('Erro ao carregar unidades:', err);
    }
}

// ── Navegação de página ───────────────────────────────────────────
function irParaPagina(page) {
    state.page = page;
    carregarUsuarios();
}

// ── Filtros com debounce no search ────────────────────────────────
let debounceTimer;
inputSearch.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        state.search = inputSearch.value.trim();
        state.page   = 1;
        carregarUsuarios();
    }, 400);
});

selRole.addEventListener('change', () => {
    state.role = selRole.value;
    state.page = 1;
    carregarUsuarios();
});

selAtivo.addEventListener('change', () => {
    state.ativo = selAtivo.value;
    state.page  = 1;
    carregarUsuarios();
});

selUnidade.addEventListener('change', () => {
    state.unidade_id = selUnidade.value;
    state.page       = 1;
    carregarUsuarios();
});

// ── Placeholders de ação (implementar nas próximas sprints) ───────
function editarUsuario(id) {
    console.log('Editar usuário:', id);
}

function excluirUsuario(id) {
    console.log('Excluir usuário:', id);
}

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    carregarUnidades();
    carregarUsuarios();
});