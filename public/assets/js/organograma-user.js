/*
|--------------------------------------------------------------------------
| Admin (clicável) → Gestor (clicável) → Você (não expande)
| Colegas de unidade exibidos abaixo, sem expansão
|--------------------------------------------------------------------------
*/
const SVG_NS = 'http://www.w3.org/2000/svg';
let _scale    = 1;
let _dadosUser = null;
let _colegas   = [];

let _gestorVisivel  = false;
let _usuarioVisivel = false;

document.addEventListener('DOMContentLoaded', () => {
    carregarHierarquia();
});

async function carregarHierarquia() {
    try {
        const [resHier, resCol] = await Promise.all([
            fetch('/api/organograma/hierarquia-usuario'),
            fetch('/api/colaboradores')
        ]);
        const jsonHier = await resHier.json();
        const jsonCol  = await resCol.json();
        if (!jsonHier.success) throw new Error(jsonHier.message);
        _dadosUser = jsonHier.data;
        _colegas   = resCol.ok
            ? (jsonCol.data ?? []).filter(c => c.id !== _dadosUser.usuario.id)
            : [];
        renderOrganograma(_dadosUser);

        const est = StateManager.load('/organograma-user');
        if (est?.gestorVisivel) {
            _restaurarEstadoUser(est);
        }
    } catch (e) {
        document.getElementById('organograma-root').innerHTML =
            `<p style="text-align:center;color:var(--text-secondary)">
                <i class="fas fa-circle-exclamation"></i> Erro ao carregar organograma.
             </p>`;
    }
}

function renderOrganograma(dados) {
    const root    = document.getElementById('organograma-root');
    const usuario = dados.usuario;
    root.innerHTML = `
        <div id="org-zoom-wrapper">
            <div id="org-tree">
                <svg id="svg-lines"></svg>
                <div class="org-level" id="level-admin"></div>
                <div class="org-level" id="level-gestor"></div>
                <div class="org-level level-usuario-centro" id="level-usuario">
                    <div class="org-node" id="node-user-${usuario.id}">
                        ${cardUserHTML(usuario, 'user', true, true)}
                    </div>
                </div>
                <div class="org-level hidden" id="level-colegas"></div>
            </div>
        </div>
    `;
    iniciarZoom();
    atualizarSVGSize();
    document.getElementById(`card-user-${usuario.id}`)
        .addEventListener('click', toggleExpansao);
}

function toggleExpansao() {
    const admin      = _dadosUser.admin;
    const gestor     = _dadosUser.gestor;
    const usuario    = _dadosUser.usuario;
    const levelAdmin = document.getElementById('level-admin');
    const levelGest  = document.getElementById('level-gestor');
    const levelCol   = document.getElementById('level-colegas');
    const userCard   = document.getElementById(`card-user-${usuario.id}`);
    const badge      = userCard?.querySelector('.expand-badge');
    const tree       = document.getElementById('org-tree');

    if (_gestorVisivel || _usuarioVisivel) {
        limparLinhas('admin-line');
        limparLinhas('gestor-line');
        [levelAdmin, levelGest, levelCol].forEach(level => {
            level.querySelectorAll('.org-node').forEach(n => n.classList.add('leaving'));
        });
        setTimeout(() => {
            levelAdmin.innerHTML = ''; 
            levelGest.innerHTML  = '';
            levelCol.classList.add('hidden'); levelCol.innerHTML = '';
            _gestorVisivel  = false;
            _usuarioVisivel = false;
            tree.classList.remove('expanded');
            if (badge) badge.textContent = '+';
            atualizarSVGSize();
            StateManager.clear('/organograma-user');
        }, 310);
        return;
    }

    userCard.classList.add('pulsing');
    setTimeout(() => userCard.classList.remove('pulsing'), 600);

    _gestorVisivel  = true;
    _usuarioVisivel = true;
    tree.classList.add('expanded');
    if (badge) badge.textContent = '−';

    levelAdmin.innerHTML = `
        <div class="org-node" id="node-admin-${admin.id}">
            ${cardUserHTML(admin, 'admin', false, false)}
        </div>
    `;

    levelGest.innerHTML = `
        <div class="org-node" id="node-gestor-${gestor.id}">
            ${cardUserHTML(gestor, 'gestor', false, false)}
        </div>
    `;

    if (_colegas.length) {
        levelCol.innerHTML = _colegas.map((c, i) => `
            <div class="org-node" id="node-colega-${c.id}" style="animation-delay:${i * 0.06}s">
                <div class="person-card user" style="cursor:default">
                    <div class="avatar-ring" style="cursor:default">
                        ${avatarIniciais(formatarNome(c.nome), '#6B7280')}
                    </div>
                    <div class="person-name">${formatarNome(c.nome)}</div>
                    <div class="person-role">${c.cargo_label ?? c.cargo ?? '—'}</div>
                </div>
            </div>
        `).join('');
        levelCol.classList.remove('hidden');
    }

    StateManager.save('/organograma-user', {
        gestorVisivel: true,
        usuarioVisivel: true,
    });

    setTimeout(() => {
        atualizarSVGSize();
        desenharLinhaAdminGestor();
        desenharLinhaGestorUsuario();
        desenharLinhasUsuarioColegas();
    }, 500);
}

