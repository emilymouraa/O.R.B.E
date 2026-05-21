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
    .replace(/\/(login|register|dashboard|usuarios|perfil|colaboradores|organograma|competencias|banco-talentos|painel|api).*$/, '')
    .replace(/\/$/, '');

    const StateManager = {
    _key(rota) {
        return `orbe_state_${rota}`;
    },
    save(rota, dados) {
        try {
            sessionStorage.setItem(this._key(rota), JSON.stringify(dados));
        } catch (e) {
            console.warn('StateManager.save falhou:', e);
        }
    },
    load(rota) {
        try {
            const raw = sessionStorage.getItem(this._key(rota));
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    },
    clear(rota) {
        sessionStorage.removeItem(this._key(rota));
    },
    clearAll() {
        Object.keys(sessionStorage)
            .filter(k => k.startsWith('orbe_state_'))
            .forEach(k => sessionStorage.removeItem(k));
    }
};

// ── Tema ─────────────────────────────────────────────────────────
function toggleTheme() {
    const html = document.documentElement;
    const next = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';

    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);

    // Atualiza botão
    const btn = document.getElementById('btnTema');
    if (btn) btn.textContent = next === 'dark' ? '☀️' : '🌙';

    // Atualiza logo
    atualizarLogoTema(next);
}

function atualizarLogoTema(theme) {
    const logo = document.getElementById('logoTema');

    if (!logo) return;

    // Fade out
    logo.style.opacity = '0';

    setTimeout(() => {
        logo.src = theme === 'light'
            ? '/assets/images/orbe_logo3.png'
            : '/assets/images/orbe_logo4.png';

        logo.style.opacity = '1';
    }, 80);
}

document.addEventListener('DOMContentLoaded', () => {
    const btnLogout = document.querySelector('form[action="/logout"] button, button[onclick*="logout"], .btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', () => {
            StateManager.clearAll();
        });
    }
    const saved = localStorage.getItem('theme');
    atualizarLogoTema(saved || 'dark');
});

function toggleSenha() {
    const input = document.getElementById('password');
    const icon = document.getElementById('iconeSenha');

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

function toggleConfirmSenha() {
    const input = document.getElementById('confirm_password');
    const icon = document.getElementById('iconeConfirmSenha');

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        const btn = document.getElementById('btnTema');
        if (btn) btn.textContent = saved === 'dark' ? '☀️' : '🌙';
    }

    if (document.getElementById('tabela-body') && window.location.pathname === '/usuarios') {
        const estadoSalvo = StateManager.load('/usuarios');
        if (estadoSalvo) {
            state.search     = estadoSalvo.search     ?? '';
            state.cargo      = estadoSalvo.cargo      ?? '';
            state.situacao   = estadoSalvo.situacao   ?? '';
            state.unidade_id = estadoSalvo.unidade_id ?? '';
            state.page       = estadoSalvo.page       ?? 1;
            // Restaura valores nos inputs/selects
            const inp = document.getElementById('filtro-search');
            if (inp) inp.value = state.search;
            const sCargo = document.getElementById('filtro-cargo');
            if (sCargo) sCargo.value = state.cargo;
            const sAtivo = document.getElementById('filtro-ativo');
            if (sAtivo) sAtivo.value = state.situacao;
            const sUnidade = document.getElementById('filtro-unidade');
            if (sUnidade) sUnidade.value = state.unidade_id;
        }
        await carregarUnidades();
        await carregarUsuarios();
        if (estadoSalvo?.unidade_id) {
            const sUnidade = document.getElementById('filtro-unidade');
            if (sUnidade) sUnidade.value = estadoSalvo.unidade_id;
        }
        if (estadoSalvo?.modalCadastro) {
            _restaurarCamposModalCadastro(estadoSalvo.modalCadastro);
        }
    }
});

// ── Estado da tabela ─────────────────────────────────────────────
const state = {
    page:       1,
    limit:      10,
    search:     '',
    cargo:      '',
    situacao:   '',
    unidade_id: ''
};

// ── Elementos do DOM — só inicializa na página /usuarios ─────────
const _naTelaUsuarios = window.location.pathname === '/usuarios';
const tbody         = _naTelaUsuarios ? document.getElementById('tabela-body')   : null;
const paginacao     = _naTelaUsuarios ? document.getElementById('paginacao')     : null;
const contador      = _naTelaUsuarios ? document.getElementById('contador')      : null;
const inputSearch   = _naTelaUsuarios ? document.getElementById('filtro-search') : null;
const selCargoAdmin = _naTelaUsuarios ? document.getElementById('filtro-cargo')  : null;
const selAtivo      = _naTelaUsuarios ? document.getElementById('filtro-ativo')  : null;
const selUnidade    = _naTelaUsuarios ? document.getElementById('filtro-unidade'): null;

if (!tbody) {}

// ── Helpers de badge e pill ───────────────────────────────────────
function badgePerfil(perfilRaw, perfilLabel) {
    const map = { admin: 'badge-admin', gestor: 'badge-gestor', user: 'badge-user' };
    const cls = map[perfilRaw] ?? 'badge-user';
    return `<span class="badge ${cls}">${perfilLabel}</span>`;
}

function pillSituacao(situacao) {
    const map = {
        ativo:                { cls: 'pill-active',    label: 'Ativo' },
        afastado:             { cls: 'pill-afastado',  label: 'Afastado' },
        aposentado:           { cls: 'pill-aposentado',label: 'Aposentado' },
        licenca_maternidade:  { cls: 'pill-licenca',   label: 'Licença Maternidade' },
        desligado:            { cls: 'pill-desligado', label: 'Desligado' },
        outros:               { cls: 'pill-outros',    label: 'Outros' },
    };
    const s = map[situacao] ?? { cls: 'pill-outros', label: situacao };
    return `<span class="pill ${s.cls}">${s.label}</span>`;
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

    tbody.innerHTML = users.map(u => {
        const acoes = u.pode_editar
            ? `<button class="btn-acao btn-acao--editar" title="Editar" 
                onclick="editarUsuario(${u.id})">Editar</button>`
            : '—';

        return `
            <tr>
                <td><span class="link-perfil-servidor" style="cursor:pointer;font-weight:600;"
                    onclick="abrirPerfilServidor(${u.servidor_id})">${u.nome}</span></td>
                <td>${u.email}</td>
                <td>${badgePerfil(u.perfil_raw, u.perfil)}</td>
                <td>${u.unidade ?? '—'}</td>
                <td>${pillSituacao(u.situacao)}</td>
                <td>${u.data_cadastro}</td>
                <td class="actions">${acoes}</td>
            </tr>`;
    }).join('');
}

