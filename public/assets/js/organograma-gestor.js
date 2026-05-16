/*
|--------------------------------------------------------------------------
| ORGANOGRAMA GESTOR
|--------------------------------------------------------------------------
| Fluxo fixo:
|
| ADMIN
|   ↓
| GESTOR (logado)
|   ↓
| COLABORADORES DA UNIDADE
|--------------------------------------------------------------------------
*/

const SVG_NS = "http://www.w3.org/2000/svg";

let _scale = 1;
let _dados = null;

document.addEventListener("DOMContentLoaded", () => {
    carregarOrganogramaGestor();
});

async function carregarOrganogramaGestor() {

    try {

        const [hierarquiaRes, colaboradoresRes] = await Promise.all([
            fetch("/api/organograma/hierarquia-usuario"),
            fetch("/api/colaboradores")
        ]);

        const hierarquiaJson   = await hierarquiaRes.json();
        const colaboradoresJson = await colaboradoresRes.json();

        _dados = hierarquiaJson.data;

        const gestorId =
            _dados.usuario?.id ||
            _dados.gestor?.id;

        const colaboradores = (colaboradoresJson.data || []).filter(c =>
            c.id !== gestorId &&
            c.perfil_raw !== 'admin' &&
            c.perfil_raw !== 'gestor'
        );

        renderizarOrganograma(
            _dados,
            colaboradores
        );

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

function renderizarOrganograma(dados, colaboradores) {

    const root = document.getElementById("organograma-root");

    const admin  = dados.admin;
    const gestor = dados.usuario;

    root.innerHTML = `
        <div id="org-zoom-wrapper">

            <div id="org-tree" class="gestor-organograma">

                <svg id="svg-lines"></svg>

                ${
                    admin ? `
                        <div class="org-level level-admin">

                            <div class="org-node" id="node-admin-${admin.id}">
                                ${cardHTML(admin, "admin")}
                            </div>

                        </div>
                    ` : ""
                }

                <div class="org-level">

                    <div class="org-node" id="node-gestor-${gestor.id}">
                        ${cardHTML(gestor, "gestor", true)}
                    </div>

                </div>

                <div class="org-colegas-titulo">
                    <i class="fas fa-users"></i>
                    Equipe da Unidade
                </div>

                <div class="org-level org-level-colegas">

                    ${colaboradores.map(colaborador => `
                        
                        <div class="org-node" id="node-user-${colaborador.id}">
                            ${cardHTML(colaborador, "user")}
                        </div>

                    `).join("")}

                </div>

            </div>

        </div>
    `;

    iniciarZoom();

    setTimeout(() => {

        atualizarSVGSize();

        desenharLinhas(
            admin,
            gestor,
            colaboradores
        );

    }, 400);
}

function cardHTML(pessoa, tipo, eu = false) {
    

    const nome = formatarNome(pessoa.nome);

    const avatar = pessoa.avatar
        ? `
            <img
                src="${pessoa.avatar}"
                alt="${nome}"
                class="avatar-foto"
                onerror="this.parentElement.innerHTML=avatarIniciais('${nome}','${pessoa.cor ?? '#1E3A8A'}')"
            >
        `
        : avatarIniciais(
            nome,
            pessoa.cor ?? "#1E3A8A"
        );

    const podeAbrirPerfil = tipo !== 'admin';

    return `
        <div class="person-card ${tipo} ${eu ? "is-me" : ""}">

            <div
                class="avatar-ring ${!podeAbrirPerfil ? 'perfil-bloqueado' : ''}"
                ${podeAbrirPerfil
                    ? `onclick="abrirPerfilServidor(${pessoa.id})"`
                    : ''
                }
                style="cursor:${podeAbrirPerfil ? 'pointer' : 'default'}"
            >
                ${avatar}
            </div>

            <div class="person-name">
                ${nome}
                ${eu ? '<span class="me-badge">Você</span>' : ""}
            </div>

            <div class="person-role">
                ${pessoa.cargo_label || pessoa.cargo || "Servidor"}
            </div>

        </div>
    `;
}

function desenharLinhas(admin, gestor, colaboradores) {

    limparLinhas("org-line");

    /*
    |--------------------------------------------------------------------------
    | ADMIN → GESTOR
    |--------------------------------------------------------------------------
    */

    if (admin) {

        const adminNode  = document.getElementById(`node-admin-${admin.id}`);
        const gestorNode = document.getElementById(`node-gestor-${gestor.id}`);

        if (adminNode && gestorNode) {

            const r1 = getRect(adminNode);
            const r2 = getRect(gestorNode);

            criarCurvaBezier(
                r1.cx,
                r1.bot + 2,
                r2.cx,
                r2.top - 2,
                "org-line"
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GESTOR → COLABORADORES
    |--------------------------------------------------------------------------
    */

    const gestorNode = document.getElementById(`node-gestor-${gestor.id}`);

    if (!gestorNode) return;

    const gestorRect = getRect(gestorNode);

    colaboradores.forEach((user, index) => {

        const userNode = document.getElementById(`node-user-${user.id}`);

        if (!userNode) return;

        const userRect = getRect(userNode);

        criarCurvaBezier(
            gestorRect.cx,
            gestorRect.bot + 2,
            userRect.cx,
            userRect.top - 2,
            "org-line",
            index * 0.04
        );
    });
}

function formatarNome(nomeCompleto) {

    if (!nomeCompleto || typeof nomeCompleto !== "string") {
        return "—";
    }

    const partes = nomeCompleto.trim().split(" ");

    return partes.length === 1
        ? partes[0]
        : `${partes[0]} ${partes[partes.length - 1]}`;
}

function avatarIniciais(nome, cor) {

    const partes = nome.trim().split(" ").filter(Boolean);

    const iniciais = partes.length >= 2
        ? partes[0][0].toUpperCase() +
          partes[partes.length - 1][0].toUpperCase()
        : (partes[0]?.[0] ?? "?").toUpperCase();

    return `
        <span
            class="avatar-iniciais"
            style="background:${cor}"
        >
            ${iniciais}
        </span>
    `;
}

function getRect(el) {

    const treeRect = document
        .getElementById("org-tree")
        .getBoundingClientRect();

    const rect = el.getBoundingClientRect();

    return {
        cx:  (rect.left - treeRect.left + rect.width / 2) / _scale,
        top: (rect.top  - treeRect.top) / _scale,
        bot: (rect.bottom - treeRect.top) / _scale,
    };
}

function criarCurvaBezier(
    x1,
    y1,
    x2,
    y2,
    classe,
    delay = 0
) {

    const svg = document.getElementById("svg-lines");

    const midY = (y1 + y2) / 2;

    const d = `
        M ${x1} ${y1}
        C ${x1} ${midY},
          ${x2} ${midY},
          ${x2} ${y2}
    `;

    const path = document.createElementNS(SVG_NS, "path");

    path.setAttribute("d", d);

    path.classList.add("svg-conn", classe);

    svg.appendChild(path);

    const len = path.getTotalLength();

    path.style.strokeDasharray  = len;
    path.style.strokeDashoffset = len;

    path.getBoundingClientRect();

    path.style.transition =
        `stroke-dashoffset .55s ease ${delay}s`;

    path.style.strokeDashoffset = "0";

    return path;
}

function limparLinhas(classe) {

    document
        .querySelectorAll(`#svg-lines .${classe}`)
        .forEach(l => l.remove());
}

function atualizarSVGSize() {

    const tree = document.getElementById("org-tree");
    const svg  = document.getElementById("svg-lines");

    if (!tree || !svg) return;

    svg.style.width  = tree.offsetWidth + "px";
    svg.style.height = tree.scrollHeight + "px";
}

function iniciarZoom() {

    const root    = document.getElementById("organograma-root");
    const wrapper = document.getElementById("org-zoom-wrapper");

    let scale = 1;

    const MIN  = 0.25;
    const MAX  = 2.5;
    const STEP = 0.1;

    function aplicar(novo) {

        scale = Math.min(
            MAX,
            Math.max(MIN, +novo.toFixed(2))
        );

        _scale = scale;

        wrapper.style.transform = `scale(${scale})`;

        root.style.height =
            (wrapper.scrollHeight * scale) + "px";
    }

    root.addEventListener("wheel", e => {

        e.preventDefault();

        aplicar(
            scale + (e.deltaY < 0 ? STEP : -STEP)
        );

    }, { passive:false });
}