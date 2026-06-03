const SVG_NS = "http://www.w3.org/2000/svg";
let _scale  = 1;
let _dados  = null;
let _colaboradores = [];

let _gestorVisivel       = false;
let _colaboradoresVisiveis = false;

document.addEventListener("DOMContentLoaded", () => {
    carregarOrganogramaGestor();
});

async function carregarOrganogramaGestor() {
    try {
        const [hierarquiaRes, colaboradoresRes] = await Promise.all([
            fetch("/api/organograma/hierarquia-usuario"),
            fetch("/api/colaboradores")
        ]);
        const hierarquiaJson    = await hierarquiaRes.json();
        const colaboradoresJson = await colaboradoresRes.json();
        _dados = hierarquiaJson.data;

        const gestorId = _dados.usuario?.id;
        _colaboradores = (colaboradoresJson.data || []).filter(c =>
            c.id !== gestorId &&
            c.perfil_raw !== 'admin' &&
            c.perfil_raw !== 'gestor'
        );

        renderizarOrganograma(_dados);
        const est = StateManager.load('/organograma-gestor');
        if (est?.gestorVisivel || est?.colaboradoresVisiveis) {
            await _restaurarEstadoGestor(est);
        }
    } catch (e) {
        console.error(e);
        document.getElementById("organograma-root").innerHTML = `
            <div style="padding:40px;text-align:center;color:var(--text-muted)">
                <i class="fas fa-circle-exclamation"></i>
                Erro ao carregar organograma.
            </div>
        `;
    }
}

function renderizarOrganograma(dados) {
    const root   = document.getElementById("organograma-root");
    const gestor = dados.usuario;
    root.innerHTML = `
        <div id="org-zoom-wrapper">
            <div id="org-tree" class="gestor-organograma">
                <svg id="svg-lines"></svg>
                <div class="org-level" id="level-admin"></div>
                <div class="org-level level-gestor-centro" id="level-gestor">
                    <div class="org-node" id="node-gestor-${gestor.id}">
                        ${cardHTML(gestor, "gestor", true, true)}
                    </div>
                </div>
                <div class="org-level hidden" id="level-colaboradores"></div>
            </div>
        </div>
    `;
    iniciarZoom();
    atualizarSVGSize();
    document.getElementById(`card-gestor-${gestor.id}`)
        .addEventListener("click", toggleExpansao);
}

async function _restaurarEstadoGestor(est) {
    const admin      = _dados.admin;
    const gestor     = _dados.usuario;
    const tree       = document.getElementById("org-tree");
    const levelAdmin = document.getElementById("level-admin");
    const levelColab = document.getElementById("level-colaboradores");
    const gestorCard = document.getElementById(`card-gestor-${gestor.id}`);
    const gestorBadge = gestorCard?.querySelector(".expand-badge");

    _gestorVisivel         = true;
    _colaboradoresVisiveis = true;
    tree.classList.add("expanded");
    if (gestorBadge) gestorBadge.textContent = "−";

    levelAdmin.innerHTML = `
        <div class="org-node" id="node-admin-${admin.id}">
            ${cardHTML(admin, "admin", false, false)}
        </div>
    `;
    levelAdmin.classList.remove("hidden");

    levelColab.innerHTML = _colaboradores.map(c => `
        <div class="org-node" id="node-user-${c.id}">
            ${cardHTML(c, "user", false, false)}
        </div>
    `).join("");
    levelColab.classList.remove("hidden");

    setTimeout(() => {
        atualizarSVGSize();
        desenharLinhaAdminGestor();
        desenharLinhasGestorColaboradores();
    }, 100);
}