function desenharLinhasUsuarioColegas() {
    limparLinhas('colega-line');
    const usuario  = _dadosUser.usuario;
    const userNode = document.getElementById(`node-user-${usuario.id}`);
    if (!userNode) return;
    const uRect = getRect(userNode);
    _colegas.forEach((c, i) => {
        const cNode = document.getElementById(`node-colega-${c.id}`);
        if (!cNode) return;
        const cRect = getRect(cNode);
        criarCurvaBezier(uRect.cx, uRect.bot + 2, cRect.cx, cRect.top - 2, 'colega-line', i * 0.05);
    });
}

function desenharLinhaAdminGestor() {
    limparLinhas('admin-line');
    const admin      = _dadosUser.admin;
    const gestor     = _dadosUser.gestor;
    const adminNode  = document.getElementById(`node-admin-${admin.id}`);
    const gestorNode = document.getElementById(`node-gestor-${gestor.id}`);
    if (!adminNode || !gestorNode) return;
    const r1 = getRect(adminNode);
    const r2 = getRect(gestorNode);
    criarCurvaBezier(r1.cx, r1.bot + 2, r2.cx, r2.top - 2, 'admin-line');
}

function desenharLinhaGestorUsuario() {
    limparLinhas('gestor-line');
    const gestor     = _dadosUser.gestor;
    const usuario    = _dadosUser.usuario;
    const gestorNode = document.getElementById(`node-gestor-${gestor.id}`);
    const userNode   = document.getElementById(`node-user-${usuario.id}`);
    if (!gestorNode || !userNode) return;
    const r1 = getRect(gestorNode);
    const r2 = getRect(userNode);
    criarCurvaBezier(r1.cx, r1.bot + 2, r2.cx, r2.top - 2, 'gestor-line');
}

function cardUserHTML(pessoa, tipo, eu = false, expansivel = false) {
    const nome  = formatarNome(pessoa.nome);
    const badge = expansivel ? `<div class="expand-badge">+</div>` : '';

    const avatarInner = pessoa.avatar
        ? `<img src="${pessoa.avatar}" alt="${nome}" class="avatar-foto"
               onerror="this.parentElement.innerHTML=avatarIniciais('${nome}','${pessoa.cor ?? '#1E3A8A'}')">`
        : avatarIniciais(nome, pessoa.cor ?? '#1E3A8A');

    const isMe      = tipo === 'user' && eu;
    const clickAttr = isMe
        ? `onclick="abrirPerfilServidor(${pessoa.servidor_id})" title="Ver meu perfil" style="cursor:pointer"`
        : `style="cursor:default"`;

    return `
        <div class="person-card ${tipo} ${eu ? 'is-me' : ''}" id="card-${tipo}-${pessoa.id}">
            <div class="avatar-ring" ${clickAttr}>
                ${avatarInner}
            </div>
            <div class="person-name">
                ${nome}${eu ? ' <span class="me-badge">Você</span>' : ''}
            </div>
            <div class="person-role">${pessoa.cargo_label || pessoa.cargo || 'Servidor'}</div>
            ${badge}
        </div>
    `;
}