// ── Renderiza paginação ───────────────────────────────────────────
function renderPaginacao(page, totalPages) {
    if (!paginacao) return;
    if (totalPages <= 1) { paginacao.innerHTML = ''; return; }    if (totalPages <= 1) { paginacao.innerHTML = ''; return; }

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
        ...(state.cargo      && { cargo:      state.cargo }),
        ...(state.situacao   && { situacao:   state.situacao }),
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
        [selUnidade, document.getElementById('m-unidade'), document.getElementById('edit-unidade')].forEach(sel => {
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
    _salvarEstadoUsuarios();
    carregarUsuarios();
}

// ── Filtros com debounce no search ────────────────────────────────
function _salvarEstadoUsuarios() {
    if (!document.getElementById('tabela-body')) return;
    StateManager.save('/usuarios', {
        search:     state.search,
        cargo:      state.cargo,
        situacao:   state.situacao,
        unidade_id: state.unidade_id,
        page:       state.page,
    });
}

let debounceTimer;
if (inputSearch && selCargoAdmin !== undefined) {
    inputSearch.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            state.search = inputSearch.value.trim();
            state.page   = 1;
            _salvarEstadoUsuarios();
            carregarUsuarios();
        }, 400);
    });
}
if (selCargoAdmin) {
    selCargoAdmin.addEventListener('change', () => {
        state.cargo = selCargoAdmin.value;
        state.page  = 1;
        _salvarEstadoUsuarios();
        carregarUsuarios();
    });
}

if (selAtivo) {
    selAtivo.addEventListener('change', () => {
        state.situacao = selAtivo.value;
        state.page     = 1;
        _salvarEstadoUsuarios();
        carregarUsuarios();
    });
}
if (selUnidade) {
    selUnidade.addEventListener('change', () => {
        if (window.ORBE_USER?.role === 'admin') {
            state.unidade_id = selUnidade.value;
        }
        state.page = 1;
        _salvarEstadoUsuarios();
        carregarUsuarios();
    });
}

async function editarUsuario(id, modoGestor = false) {
    try {
        const endpoint = modoGestor
            ? `${BASE_URL}/api/users/${id}`
            : `${BASE_URL}/api/users/${id}`;

        const res = await fetch(endpoint);
        if (!res.ok) throw new Error('Erro ao buscar usuário');
        const json = await res.json();
        const u = json.data;

        // Avatar
        const avatarEl = document.getElementById('editAvatar');
        if (u.foto_url) {
            avatarEl.innerHTML = `<img src="${u.foto_url}" alt="${u.nome}" class="perfil-avatar-img">`;
        } else {
            avatarEl.textContent = u.nome.trim().split(' ')
                .filter(Boolean).slice(0, 2)
                .map(w => w[0].toUpperCase()).join('');
        }

        // Identity card
        document.getElementById('editRA').textContent    = u.ra       ?? '—';
        document.getElementById('editNome').textContent  = u.nome;
        document.getElementById('editCargo').textContent = u.cargo_raw ?? '—';

        // Campos hidden
        document.getElementById('edit-id').value          = u.id;
        document.getElementById('edit-servidor-id').value = u.servidor_id ?? '';

        // Campos do form
        document.getElementById('edit-nome').value                    = u.nome;
        document.getElementById('edit-email').value                   = u.email;
        document.getElementById('edit-cpf').value                     = u.cpf        ?? '';
        document.getElementById('edit-cargo').value                   = u.cargo_raw  ?? '';
        document.getElementById('edit-role').value                    = u.perfil_raw ?? 'user';
        document.getElementById('edit-situacao').value                = u.situacao   ?? 'ativo';
        document.getElementById('edit-data-nascimento').value         = brParaIso(u.data_nascimento);
        document.getElementById('edit-data-ingresso').value           = brParaIso(u.data_ingresso);
        document.getElementById('edit-previsao-aposentadoria').value  = brParaIso(u.previsao_aposentadoria);
        document.getElementById('edit-unidade').value                 = u.unidade_id ?? '';

        document.getElementById('modalEdicao').dataset.modoGestor = modoGestor ? '1' : '0';

        if (!modoGestor) {
            const est = StateManager.load('/usuarios') ?? {};
            est.modalEdicaoId = id;
            StateManager.save('/usuarios', est);
        }
        const camposBloqueados = ['edit-cargo', 'edit-role', 'edit-cpf'];
        camposBloqueados.forEach(campoId => {
            const el = document.getElementById(campoId);
            if (modoGestor) {
                el.setAttribute('disabled', 'disabled');
                el.style.cursor  = 'not-allowed';
                el.style.opacity = '0.6';
            } else {
                el.removeAttribute('disabled');
                el.style.cursor  = '';
                el.style.opacity = '';
            }
        });

        const selUnidadeModal = document.getElementById('edit-unidade');
        if (modoGestor) {
            const unidadeId    = u.unidade_id ?? '';
            const unidadeNome  = u.unidade    ?? '—';
            selUnidadeModal.innerHTML = `<option value="${unidadeId}">${unidadeNome}</option>`;
            selUnidadeModal.value    = unidadeId;
            selUnidadeModal.setAttribute('disabled', 'disabled');
            selUnidadeModal.style.cursor  = 'not-allowed';
            selUnidadeModal.style.opacity = '0.6';
        } else {
            selUnidadeModal.removeAttribute('disabled');
            selUnidadeModal.style.cursor  = '';
            selUnidadeModal.style.opacity = '';
            if (selUnidadeModal.options.length <= 1) {
                await carregarUnidades();
            }
            selUnidadeModal.value = u.unidade_id ?? '';
        }

        setFeedbackEdicao('', '');
        document.getElementById('modalEdicao').classList.add('open');
        document.body.style.overflow = 'hidden';

    } catch (err) {
        console.error(err);
        alert('Não foi possível carregar os dados do usuário.');
    }
}

function brParaIso(dataStr) {
    if (!dataStr) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataStr)) return dataStr;
    const [d, m, y] = dataStr.split('/');
    return `${y}-${m}-${d}`;
}