async function toggleExpansao() {
    const admin      = _dados.admin;
    const gestor     = _dados.usuario;
    const levelAdmin = document.getElementById("level-admin");
    const levelColab = document.getElementById("level-colaboradores");
    const gestorCard = document.getElementById(`card-gestor-${gestor.id}`);
    const badge      = gestorCard?.querySelector(".expand-badge");
    const tree       = document.getElementById("org-tree");

    // Colapsa tudo
    if (_gestorVisivel || _colaboradoresVisiveis) {
        limparLinhas("admin-line");
        limparLinhas("gestor-line");
        [levelAdmin, levelColab].forEach(level => {
            level.querySelectorAll(".org-node").forEach(n => n.classList.add("leaving"));
        });
        setTimeout(() => {
            levelAdmin.classList.add("hidden"); levelAdmin.innerHTML = "";
            levelColab.classList.add("hidden"); levelColab.innerHTML = "";
            _gestorVisivel         = false;
            _colaboradoresVisiveis = false;
            tree.classList.remove("expanded");
            if (badge) badge.textContent = "+";
            atualizarSVGSize();
            StateManager.clear('/organograma-gestor');
        }, 310);
        return;
    }

    // Expande admin (acima) + colaboradores (abaixo) de uma vez
    gestorCard.classList.add("pulsing");
    setTimeout(() => gestorCard.classList.remove("pulsing"), 600);

    _gestorVisivel         = true;
    _colaboradoresVisiveis = true;
    tree.classList.add("expanded");
    if (badge) badge.textContent = "−";

    // Injeta admin no level-admin (aparece acima no DOM)
    levelAdmin.innerHTML = `
        <div class="org-node" id="node-admin-${admin.id}">
            ${cardHTML(admin, "admin", false, false)}
        </div>
    `;
    levelAdmin.classList.remove("hidden");

    // Injeta colaboradores abaixo
    levelColab.innerHTML = _colaboradores.map((c, i) => `
        <div class="org-node" id="node-user-${c.id}" style="animation-delay:${i * 0.06}s">
            ${cardHTML(c, "user", false, false)}
        </div>
    `).join("");
    levelColab.classList.remove("hidden");

    StateManager.save('/organograma-gestor', {
        gestorVisivel: true,
        colaboradoresVisiveis: true,
    });

    setTimeout(() => {
        atualizarSVGSize();
        desenharLinhaAdminGestor();
        desenharLinhasGestorColaboradores();
    }, 500);
}

function desenharLinhaAdminGestor() {
    limparLinhas("admin-line");
    const admin  = _dados.admin;
    const gestor = _dados.usuario;
    const adminNode  = document.getElementById(`node-admin-${admin.id}`);
    const gestorNode = document.getElementById(`node-gestor-${gestor.id}`);
    if (!adminNode || !gestorNode) return;
    const rAdmin  = getRect(adminNode);
    const rGestor = getRect(gestorNode);
    criarCurvaBezier(rGestor.cx, rGestor.top - 2, rAdmin.cx, rAdmin.bot + 2, "admin-line");
}

function desenharLinhasGestorColaboradores() {
    limparLinhas("gestor-line");
    const gestor     = _dados.usuario;
    const gestorNode = document.getElementById(`node-gestor-${gestor.id}`);
    if (!gestorNode) return;
    const gRect = getRect(gestorNode);
    _colaboradores.forEach((c, i) => {
        const uNode = document.getElementById(`node-user-${c.id}`);
        if (!uNode) return;
        const uRect = getRect(uNode);
        criarCurvaBezier(gRect.cx, gRect.bot + 2, uRect.cx, uRect.top - 2, "gestor-line", i * 0.05);
    });
}

function cardHTML(pessoa, tipo, eu = false, expansivel = false) {
    const nome  = formatarNome(pessoa.nome);
    const badge = expansivel ? `<div class="expand-badge">+</div>` : "";

    const avatarInner = pessoa.avatar
        ? `<img src="${pessoa.avatar}" alt="${nome}" class="avatar-foto"
               onerror="this.parentElement.innerHTML=avatarIniciais('${nome}','${pessoa.cor ?? '#1E3A8A'}')">`
        : avatarIniciais(nome, pessoa.cor ?? "#1E3A8A");

    const podeAbrirPerfil = tipo !== "admin";

    return `
        <div class="person-card ${tipo} ${eu ? "is-me" : ""}" id="card-${tipo}-${pessoa.id}" data-expanded="false">
            <div class="avatar-ring"
                ${podeAbrirPerfil
                    ? `onclick="event.stopPropagation(); abrirPerfilServidor(${pessoa.servidor_id ?? pessoa.id})" title="Ver perfil completo" style="cursor:pointer"`
                    : `style="cursor:default"`
                }>
                ${avatarInner}
            </div>
            <div class="person-name">
                ${nome}
                ${eu ? '<span class="me-badge">Você</span>' : ""}
            </div>
            <div class="person-role">${pessoa.cargo_label || pessoa.cargo || "Servidor"}</div>
            ${badge}
        </div>
    `;
}

