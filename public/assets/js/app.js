/*
 * app.js
 * Responsável por:
 *  - Alternar tema claro/escuro (toggleTheme)
 *  - Consumir a API /api/users e renderizar a tabela de usuários
 *  - Gerenciar paginação e filtros (busca, perfil, status, unidade)
 *  - Popular o select de unidades via /api/unidades
 *  - Gerenciar o modal de cadastro de novos usuários
 *  - Enviar o formulário de cadastro via fetch AJAX para /register
 */

// ── Base URL dinâmica ─────────────────────────────────────────────
const BASE_URL = window.location.pathname
    .replace(/\/(login|register|dashboard|api).*$/, '')
    .replace(/\/$/, '');

// ── Tema ─────────────────────────────────────────────────────────
function toggleTheme() {
    const html = document.documentElement;
    const next = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);

    // Atualiza ícone do botão fixo
    const btn = document.getElementById('btnTema');
    if (btn) btn.textContent = next === 'dark' ? '☀️' : '🌙';
}

function toggleSenha() {
    const input  = document.getElementById('password');
    const icone  = document.getElementById('iconeSenha');
    if (input.type === 'password') {
        input.type   = 'text';
        icone.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type   = 'password';
        icone.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        const btn = document.getElementById('btnTema');
        if (btn) btn.textContent = saved === 'dark' ? '☀️' : '🌙';
    }
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

        if (res.status === 401) { window.location.href = `${BASE_URL}/login`; return; }
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

// ── Popula selects de unidade (filtro + modal) ────────────────────
async function carregarUnidades() {
    try {
        const res = await fetch(`${BASE_URL}/api/unidades`);
        if (!res.ok) return;

        const json = await res.json();

        // Popula tanto o filtro da tabela quanto o select do modal
        [selUnidade, document.getElementById('m-unidade')].forEach(sel => {
            if (!sel) return;
            // Remove opções antigas (exceto a primeira placeholder)
            while (sel.options.length > 1) sel.remove(1);

            json.data.forEach(u => {
                const opt = document.createElement('option');
                opt.value       = u.id;
                opt.textContent = u.nome;
                sel.appendChild(opt);
            });
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
if (inputSearch) {
    inputSearch.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            state.search = inputSearch.value.trim();
            state.page   = 1;
            carregarUsuarios();
        }, 400);
    });
}
if (selRole) {
    selRole.addEventListener('change', () => { state.role = selRole.value; state.page = 1; carregarUsuarios(); });
}
if (selAtivo) {
    selAtivo.addEventListener('change', () => { state.ativo = selAtivo.value; state.page = 1; carregarUsuarios(); });
}
if (selUnidade) {
    selUnidade.addEventListener('change', () => { state.unidade_id = selUnidade.value; state.page = 1; carregarUsuarios(); });
}

// ── Placeholders de ação ──────────────────────────────────────────
function editarUsuario(id)  { console.log('Editar usuário:', id); }
function excluirUsuario(id) { console.log('Excluir usuário:', id); }

// ─────────────────────────────────────────────────────────────────
// ── MODAL DE CADASTRO ─────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────

const modal          = document.getElementById('modalCadastro');
const formUsuario    = document.getElementById('formUsuario');
const feedbackEl     = document.getElementById('feedback');
const inputCpf       = document.getElementById('m-cpf');
const inputDataCad   = document.getElementById('m-data-cadastro');
const btnSalvar      = document.getElementById('btnSalvar');

/** Abre o modal e preenche a data de cadastro com a data atual */
function openModal() {
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Atualiza título com próximo RA estimado
    const totalAtual = parseInt(contador?.textContent?.match(/\d+/)?.[0] ?? 0);
    const proximoRA  = String(totalAtual + 1).padStart(5, '0');
    const titulo     = document.getElementById('modalTitulo');
    if (titulo) titulo.textContent = `Cadastro Servidor Público PRF`;

    const hoje = new Date();
    const dd   = String(hoje.getDate()).padStart(2, '0');
    const mm   = String(hoje.getMonth() + 1).padStart(2, '0');
    const yyyy = hoje.getFullYear();
    if (inputDataCad) inputDataCad.value = `${dd}/${mm}/${yyyy}`;

    setFeedback('', '');
}

const selCargo = document.getElementById('m-cargo');
const selPerfil = document.getElementById('m-role');

if (selCargo && selPerfil) {
    selCargo.addEventListener('change', function () {
        const mapa = {
            '3a_classe':     'user',
            '2a_classe':     'user',
            '1a_classe':     'user',
            'classe_especial': 'user',
            'chefe_divisao': 'gestor',
            'diretor_geral': 'admin',
        };
        const perfil = mapa[this.value];
        if (perfil) selPerfil.value = perfil;
    });
}

/** Fecha o modal, limpa o form e remove o feedback */
function closeModal() {
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
    if (formUsuario) formUsuario.reset();
    setFeedback('', '');
}

/** Exibe feedback visual no modal */
function setFeedback(msg, tipo) {
    if (!feedbackEl) return;
    feedbackEl.textContent  = msg;
    feedbackEl.className    = 'feedback-msg' + (tipo ? ` ${tipo}` : '');
    if (msg) feedbackEl.style.display = 'block';
    else     feedbackEl.style.display = 'none';
}

/** Máscara de CPF em tempo real */
if (inputCpf) {
    inputCpf.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '').slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        this.value = v;
    });
}