function calcularPrevisaoAposentadoria(dataNascimento, dataIngresso) {
    if (!dataNascimento || !dataIngresso) return '';

    const nasc    = new Date(dataNascimento);
    const ingresso = new Date(dataIngresso);

    const porIdade = new Date(nasc);
    porIdade.setFullYear(porIdade.getFullYear() + 55);

    const porTempo = new Date(ingresso);
    porTempo.setFullYear(porTempo.getFullYear() + 25);

    const maior = porIdade > porTempo ? porIdade : porTempo;
    return maior.toISOString().split('T')[0];
}

function closeModalEdicao() {
    const est = StateManager.load('/usuarios') ?? {};
    delete est.modalEdicaoId;
    StateManager.save('/usuarios', est);
    document.getElementById('modalEdicao').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('formEdicao').reset();
    setFeedbackEdicao('', '');
}

function dismissModalEdicao() {
    const id = document.getElementById('edit-id')?.value;
    if (id) {
        const est = StateManager.load('/usuarios') ?? {};
        est.modalEdicaoId = parseInt(id);
        StateManager.save('/usuarios', est);
    }
    document.getElementById('modalEdicao').classList.remove('open');
    document.body.style.overflow = '';
    setFeedbackEdicao('', '');
}

function setFeedbackEdicao(msg, tipo) {
    const el = document.getElementById('feedbackEdicao');
    if (!el) return;
    el.textContent = msg;
    el.className   = 'feedback-msg' + (tipo ? ` ${tipo}` : '');
    el.style.display = msg ? 'block' : 'none';
}

const modalEdicao = document.getElementById('modalEdicao');
if (modalEdicao) {
    modalEdicao.addEventListener('click', function (e) {
        if (e.target === this) closeModalEdicao();
    });
}

// ── MODAL DE CADASTRO ─────────────────────────────────────────────

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
    _salvarModalCadastro();
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

function dismissModal() {
    if (!modal) return;
    _salvarModalCadastro();
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

/** Fecha e reseta*/
function closeModal() {
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
    modal.querySelector('form')?.reset();
    setFeedback('', '');
    const est = StateManager.load('/usuarios') ?? {};
    delete est.modalCadastroAberto;
    delete est.modalCadastro;
    StateManager.save('/usuarios', est);
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

const inputNascCad    = document.getElementById('m-data-nascimento');
const inputAdmCad     = document.getElementById('m-data-admissao');
const inputPrevCad    = document.getElementById('m-previsao-aposentadoria');

function atualizarPrevisaoCadastro() {
    if (!inputNascCad || !inputAdmCad || !inputPrevCad) return;
    const prev = calcularPrevisaoAposentadoria(inputNascCad.value, inputAdmCad.value);
    inputPrevCad.value = prev;
}

if (inputNascCad) inputNascCad.addEventListener('change', atualizarPrevisaoCadastro);
if (inputAdmCad)  inputAdmCad.addEventListener('change',  atualizarPrevisaoCadastro);

const inputNascEdit = document.getElementById('edit-data-nascimento');
const inputAdmEdit  = document.getElementById('edit-data-ingresso');
const inputPrevEdit = document.getElementById('edit-previsao-aposentadoria');

function atualizarPrevisaoEdicao() {
    if (!inputNascEdit || !inputAdmEdit || !inputPrevEdit) return;
    const prev = calcularPrevisaoAposentadoria(inputNascEdit.value, inputAdmEdit.value);
    inputPrevEdit.value = prev;
}

if (inputNascEdit) inputNascEdit.addEventListener('change', atualizarPrevisaoEdicao);
if (inputAdmEdit)  inputAdmEdit.addEventListener('change',  atualizarPrevisaoEdicao);

/** Fecha o modal ao clicar no backdrop */
if (modal) {
    modal.addEventListener('click', function (e) {
        if (e.target === modal) dismissModal();
    });
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal?.classList.contains('open')) dismissModal();
});
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

            closeModal();
            carregarUsuarios();
            Toast.success('Usuário cadastrado!', json.mensagem ?? `RA: ${json.ra ?? '—'} | Senha padrão: ${json.senha ?? '—'}`);

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

const formEdicao = document.getElementById('formEdicao');
if (formEdicao) {
    formEdicao.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSalvarEdicao');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        const id = document.getElementById('edit-id').value;

        const modoGestor = document.getElementById('modalEdicao').dataset.modoGestor === '1';
        const cpfRaw = modoGestor
            ? null
            : (document.getElementById('edit-cpf').value || '').replace(/\D/g, '');

        const payload = {
            nome:                   document.getElementById('edit-nome').value.trim(),
            email:                  document.getElementById('edit-email').value.trim(),
            cpf:                    cpfRaw,
            cargo:                  document.getElementById('edit-cargo').value,
            role:                   document.getElementById('edit-role').value,
            situacao:               document.getElementById('edit-situacao').value.toLowerCase(),
            unidade_id:             document.getElementById('edit-unidade').value,
            data_nascimento:        document.getElementById('edit-data-nascimento').value || null,
            data_ingresso:          document.getElementById('edit-data-ingresso').value   || null,
            previsao_aposentadoria: document.getElementById('edit-previsao-aposentadoria').value || null,
        };

        try {
            const modoGestor = document.getElementById('modalEdicao').dataset.modoGestor === '1';
            const endpoint   = modoGestor
                ? `${BASE_URL}/api/gestor/colaboradores/${id}`
                : `${BASE_URL}/api/users/${id}`;

            const res  = await fetch(endpoint, {
                method:  'PUT',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Erro ao salvar.');
            closeModalEdicao();
            if (modoGestor && typeof recarregarColaboradores === 'function') {
                recarregarColaboradores();
            } else {
                carregarUsuarios();
            }
            Toast.success('Usuário atualizado!', 'As alterações foram salvas com sucesso.');
        } catch (err) {
            setFeedbackEdicao(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
        }
    });
}

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    if (tbody && document.getElementById('filtro-role')) {
        carregarUnidades();
        carregarUsuarios();
    }
});

(function () {
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
 
    if (!sidebar || !toggle) return;
 
    // Restaura estado salvo (aberta ou fechada)
    const savedSidebar = localStorage.getItem('sidebarOpen');
    if (savedSidebar === 'true') {
        sidebar.classList.add('open');
    }
 
    // Clique no hambúrguer
    toggle.addEventListener('click', function () {
        const isOpen = sidebar.classList.toggle('open');
        localStorage.setItem('sidebarOpen', isOpen);
    });
})();