function avatarIniciais(nome, cor) {
    const partes   = nome.trim().split(' ').filter(Boolean);
    const iniciais = partes.length >= 2
        ? partes[0][0].toUpperCase() + partes[partes.length - 1][0].toUpperCase()
        : (partes[0]?.[0] ?? '?').toUpperCase();
    return `<span class="avatar-iniciais" style="background:${cor}">${iniciais}</span>`;
}

function formatarNome(nomeCompleto) {
    if (!nomeCompleto || typeof nomeCompleto !== 'string') return '—';
    const p = nomeCompleto.trim().split(' ');
    return p.length === 1 ? p[0] : `${p[0]} ${p[p.length - 1]}`;
}

function getRect(el) {
    const tree     = document.getElementById('org-tree');
    const treeRect = tree.getBoundingClientRect();
    const elRect   = el.getBoundingClientRect();
    return {
        cx:  (elRect.left - treeRect.left + elRect.width  / 2) / _scale,
        top: (elRect.top  - treeRect.top) / _scale,
        bot: (elRect.bottom - treeRect.top) / _scale,
    };
}

function criarCurvaBezier(x1, y1, x2, y2, classe, delay = 0) {
    const svg  = document.getElementById('svg-lines');
    const midY = (y1 + y2) / 2;
    const d    = `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`;
    const path = document.createElementNS(SVG_NS, 'path');
    path.setAttribute('d', d);
    path.classList.add('svg-conn', classe);
    svg.appendChild(path);
    const len = path.getTotalLength();
    path.style.strokeDasharray  = len;
    path.style.strokeDashoffset = len;
    path.getBoundingClientRect();
    path.style.transition       = `stroke-dashoffset 0.55s cubic-bezier(0.4,0,0.2,1) ${delay}s`;
    path.style.strokeDashoffset = '0';
    return path;
}

function limparLinhas(classe) {
    document.querySelectorAll(`#svg-lines .${classe}`).forEach(p => p.remove());
}

function atualizarSVGSize() {
    const tree = document.getElementById('org-tree');
    const svg  = document.getElementById('svg-lines');
    if (!tree || !svg) return;
    const h = Math.max(tree.offsetHeight, tree.scrollHeight);
    svg.style.width  = tree.offsetWidth  + 'px';
    svg.style.height = h + 'px';
    svg.setAttribute('viewBox', `0 0 ${tree.offsetWidth} ${h}`);
}

let _resizeTimer = null;
const _ro = new ResizeObserver(() => {
    clearTimeout(_resizeTimer);
    _resizeTimer = setTimeout(() => {
        atualizarSVGSize();
        if (_gestorVisivel)  { desenharLinhaAdminGestor(); desenharLinhaGestorUsuario(); }
        if (_usuarioVisivel) desenharLinhasUsuarioColegas();
    }, 80);
});

document.addEventListener('DOMContentLoaded', () => {
    const tree = document.getElementById('org-tree');
    if (tree) _ro.observe(tree);
});

function iniciarZoom() {
    const root    = document.getElementById('organograma-root');
    const wrapper = document.getElementById('org-zoom-wrapper');
    let scale     = 1;
    const MIN = 0.25, MAX = 2.5, STEP = 0.15;

    function aplicar(novo) {
        scale  = Math.min(MAX, Math.max(MIN, +novo.toFixed(2)));
        _scale = scale;
        wrapper.style.transform       = `scale(${scale})`;
        wrapper.style.transformOrigin = 'top center';
        root.style.height = (wrapper.scrollHeight * scale) + 'px';
    }

    if (!document.getElementById('org-zoom-controls')) {
        const controls = document.createElement('div');
        controls.id = 'org-zoom-controls';
        controls.innerHTML = `
            <button id="btn-zoom-in"  title="Aproximar">+</button>
            <button id="btn-zoom-out" title="Afastar">−</button>
        `;
        root.appendChild(controls);
    }

    document.getElementById('btn-zoom-in') .addEventListener('click', () => aplicar(scale + STEP));
    document.getElementById('btn-zoom-out').addEventListener('click', () => aplicar(scale - STEP));
}