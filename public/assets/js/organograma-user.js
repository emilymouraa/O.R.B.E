let _dadosUser = null;

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
        const colegas = (jsonCol.data ?? []).filter(c => c.id !== _dadosUser.usuario.id);
        renderBottomUp(_dadosUser, colegas);
    } catch (e) {
        document.getElementById('organograma-root').innerHTML =
            `<p style="text-align:center;color:var(--text-secondary)">
                <i class="fas fa-circle-exclamation"></i> Erro ao carregar organograma.
             </p>`;
    }
}

function renderBottomUp(dados, colegas = []) {
    const container = document.getElementById('organograma-root');
    const hint      = document.getElementById('org-hint');
    if (hint) hint.style.display = 'none';

    const niveis = [];
    if (dados.admin)  niveis.push({ dados: dados.admin,  tipo: 'admin' });
    if (dados.gestor) niveis.push({ dados: dados.gestor, tipo: 'gestor' });
    niveis.push({ dados: dados.usuario, tipo: 'user' });

    // Seção de colegas (sem modal, sem click)
    const colegasHTML = colegas.length ? `
        <div class="org-colegas-section">
            <div class="org-colegas-titulo">
                <i class="fas fa-users"></i> Colegas de Unidade
            </div>
            <div class="org-level org-level-colegas">
                ${colegas.map(c => `
                    <div class="org-node">
                        <div class="person-card user" style="cursor:default">
                            <div class="avatar-ring" style="cursor:default">
                                ${avatarIniciais(formatarNome(c.nome), '#6B7280')}
                            </div>
                            <div class="person-name">${formatarNome(c.nome)}</div>
                            <div class="person-role">${c.cargo ?? '—'}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    ` : '';

    container.innerHTML = `
        <div id="org-zoom-wrapper">
            <div id="org-tree">
                <svg id="svg-lines"></svg>
                ${niveis.map(({ dados: p, tipo }, i) => `
                    <div class="org-level ${i === 0 ? 'level-admin' : ''}" id="level-${tipo}">
                        <div class="org-node" id="node-${tipo}-${p.id}">
                            ${cardUserHTML(p, tipo)}
                        </div>
                    </div>
                `).join('')}
                ${colegas.length ? `
                    <div class="org-colegas-titulo">
                        <i class="fas fa-users"></i> Colegas de Unidade
                    </div>
                    <div class="org-level org-level-colegas" id="level-colegas">
                        ${colegas.map(c => `
                            <div class="org-node">
                                <div class="person-card user" style="cursor:default">
                                    <div class="avatar-ring" style="cursor:default">
                                        ${avatarIniciais(formatarNome(c.nome), '#6B7280')}
                                    </div>
                                    <div class="person-name">${formatarNome(c.nome)}</div>
                                    <div class="person-role">${c.cargo ?? '—'}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        </div>
    `;

    iniciarZoom();

    setTimeout(() => {
        atualizarSVGSize();
        desenharLinhasBottomUp(dados, niveis);
        atualizarSVGSize();
    }, 1000);
}

function cardUserHTML(pessoa, tipo) {
    const nome = formatarNome(pessoa.nome);
    const avatarInner = pessoa.avatar
        ? `<img src="${pessoa.avatar}" alt="${nome}" class="avatar-foto"
               onerror="this.parentElement.innerHTML=avatarIniciais('${nome}','${pessoa.cor ?? '#1E3A8A'}')">`
        : avatarIniciais(nome, pessoa.cor ?? '#1E3A8A');

    const isMe      = tipo === 'user';
    const clickAttr = isMe
        ? `onclick="abrirPerfilServidor(${pessoa.servidor_id})" title="Ver meu perfil" style="cursor:pointer"`
        : `style="cursor:default"`;

    return `
        <div class="person-card ${tipo} ${isMe ? 'is-me' : ''}" id="card-${tipo}-${pessoa.id}">
            <div class="avatar-ring" ${clickAttr}>
                ${avatarInner}
            </div>
            <div class="person-name">${nome}${isMe ? ' <span class="me-badge">Você</span>' : ''}</div>
            <div class="person-role">${pessoa.cargo_label}</div>
        </div>
    `;
}   

function desenharLinhasBottomUp(dados, niveis) {
    limparLinhas('org-line');

    // Linhas da hierarquia (admin → gestor → você)
    for (let i = 0; i < niveis.length - 1; i++) {
        const de  = document.getElementById(`node-${niveis[i].tipo}-${niveis[i].dados.id}`);
        const ate = document.getElementById(`node-${niveis[i+1].tipo}-${niveis[i+1].dados.id}`);
        if (!de || !ate) continue;
        const r1 = getRect(de);
        const r2 = getRect(ate);
        criarCurvaBezier(r1.cx, r1.bot + 2, r2.cx, r2.top - 2, 'org-line', i * 0.1);
    }

    const userNode = document.getElementById(`node-user-${dados.usuario.id}`);
    if (!userNode) return;
    const uRect = getRect(userNode);


    document.querySelectorAll('#level-colegas .org-node').forEach((cNode, i) => {
        const cRect = getRect(cNode);
        criarCurvaBezier(
            uRect.cx, uRect.bot + 2,
            cRect.cx, cRect.top - 2,
            'org-line',
            0.3 + i * 0.04
        );
    });
}

const SVG_NS = 'http://www.w3.org/2000/svg';
let _scale = 1;

function formatarNome(nomeCompleto) {
    if (!nomeCompleto || typeof nomeCompleto !== 'string') return '—';
    const p = nomeCompleto.trim().split(' ');
    return p.length === 1 ? p[0] : `${p[0]} ${p[p.length - 1]}`;
}

function avatarIniciais(nome, cor) {
    const partes   = nome.trim().split(' ').filter(Boolean);
    const iniciais = partes.length >= 2
        ? partes[0][0].toUpperCase() + partes[partes.length - 1][0].toUpperCase()
        : (partes[0]?.[0] ?? '?').toUpperCase();
    return `<span class="avatar-iniciais" style="background:${cor}">${iniciais}</span>`;
}

function getRect(el) {
    const tree     = document.getElementById('org-tree');
    const treeRect = tree.getBoundingClientRect();
    const elRect   = el.getBoundingClientRect();
    return {
        cx:  (elRect.left - treeRect.left + elRect.width  / 2) / _scale,
        cy:  (elRect.top  - treeRect.top  + elRect.height / 2) / _scale,
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

function iniciarZoom() {
    const root    = document.getElementById('organograma-root');
    const wrapper = document.getElementById('org-zoom-wrapper');
    let scale     = 1;
    const MIN = 0.25, MAX = 2.5, STEP = 0.1;
    function aplicar(novo) {
        scale = Math.min(MAX, Math.max(MIN, +novo.toFixed(2)));
        _scale = scale;
        wrapper.style.transform = `scale(${scale})`;
        root.style.height = (wrapper.scrollHeight * scale) + 'px';
    }
    root.addEventListener('wheel', e => {
        e.preventDefault();
        aplicar(scale + (e.deltaY < 0 ? STEP : -STEP));
    }, { passive: false });
    let lastDist = null;
    root.addEventListener('touchmove', e => {
        if (e.touches.length !== 2) return;
        e.preventDefault();
        const dx   = e.touches[0].clientX - e.touches[1].clientX;
        const dy   = e.touches[0].clientY - e.touches[1].clientY;
        const dist = Math.hypot(dx, dy);
        if (lastDist) aplicar(scale * (dist / lastDist));
        lastDist = dist;
    }, { passive: false });
    root.addEventListener('touchend', () => { lastDist = null; });
}

const style = document.createElement('style');
style.textContent = `
    .is-me { box-shadow: 0 0 0 3px var(--primary, #3b82f6); }
    .me-badge {
        display: inline-block;
        background: var(--primary, #3b82f6);
        color: #fff;
        font-size: .65rem;
        font-weight: 700;
        padding: .1rem .4rem;
        border-radius: 999px;
        margin-left: .35rem;
        vertical-align: middle;
    }
    .org-connector-hint {
        text-align: center;
        color: var(--text-secondary, #64748b);
        font-size: .85rem;
        padding: .25rem 0;
    }
`;
document.head.appendChild(style);

style.textContent = `
    .is-me { box-shadow: 0 0 0 3px var(--primary, #3b82f6); }
    .me-badge {
        display: inline-block;
        background: var(--primary, #3b82f6);
        color: #fff;
        font-size: .65rem;
        font-weight: 700;
        padding: .1rem .4rem;
        border-radius: 999px;
        margin-left: .35rem;
        vertical-align: middle;
    }
    .org-colegas-titulo {
        width: 100%;
        text-align: center;
        font-size: .82rem;
        font-weight: 600;
        color: var(--text-secondary, #64748b);
        margin-top: 48px;
        padding-top: 24px;
        border-top: 1px dashed var(--border, #e2e8f0);
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .org-level-colegas {
        padding-top: 12px !important;
    }
`;