(function () {
    'use strict';
    const CARGOS = {
        '3a_classe':      '3ª Classe',
        '2a_classe':      '2ª Classe',
        '1a_classe':      '1ª Classe',
        'classe_especial':'Classe Especial',
        'chefe_divisao':  'Chefe de Divisão',
        'diretor_geral':  'Diretor-Geral',
    };

    let rankingCompleto = [];

    function nivelClass(nivel) {
        if (nivel === 'Alto')  return 'alto';
        if (nivel === 'Médio') return 'medio';
        return 'baixo';
    }

    function cargoLabel(cargo) {
        return CARGOS[cargo] ?? cargo;
    }

    function renderIndicadores(data) {
        document.getElementById('ind-alto').textContent  = data.distribuicao.alto_potencial.quantidade;
        document.getElementById('ind-alto-pct').textContent =
            data.distribuicao.alto_potencial.percentual + '% do total';

        document.getElementById('ind-medio').textContent = data.distribuicao.medio_potencial.quantidade;
        document.getElementById('ind-medio-pct').textContent =
            data.distribuicao.medio_potencial.percentual + '% do total';

        document.getElementById('ind-media').textContent = data.pontuacao_media;
        document.getElementById('ind-total').textContent = data.total_talentos;
    }

    function renderCard(s) {
        const cls      = nivelClass(s.nivel_potencial);
        const posClass = s.posicao <= 3 ? `bt-posicao--${s.posicao}` : '';
        const barFill  = `bt-metrica__barra-fill--${cls}`;
        const largura  = Math.min(s.pontuacao, 100);

        return `
        <div class="bt-servidor bt-servidor--${cls}" onclick="window.location.href='/competencias?servidor_id=${s.id}'" style="cursor:pointer">
            <div class="bt-servidor__top">
                <div class="bt-posicao ${posClass}">${s.posicao}º</div>
                <div class="bt-avatar"><i class="fas fa-user-shield"></i></div>
                <div class="bt-info">
                    <div class="bt-info__nome">${s.nome}</div>
                    <div class="bt-info__sub">
                        <span class="bt-cargo-label">${cargoLabel(s.cargo)}</span>
                        ${s.unidade}
                    </div>
                </div>
                <span class="bt-badge bt-badge--${cls}">${s.nivel_potencial} Potencial</span>
            </div>

            <div class="bt-servidor__metricas">
                <!-- Pontuação com barra -->
                <div>
                    <div class="bt-metrica__label">Pontuação Total</div>
                    <div class="bt-metrica__barra-wrap">
                        <div class="bt-metrica__barra">
                            <div class="bt-metrica__barra-fill ${barFill}"
                                 style="width:${largura}%"></div>
                        </div>
                        <span class="bt-metrica__num">${s.pontuacao}</span>
                    </div>
                </div>

                <!-- Competências -->
                <div>
                    <div class="bt-metrica__label">Competências</div>
                    <div class="bt-metrica__val">
                        <i class="fas fa-trophy"></i>
                        ${s.total_competencias} registrada${s.total_competencias !== 1 ? 's' : ''}
                    </div>
                </div>

                <!-- Tempo de serviço -->
                <div>
                    <div class="bt-metrica__label">Tempo de Serviço</div>
                    <div class="bt-metrica__val">
                        <i class="fas fa-clock"></i>
                        ${s.tempo_servico}
                    </div>
                </div>
            </div>
        </div>`;
    }

    function renderLista(lista) {
        const el = document.getElementById('bt-lista');

        if (!lista || lista.length === 0) {
            el.innerHTML = `
                <div class="bt-empty">
                    <i class="fas fa-user-slash"></i>
                    Nenhum servidor encontrado para este critério.
                </div>`;
            document.getElementById('bt-contador').textContent = '0 servidores';
            return;
        }

        el.innerHTML = lista.map(renderCard).join('');
        document.getElementById('bt-contador').textContent =
            lista.length + (lista.length === 1 ? ' servidor' : ' servidores');
    }

    async function carregarIndicadores() {
        try {
            const res  = await fetch('/api/banco-talentos/indicadores');
            const json = await res.json();
            if (json.success) renderIndicadores(json.data);
        } catch (e) {
            console.error('Erro ao carregar indicadores:', e);
        }
    }

    async function carregarRanking() {
        try {
            const res  = await fetch('/api/banco-talentos/ranking');
            const json = await res.json();
            if (json.success) {
                rankingCompleto = json.data;
                renderLista(rankingCompleto);
            }
        } catch (e) {
            document.getElementById('bt-lista').innerHTML = `
                <div class="bt-empty">
                    <i class="fas fa-triangle-exclamation"></i>
                    Erro ao carregar o ranking. Tente novamente.
                </div>`;
        }
    }

    let debounceTimer;

    async function executarBusca(termo) {
        termo = termo.trim();

        if (termo === '') {
            renderLista(rankingCompleto);
            return;
        }

        try {
            const res  = await fetch('/api/banco-talentos/busca?q=' + encodeURIComponent(termo));
            const json = await res.json();

            if (json.success) {
                const resultado = json.data.map(s => {
                    const original = rankingCompleto.find(r => r.nome === s.nome);
                    return original ?? s;
                });
                renderLista(resultado);
            } else {
                renderLista([]);
            }
        } catch (e) {
            console.error('Erro na busca:', e);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (!document.getElementById('bt-lista')) return;
        carregarIndicadores();
        carregarRanking();
        const input    = document.getElementById('bt-search');
        const btnClear = document.getElementById('bt-search-clear');
        const estBT = StateManager.load('/banco-talentos');
        if (estBT?.search) {
            input.value = estBT.search;
            btnClear.style.display = estBT.search ? 'block' : 'none';
            carregarRanking().then(() => executarBusca(estBT.search));
        }

        input.addEventListener('input', () => {
            const v = input.value;
            btnClear.style.display = v ? 'block' : 'none';
            StateManager.save('/banco-talentos', { search: v });
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => executarBusca(v), 350);
        });

        btnClear.addEventListener('click', () => {
            input.value = '';
            btnClear.style.display = 'none';
            StateManager.clear('/banco-talentos');
            renderLista(rankingCompleto);
            input.focus();
        });
    });

})();