function avatarIniciais(nome, cor) {
    const partes   = nome.trim().split(" ").filter(Boolean);
    const iniciais = partes.length >= 2
        ? partes[0][0].toUpperCase() + partes[partes.length - 1][0].toUpperCase()
        : (partes[0]?.[0] ?? "?").toUpperCase();
    return `<span class="avatar-iniciais" style="background:${cor}">${iniciais}</span>`;
}

function formatarNome(nomeCompleto) {
    if (!nomeCompleto || typeof nomeCompleto !== "string") return "—";
    const p = nomeCompleto.trim().split(" ");
    return p.length === 1 ? p[0] : `${p[0]} ${p[p.length - 1]}`;
}

function getRect(el) {
    const treeRect = document.getElementById("org-tree").getBoundingClientRect();
    const rect     = el.getBoundingClientRect();
    return {
        cx:  (rect.left - treeRect.left + rect.width  / 2) / _scale,
        top: (rect.top  - treeRect.top) / _scale,
        bot: (rect.bottom - treeRect.top) / _scale,
    };
}

function criarCurvaBezier(x1, y1, x2, y2, classe, delay = 0) {
    const svg  = document.getElementById("svg-lines");
    const midY = (y1 + y2) / 2;
    const d    = `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`;
    const path = document.createElementNS(SVG_NS, "path");
    path.setAttribute("d", d);
    path.classList.add("svg-conn", classe);
    svg.appendChild(path);
    const len = path.getTotalLength();
    path.style.strokeDasharray  = len;
    path.style.strokeDashoffset = len;
    path.getBoundingClientRect();
    path.style.transition       = `stroke-dashoffset 0.55s cubic-bezier(0.4,0,0.2,1) ${delay}s`;
    path.style.strokeDashoffset = "0";
    return path;
}

function limparLinhas(classe) {
    document.querySelectorAll(`#svg-lines .${classe}`).forEach(p => p.remove());
}

function atualizarSVGSize() {
    const tree = document.getElementById("org-tree");
    const svg  = document.getElementById("svg-lines");
    if (!tree || !svg) return;
    svg.style.width  = tree.offsetWidth  + "px";
    svg.style.height = tree.scrollHeight + "px";
}

let _resizeTimer = null;
const _ro = new ResizeObserver(() => {
    clearTimeout(_resizeTimer);
    _resizeTimer = setTimeout(() => {
        atualizarSVGSize();
        if (_gestorVisivel)         desenharLinhaAdminGestor();
        if (_colaboradoresVisiveis) desenharLinhasGestorColaboradores();
    }, 80);
});

document.addEventListener("DOMContentLoaded", () => {
    const tree = document.getElementById("org-tree");
    if (tree) _ro.observe(tree);
});

function iniciarZoom() {
    const root    = document.getElementById("organograma-root");
    const wrapper = document.getElementById("org-zoom-wrapper");
    let scale     = 1;
    const MIN = 0.25, MAX = 2.5, STEP = 0.15;

    function aplicar(novo) {
        scale  = Math.min(MAX, Math.max(MIN, +novo.toFixed(2)));
        _scale = scale;
        wrapper.style.transform       = `scale(${scale})`;
        wrapper.style.transformOrigin = "top center";
        root.style.height = (wrapper.scrollHeight * scale) + "px";
    }

    if (!document.getElementById("org-zoom-controls")) {
        const controls = document.createElement("div");
        controls.id = "org-zoom-controls";
        controls.innerHTML = `
            <button id="btn-zoom-in"  title="Aproximar">+</button>
            <button id="btn-zoom-out" title="Afastar">−</button>
        `;
        root.appendChild(controls);
    }

    document.getElementById("btn-zoom-in") .addEventListener("click", () => aplicar(scale + STEP));
    document.getElementById("btn-zoom-out").addEventListener("click", () => aplicar(scale - STEP));
}