/** Fecha o modal ao clicar no backdrop */
if (modal) {
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
}

/** Fecha o modal com ESC */
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal?.classList.contains('open')) closeModal();
});

/** Submit do formulário via fetch AJAX */
if (formUsuario) {
    formUsuario.addEventListener('submit', async function (e) {
        e.preventDefault();

        // ── Validação client-side ─────────────────────────────────
        const obrigatorios = ['m-nome', 'm-email', 'm-cpf', 'm-cargo', 'm-role', 'm-unidade', 'm-status'];
        let valido = true;

        obrigatorios.forEach(id => {
            const campo = document.getElementById(id);
            if (!campo || !campo.value.trim()) valido = false;
        });

        if (inputCpf && inputCpf.value.length < 14) valido = false;

        if (!valido) {
            setFeedback('Preencha todos os campos obrigatórios corretamente.', 'msg-error');
            return;
        }

        // ── Monta o payload ───────────────────────────────────────
        const payload = new FormData(formUsuario);
        payload.set('role', selPerfil.value);  // role vem do select disabled

        // Remove máscara do CPF antes de enviar
        const cpfLimpo = document.getElementById('m-cpf').value.replace(/\D/g, '');
        payload.set('cpf', cpfLimpo);

        // Garante que as datas vão no formato correto
        const dataNasc = document.getElementById('m-data-nascimento').value;
        const dataAdm  = document.getElementById('m-data-admissao').value;
        if (dataNasc) payload.set('data_nascimento', dataNasc);
        if (dataAdm)  payload.set('data_admissao', dataAdm);

        // ── Bloqueia o botão durante o envio ──────────────────────
        if (btnSalvar) {
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        }

        try {
            const res = await fetch(`${BASE_URL}/register`, {
                method:  'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body:    payload,
            });

            const json = await res.json();

            if (json.error) {
                setFeedback(json.error, 'msg-error');
                return;
            }

            // Sucesso: exibe RA e senha gerados, fecha o modal e recarrega a tabela
            const msg = json.mensagem
                ?? `Usuário criado! RA: ${json.ra ?? '—'} | Senha padrão: ${json.senha ?? '—'}`;

            setFeedback(msg, 'msg-success');

            setTimeout(() => {
                closeModal();
                carregarUsuarios(); // Atualiza a tabela automaticamente
            }, 2500);

        } catch (err) {
            console.error(err);
            setFeedback('Erro de comunicação com o servidor. Tente novamente.', 'msg-error');
        } finally {
            if (btnSalvar) {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = '<i class="fas fa-save"></i> Salvar Usuário';
            }
        }
    });
}

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (tbody) {
        carregarUnidades();
        carregarUsuarios();
    }
});