(function () {
    if (!document.getElementById('painel-indicadores')) return;

    function isDark()     { return document.documentElement.getAttribute('data-theme') === 'dark'; }
    function corTexto()   { return isDark() ? '#e2e8f0' : '#1e293b'; }
    function corGrade()   { return isDark() ? '#334155' : '#e2e8f0'; }
    function corPrimary() { return isDark() ? '#3a7cff' : '#002d5e'; }

    function fmtNum(v)  { return (v == null || v === '') ? '—' : Number(v).toLocaleString('pt-BR'); }
    function fmtAnos(v) { return (v == null || v === '') ? '—' : parseFloat(v).toFixed(1) + ' anos'; }
    function fmtPct(v) {
        if (v == null || v === '') return '—';
        const n = parseFloat(v);
        return (n >= 0 ? '+' : '') + n.toFixed(1) + '%';
    }

    function abreviaUnidade(nome, sigla) {
        if (sigla && sigla.trim()) return sigla.trim();
        return nome.length > 14 ? nome.slice(0, 12) + '…' : nome;
    }

    function preencheIndicadores(d) {
        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        setText('ind-total', d.total_servidores);
        setText('ind-admissoes', d.admissoes_ano ?? 0);
        setText('ind-aposentadoria', d.proximos_aposentadoria);
        setText('ind-capacitacoes', d.capacitacoes_ano);
        setText(
            'ind-tempo-medio',
            `${Number(d.tempo_medio_servico).toFixed(1)} anos`
        );
    }

    let chartBarras = null;
    let chartPizza  = null;
    let dadosBarras = null;
    let dadosPizza  = null;

    function renderBarras(itens) {
        const ctx = document.getElementById('grafico-barras').getContext('2d');
        if (chartBarras) chartBarras.destroy();

        const labels  = itens.map(i => abreviaUnidade(i.unidade, i.sigla));
        const valores = itens.map(i => Number(i.total));
        const nomes   = itens.map(i => i.unidade);

        chartBarras = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Servidores',
                    data: valores,
                    backgroundColor: corPrimary(),
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: ctx => nomes[ctx[0].dataIndex],
                            label: ctx => ' ' + fmtNum(ctx.parsed.y) + ' servidores'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: corTexto(),
                            font: { size: 11 },
                            maxRotation: 0,
                            minRotation: 0,
                        },
                        grid: { color: corGrade() }
                    },
                    y: {
                        ticks: { color: corTexto(), font: { size: 11 } },
                        grid:  { color: corGrade() },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    const CORES_PIZZA = ['#002d5e','#22c55e','#f59e0b','#ef4444','#3b82f6','#8b5cf6'];

    function renderPizza(itens) {
        const ctx = document.getElementById('grafico-pizza').getContext('2d');
        if (chartPizza) chartPizza.destroy();

        const labels  = itens.map(i => i.status);
        const valores = itens.map(i => Number(i.total));

        chartPizza = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: valores,
                    backgroundColor: CORES_PIZZA.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: isDark() ? '#1e293b' : '#ffffff',
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: corTexto(),
                            padding: 16,
                            font: { size: 12 },
                            usePointStyle: true,
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + fmtNum(ctx.parsed) + ' (' + ctx.label + ')'
                        }
                    }
                }
            }
        });
    }

    const observer = new MutationObserver(() => {
        if (dadosBarras) renderBarras(dadosBarras);
        if (dadosPizza)  renderPizza(dadosPizza);
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });

    async function carregaIndicadores() {
        try {
            const r    = await fetch('/api/painel/indicadores');
            const json = await r.json();
            if (json.data) preencheIndicadores(json.data);
        } catch (e) {
            console.error('Painel — erro indicadores:', e);
        }
    }

    async function carregaBarras() {
        const loading = document.getElementById('loading-barras');
        const wrap    = document.getElementById('wrap-barras');
        try {
            const r    = await fetch('/api/painel/servidores-por-unidade');
            const json = await r.json();
            dadosBarras = json.data || [];
            loading.style.display = 'none';
            wrap.style.display    = 'block';
            renderBarras(dadosBarras);
        } catch (e) {
            loading.innerHTML = '<i class="fas fa-triangle-exclamation"></i> Erro ao carregar dados.';
            console.error('Painel — erro barras:', e);
        }
    }

    async function carregaPizza() {
        const loading = document.getElementById('loading-pizza');
        const wrap    = document.getElementById('wrap-pizza');
        try {
            const r    = await fetch('/api/painel/distribuicao-status');
            const json = await r.json();
            dadosPizza = json.data || [];
            loading.style.display = 'none';
            wrap.style.display    = 'flex';
            renderPizza(dadosPizza);
        } catch (e) {
            loading.innerHTML = '<i class="fas fa-triangle-exclamation"></i> Erro ao carregar dados.';
            console.error('Painel — erro pizza:', e);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        carregaIndicadores();
        carregaBarras();
        carregaPizza();
    });

})();

const _toastActive = new Set();

function showToast(type, title, desc = '', duration = 3000) {
    const key = `${type}::${title}`;
    if (_toastActive.has(key)) return;
    _toastActive.add(key);

    const icons = { success: '✓', error: '!', warning: '⚠', info: 'i' };

    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] ?? 'i'}</div>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            ${desc ? `<div class="toast-desc">${desc}</div>` : ''}
        </div>
        <button class="toast-close" aria-label="Fechar">×</button>
        <div class="toast-progress" style="width:100%"></div>
    `;

    if (type === 'error') {
        container.appendChild(toast);
    } else {
        container.prepend(toast);
    }

    const bar = toast.querySelector('.toast-progress');
    bar.style.transition = `width ${duration}ms linear`;
    requestAnimationFrame(() => requestAnimationFrame(() => bar.style.width = '0%'));
    
    const dismiss = () => {
        if (toast.classList.contains('toast-removing')) return;
        toast.classList.add('toast-removing');
        _toastActive.delete(key);
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };

    toast.querySelector('.toast-close').addEventListener('click', dismiss);
    const timer = setTimeout(dismiss, duration);
    toast.querySelector('.toast-close').addEventListener('click', () => clearTimeout(timer));
}

const Toast = {
    success: (title, desc, duration)       => showToast('success', title, desc, duration),
    error:   (title, desc, duration = 5000) => showToast('error',   title, desc, duration),
    warning: (title, desc, duration)       => showToast('warning', title, desc, duration),
    info:    (title, desc, duration)       => showToast('info',    title, desc, duration),
};

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('php-toast-data');
    if (!el) return;
    const { type, title, desc, duration } = JSON.parse(el.dataset.toast);
    showToast(type, title, desc ?? '', duration ?? 3000);
    el.remove();
});

// ── Modal de Perfil do Servidor ───────────────────────────────────
let _mpsServidorIdAtual = null;

function mpsAltTab(btnClicado, tabNome) {
    document.querySelectorAll('.mps-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.mps-tab-content').forEach(c => c.style.display = 'none');
    btnClicado.classList.add('active');
    document.getElementById(`mpsTab-${tabNome}`).style.display = 'block';
}

async function abrirPerfilServidor(servidorId) {
    if (!servidorId) return;
    _mpsServidorIdAtual = servidorId;

    const modal = document.getElementById('modalPerfilServidor');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Reset visual
    document.getElementById('mpsLoading').style.display  = 'flex';
    document.getElementById('mpsError').style.display    = 'none';
    document.querySelectorAll('.mps-tab-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.mps-tab').forEach(t => t.classList.remove('active'));
    document.querySelector('.mps-tab[data-tab="funcional"]').classList.add('active');

    // Cabeçalho em branco enquanto carrega
    document.getElementById('mpsRa').textContent    = '—';
    document.getElementById('mpsNome').textContent  = '—';
    document.getElementById('mpsCargo').textContent = '—';
    document.getElementById('mpsBadges').innerHTML  = '';
    document.getElementById('mpsTempoServico').style.display = 'none';

    try {
        const res = await fetch(`${BASE_URL}/api/servidores/${servidorId}/perfil`);
        if (res.status === 401) { window.location.href = `${BASE_URL}/login`; return; }
        if (res.status === 403) {
            mpsMostrarErro();
            return;
        }
        if (!res.ok) throw new Error('Erro na API');
        const json = await res.json();
        mpsPreencherModal(json.data);
    } catch (err) {
        console.error(err);
        mpsMostrarErro();
    }
}

function mpsMostrarErro() {
    document.getElementById('mpsLoading').style.display = 'none';
    document.getElementById('mpsError').style.display   = 'flex';
}

function mpsRecarregar() {
    if (_mpsServidorIdAtual) abrirPerfilServidor(_mpsServidorIdAtual);
}

function fecharPerfilServidor() {
    document.getElementById('modalPerfilServidor').style.display = 'none';
    document.body.style.overflow = '';
    _mpsServidorIdAtual = null;
}

function mpsPreencherModal(d) {
    // ── Cabeçalho ──────────────────────────────────────────────────
    const avatarEl = document.getElementById('mpsAvatar');
    if (d.foto_url) {
        avatarEl.innerHTML = `<img src="${d.foto_url}" alt="${d.nome}" class="perfil-avatar-img">`;
    } else {
        const iniciais = d.nome.trim().split(' ').filter(Boolean)
            .slice(0, 2).map(w => w[0].toUpperCase()).join('');
        avatarEl.textContent = iniciais;
    }

    document.getElementById('mpsRa').textContent    = d.ra    ?? '—';
    document.getElementById('mpsNome').textContent  = d.nome  ?? '—';
    document.getElementById('mpsCargo').textContent = [d.patente, mpsFormatarCargo(d.cargo)].filter(Boolean).join(' · ');
    document.getElementById('mpsBadges').innerHTML  = mpsBadgeSituacao(d.situacao);

    const anos = mpsAnosServico(d.data_ingresso);
    if (anos !== null) {
        document.getElementById('mpsTempoNum').textContent      = anos;
        document.getElementById('mpsTempoServico').style.display = 'flex';
    }

    // ── Aba Funcional ───────────────────────────────────────────────
    document.getElementById('mps-ra').textContent            = d.ra                       ?? '—';
    document.getElementById('mps-cargo').textContent         = mpsFormatarCargo(d.cargo)  ?? '—';
    document.getElementById('mps-patente').textContent       = d.patente                  ?? '—';
    document.getElementById('mps-situacao').innerHTML        = mpsBadgeSituacao(d.situacao);
    document.getElementById('mps-ingresso').textContent      = d.data_ingresso            ?? '—';
    document.getElementById('mps-aposentadoria').textContent = d.previsao_aposentadoria   ?? '—';
    document.getElementById('mps-unidade').textContent       = d.unidade                  ?? '—';
    document.getElementById('mps-sigla').textContent         = d.unidade_sigla            ?? '—';
    document.getElementById('mps-estado').textContent        = d.estado                   ?? '—';
    document.getElementById('mps-unidade-tipo').textContent  = mpsFormatarTipoUnidade(d.unidade_tipo);
    document.getElementById('mps-email').textContent         = d.email                    ?? '—';

    document.getElementById('mps-salario').textContent        = d.salario_base
        ? 'R$ ' + parseFloat(d.salario_base).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
        : '—';
    document.getElementById('mps-salario-desde').textContent  = d.salario_desde   ?? '—';
    document.getElementById('mps-salario-motivo').textContent = mpsFormatarMotivoSalario(d.salario_motivo);

    // Benefícios
    const benEl = document.getElementById('mpsBeneficios');
    if (d.beneficios && d.beneficios.length) {
        benEl.innerHTML = d.beneficios.map(b => `
            <div class="mps-beneficio-item">
                <i class="${mpsBeneficioIcone(b.tipo)}" aria-hidden="true"></i>
                <div>
                    <span class="mps-beneficio-nome">${mpsFormatarTipoBeneficio(b.tipo)}</span>
                    <span class="mps-beneficio-desc">${b.descricao ?? ''}</span>
                </div>
            </div>
        `).join('');
    } else {
        benEl.innerHTML = '<span class="mps-empty">Nenhum benefício cadastrado.</span>';
    }

    // ── Aba Trajetória ──────────────────────────────────────────────

    // Movimentações
    const movEl = document.getElementById('mpsMovimentacoes');
    if (d.movimentacoes && d.movimentacoes.length) {
        movEl.innerHTML = d.movimentacoes.map(m => `
            <li class="mps-timeline-item">
                <div class="mps-tl-dot mps-tl-dot--${m.tipo}">
                    <i class="${mpsTipoMovIcone(m.tipo)}" aria-hidden="true"></i>
                </div>
                <div class="mps-tl-body">
                    <span class="mps-tl-title">${m.descricao ?? mpsFormatarTipoMov(m.tipo)}</span>
                    <span class="mps-tl-sub">
                        ${m.unidade_origem ? m.unidade_origem + ' → ' : ''}${m.unidade_destino ?? '—'}
                        · ${m.data_inicio}
                    </span>
                </div>
            </li>
        `).join('');
    } else {
        movEl.innerHTML = '<li class="mps-empty">Nenhuma movimentação registrada.</li>';
    }

    // Avaliações
    const avalEl = document.getElementById('mpsAvaliacoes');
    if (d.avaliacoes && d.avaliacoes.length) {
        avalEl.innerHTML = d.avaliacoes.map(a => `
            <div class="mps-avaliacao-item">
                <div class="mps-avaliacao-pontuacao mps-pontuacao--${mpsPontuacaoClasse(a.pontuacao)}">
                    ${a.pontuacao}<span>/100</span>
                </div>
                <div class="mps-avaliacao-info">
                    <span class="mps-avaliacao-obs">${a.observacao ?? '—'}</span>
                    <span class="mps-avaliacao-meta">
                        ${a.data_avaliacao}
                        ${a.avaliador ? '· Avaliado por ' + a.avaliador : ''}
                    </span>
                </div>
            </div>
        `).join('');
    } else {
        avalEl.innerHTML = '<span class="mps-empty">Nenhuma avaliação registrada.</span>';
    }

    // Competências
    const compEl = document.getElementById('mpsCompetencias');
    if (d.competencias && d.competencias.length) {
        compEl.innerHTML = d.competencias.map(c => `
            <div class="mps-competencia-tag mps-comp--${c.tipo}">
                <i class="${mpsCompIcone(c.tipo)}" aria-hidden="true"></i>
                <span>${c.nome}</span>
                ${c.validade ? `<small>Válido até ${c.validade}</small>` : ''}
            </div>
        `).join('');
    } else {
        compEl.innerHTML = '<span class="mps-empty">Nenhuma competência registrada.</span>';
    }

    // Férias & Afastamentos
    const ferEl = document.getElementById('mpsFerias');
    if (d.ferias_afastamentos && d.ferias_afastamentos.length) {
        ferEl.innerHTML = d.ferias_afastamentos.map(f => `
            <div class="mps-ferias-item">
                <span class="mps-ferias-tipo mps-ferias--${f.tipo}">
                    <i class="${mpsFeriasIcone(f.tipo)}" aria-hidden="true"></i>
                    ${mpsFormatarTipoAfastamento(f.tipo)}
                </span>
                <span class="mps-ferias-datas">
                    ${f.data_inicio}${f.data_fim ? ' → ' + f.data_fim : ' → em andamento'}
                    ${f.dias ? ' · ' + f.dias + ' dias' : ''}
                </span>
            </div>
        `).join('');
    } else {
        ferEl.innerHTML = '<span class="mps-empty">Nenhum registro encontrado.</span>';
    }

    // PDI
    const pdiWrap = document.getElementById('mpsPdiWrap');
    const pdiEl   = document.getElementById('mpsPdi');
    if (d.pdis && d.pdis.length) {
        const pdi = d.pdis[0]; // exibe o mais recente
        const totalAcoes     = pdi.acoes?.length ?? 0;
        const acoesConc      = pdi.acoes?.filter(a => a.concluida).length ?? 0;
        const pct            = totalAcoes ? Math.round((acoesConc / totalAcoes) * 100) : 0;

        pdiEl.innerHTML = `
            <div class="mps-pdi-card">
                <div class="mps-pdi-header">
                    <div>
                        <span class="mps-pdi-objetivo">${pdi.cargo_objetivo ?? '—'}</span>
                        <span class="mps-pdi-status mps-pdi-status--${pdi.status}">${mpsFormatarStatusPdi(pdi.status)}</span>
                    </div>
                    <span class="mps-pdi-meta">
                        Responsável: ${pdi.responsavel ?? '—'} · Revisão: ${pdi.data_revisao ?? '—'}
                    </span>
                </div>
                <div class="mps-pdi-progresso-label">
                    <span>Progresso das ações</span><span>${acoesConc}/${totalAcoes} concluídas</span>
                </div>
                <div class="mps-pdi-barra">
                    <div class="mps-pdi-barra-fill" style="width:${pct}%"></div>
                </div>
                <ul class="mps-pdi-acoes">
                    ${(pdi.acoes ?? []).map(a => `
                        <li class="${a.concluida ? 'concluida' : ''}">
                            <i class="fas ${a.concluida ? 'fa-circle-check' : 'fa-circle'}" aria-hidden="true"></i>
                            ${a.descricao}
                            ${a.prazo ? `<small>Prazo: ${a.prazo}</small>` : ''}
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;
        pdiWrap.style.display = 'block';
    } else {
        pdiWrap.style.display = 'none';
    }

    // Exibe conteúdo
    document.getElementById('mpsLoading').style.display = 'none';
    document.getElementById('mpsTab-funcional').style.display = 'block';
}

// Fecha clicando fora do modal
document.addEventListener('click', e => {
    const modal = document.getElementById('modalPerfilServidor');
    if (modal && e.target === modal) fecharPerfilServidor();
});

// ── Helpers do modal ─────────────────────────────────────────────
function mpsAnosServico(dataIngresso) {
    if (!dataIngresso) return null;
    let d;
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dataIngresso)) {
        const [dia, mes, ano] = dataIngresso.split('/');
        d = new Date(`${ano}-${mes}-${dia}`);
    } else {
        d = new Date(dataIngresso);
    }
    const hoje = new Date();
    let anos = hoje.getFullYear() - d.getFullYear();
    if (hoje.getMonth() < d.getMonth() ||
        (hoje.getMonth() === d.getMonth() && hoje.getDate() < d.getDate())) anos--;
    return anos >= 0 ? anos : null;
}

function mpsBadgeSituacao(s) {
    const map = {
        ativo:               ['pill-active',    'Ativo'],
        afastado:            ['pill-afastado',  'Afastado'],
        aposentado:          ['pill-aposentado','Aposentado'],
        licenca_maternidade: ['pill-licenca',   'Lic. Maternidade'],
        desligado:           ['pill-desligado', 'Desligado'],
    };
    const [cls, label] = map[s] ?? ['pill-outros', s ?? '—'];
    return `<span class="pill ${cls}">${label}</span>`;
}

function mpsFormatarCargo(c) {
    const map = {
        '3a_classe':      '3ª Classe',
        '2a_classe':      '2ª Classe',
        '1a_classe':      '1ª Classe',
        'classe_especial':'Classe Especial',
        'chefe_divisao':  'Chefe de Divisão',
        'diretor_geral':  'Diretor-Geral',
    };
    return map[c] ?? c ?? '—';
}

function mpsFormatarTipoUnidade(t) {
    const map = {
        superintendencia: 'Superintendência',
        delegacia:        'Delegacia',
    };
    return map[t] ?? t ?? '—';
}

function mpsFormatarMotivoSalario(m) {
    const map = {
        admissao:      'Admissão',
        promocao:      'Promoção',
        revisao_anual: 'Revisão Anual',
    };
    return map[m] ?? m ?? '—';
}

function mpsFormatarTipoBeneficio(t) {
    const map = {
        plano_saude:    'Plano de Saúde',
        plano_odonto:   'Plano Odontológico',
        vale_transporte:'Vale-Transporte',
        vale_refeicao:  'Vale-Refeição',
        auxilio_educacao:'Auxílio Educação',
        seguro_vida:    'Seguro de Vida',
        outros:         'Outros',
    };
    return map[t] ?? t ?? '—';
}

function mpsBeneficioIcone(t) {
    const map = {
        plano_saude:     'fas fa-heart-pulse',
        plano_odonto:    'fas fa-tooth',
        vale_transporte: 'fas fa-bus',
        vale_refeicao:   'fas fa-utensils',
        auxilio_educacao:'fas fa-graduation-cap',
        seguro_vida:     'fas fa-shield',
        outros:          'fas fa-circle-plus',
    };
    return map[t] ?? 'fas fa-circle-plus';
}

function mpsFormatarTipoMov(t) {
    const map = {
        transferencia: 'Transferência',
        promocao:      'Promoção',
        rebaixamento:  'Rebaixamento',
        afastamento:   'Afastamento',
        retorno:       'Retorno',
        cessao:        'Cessão',
        outros:        'Outros',
    };
    return map[t] ?? t ?? '—';
}

function mpsTipoMovIcone(t) {
    const map = {
        transferencia: 'fas fa-building',
        promocao:      'fas fa-arrow-up',
        rebaixamento:  'fas fa-arrow-down',
        afastamento:   'fas fa-user-clock',
        retorno:       'fas fa-rotate-left',
        cessao:        'fas fa-handshake',
        outros:        'fas fa-ellipsis',
    };
    return map[t] ?? 'fas fa-ellipsis';
}

function mpsFormatarTipoAfastamento(t) {
    const map = {
        ferias:               'Férias',
        licenca_medica:       'Licença Médica',
        licenca_maternidade:  'Licença Maternidade',
        licenca_paternidade:  'Licença Paternidade',
        afastamento_judicial: 'Afastamento Judicial',
        cessao:               'Cessão',
        outros:               'Outros',
    };
    return map[t] ?? t ?? '—';
}

function mpsFeriasIcone(t) {
    const map = {
        ferias:               'fas fa-umbrella-beach',
        licenca_medica:       'fas fa-kit-medical',
        licenca_maternidade:  'fas fa-baby',
        licenca_paternidade:  'fas fa-baby',
        afastamento_judicial: 'fas fa-gavel',
        cessao:               'fas fa-handshake',
        outros:               'fas fa-ellipsis',
    };
    return map[t] ?? 'fas fa-ellipsis';
}

function mpsCompIcone(t) {
    const map = {
        curso:          'fas fa-book',
        certificacao:   'fas fa-certificate',
        especializacao: 'fas fa-microscope',
        habilidade:     'fas fa-star',
    };
    return map[t] ?? 'fas fa-circle';
}

function mpsPontuacaoClasse(p) {
    if (p >= 85) return 'alto';
    if (p >= 70) return 'medio';
    return 'baixo';
}

function mpsFormatarStatusPdi(s) {
    const map = {
        nao_iniciado: 'Não iniciado',
        em_andamento: 'Em andamento',
        concluido:    'Concluído',
        cancelado:    'Cancelado',
    };
    return map[s] ?? s ?? '—';
}

// ── Preservação do modal de cadastro ─────────────────────────
function _salvarModalCadastro() {
    const modalEl = document.getElementById('modalCadastro');
    if (!modalEl) return;
    const aberto = modalEl.classList.contains('open');
    const est = StateManager.load('/usuarios') ?? {};
    if (aberto) {
        est.modalCadastro = {
            nome:              document.getElementById('m-nome')?.value ?? '',
            email:             document.getElementById('m-email')?.value ?? '',
            cpf:               document.getElementById('m-cpf')?.value ?? '',
            cargo:             document.getElementById('m-cargo')?.value ?? '',
            role:              document.getElementById('m-role')?.value ?? '',
            unidade_id:        document.getElementById('m-unidade')?.value ?? '',
            situacao:          document.getElementById('m-status')?.value ?? '',
            data_nascimento:   document.getElementById('m-data-nascimento')?.value ?? '',
            data_admissao:     document.getElementById('m-data-admissao')?.value ?? '',
        };
    } else {
        delete est.modalCadastro;
        delete est.modalCadastroAberto;
    }
    StateManager.save('/usuarios', est);
}

function _restaurarCamposModalCadastro(dados) {
    if (!document.getElementById('modalCadastro')) return;
    if (dados.nome)            document.getElementById('m-nome').value            = dados.nome;
    if (dados.email)           document.getElementById('m-email').value           = dados.email;
    if (dados.cpf)             document.getElementById('m-cpf').value             = dados.cpf;
    if (dados.cargo)           document.getElementById('m-cargo').value           = dados.cargo;
    if (dados.role)            document.getElementById('m-role').value            = dados.role;
    if (dados.situacao)        document.getElementById('m-status').value          = dados.situacao;
    if (dados.data_nascimento) document.getElementById('m-data-nascimento').value = dados.data_nascimento;
    if (dados.data_admissao)   document.getElementById('m-data-admissao').value   = dados.data_admissao;
    if (dados.unidade_id) {
        setTimeout(() => {
            const sel = document.getElementById('m-unidade');
            if (sel) sel.value = dados.unidade_id;
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const campos = ['m-nome','m-email','m-cpf','m-cargo','m-role',
                    'm-unidade','m-status','m-data-nascimento','m-data-admissao'];
    campos.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input',  _salvarModalCadastro);
        el.addEventListener('change', _salvarModalCadastro);
    });
});