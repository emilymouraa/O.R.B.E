/*
| - Admin centralizado, flutuando suave
| - Gestores "explodem" de dentro do admin com stagger delay
| - Usuários surgem do gestor clicado
| - Linhas Bézier cúbicas animadas (stroke-dashoffset)
| - Zoom via scroll/pinch mantido
*/

const SVG_NS   = "http://www.w3.org/2000/svg";
let _adminData = null;
let _gestoresExpanded  = false;
let _expandedGestorId  = null;
let _scale = 1;

document.addEventListener("DOMContentLoaded", () => {
  carregarAdmin();
});

async function carregarAdmin() {
  const response = await fetch('/api/organograma/hierarquia-usuarios');
  const json     = await response.json();
  _adminData     = json.data;
  renderOrganograma(_adminData);

  // Restaura estado salvo sem animações
  const est = StateManager.load('/organograma');
  if (est?.gestoresExpanded) {
    await _restaurarEstadoSemAnimacao(est);
  }
}

async function _restaurarEstadoSemAnimacao(est) {
  const tree       = document.getElementById("org-tree");
  const levelGest  = document.getElementById("level-gestores");
  const levelUsers = document.getElementById("level-usuarios");
  const adminCard  = document.getElementById(`card-admin-${_adminData.id}`);
  const badge      = adminCard?.querySelector(".expand-badge");
  _gestoresExpanded = true;
  tree.classList.add("expanded");
  if (badge) badge.textContent = "−";

  StateManager.save('/organograma', {
    gestoresExpanded: true,
    expandedGestorId: null,
  });

  const gestores = _adminData.children;
  levelGest.innerHTML = gestores.map((g, i) => `
    <div class="org-node" id="node-gestor-${g.id}">
      ${cardHTML(g, "gestor")}
    </div>
  `).join("");
  levelGest.classList.remove("hidden");

  gestores.forEach(g => {
    document.getElementById(`card-gestor-${g.id}`)
      ?.addEventListener("click", () => toggleUsers(g.id));
  });
  if (est.expandedGestorId !== null) {
    const gestorId   = est.expandedGestorId;
    const gestorCard = document.getElementById(`card-gestor-${gestorId}`);
    const gBadge     = gestorCard?.querySelector(".expand-badge");

    _expandedGestorId = gestorId;
    gestorCard?.classList.add("expanded");
    if (gBadge) gBadge.textContent = "−";

    const res   = await fetch(`/api/organograma/hierarquia-usuarios/gestor?gestor_id=${gestorId}`);
    const json  = await res.json();
    const users = json.data.children;

    levelUsers.innerHTML = users.map(u => `
      <div class="org-node" id="node-user-${u.id}">
        ${cardHTML(u, "user")}
      </div>
    `).join("");
    levelUsers.classList.remove("hidden");
  }
  setTimeout(() => {
    atualizarSVGSize();
    desenharLinhasParaGestores();
    if (_expandedGestorId !== null) {
      const levU  = document.getElementById("level-usuarios");
      const users = Array.from(levU.querySelectorAll(".org-node")).map(n => ({
        id: parseInt(n.id.replace("node-user-", ""))
      }));
      desenharLinhasParaUsuarios(_expandedGestorId, users);
    }
  }, 100);
}

function renderOrganograma(admin) {
  const container = document.getElementById("organograma-root");
  container.innerHTML = `
    <div id="org-zoom-wrapper">
      <div id="org-tree">
        <svg id="svg-lines"></svg>

        <div class="org-level level-admin" id="level-admin">
          <div class="org-node" id="node-admin-${admin.id}">
            ${cardHTML(admin, "admin")}
          </div>
        </div>

        <div class="org-level hidden" id="level-gestores"></div>
        <div class="org-level hidden" id="level-usuarios"></div>
      </div>
    </div>
  `;

  iniciarZoom();
  atualizarSVGSize();

  document.getElementById(`card-admin-${admin.id}`)
    .addEventListener("click", toggleGestores);
}

function toggleGestores() {
  const adminCard  = document.getElementById(`card-admin-${_adminData.id}`);
  const tree       = document.getElementById("org-tree");
  const hint       = document.getElementById("org-hint");
  const levelGest  = document.getElementById("level-gestores");
  const levelUsers = document.getElementById("level-usuarios");
  const badge      = adminCard.querySelector(".expand-badge");

  if (_gestoresExpanded) {
    collapseAll(() => {
      _gestoresExpanded = false;
      _expandedGestorId = null;
      tree.classList.remove("expanded");
      if (badge) badge.textContent = "+";
      if (hint) hint.style.display = "block";
      StateManager.clear('/organograma');
    });
    return;
  }

  adminCard.classList.add("pulsing");
  setTimeout(() => adminCard.classList.remove("pulsing"), 600);

  if (hint) hint.style.display = "none";
  _gestoresExpanded = true;
  tree.classList.add("expanded");
  if (badge) badge.textContent = "−";

  const gestores = _adminData.children;
  levelGest.innerHTML = gestores.map((g, i) => `
    <div class="org-node" id="node-gestor-${g.id}" style="animation-delay:${i * 0.06}s">
      ${cardHTML(g, "gestor")}
    </div>
  `).join("");
  levelGest.classList.remove("hidden");

  gestores.forEach(g => {
    document.getElementById(`card-gestor-${g.id}`)
      .addEventListener("click", () => toggleUsers(g.id));
  });

  setTimeout(() => {
    atualizarSVGSize();
    desenharLinhasParaGestores();
  }, 500);
}

async function toggleUsers(gestorId) {
  const gestorCard = document.getElementById(`card-gestor-${gestorId}`);
  const levelUsers = document.getElementById("level-usuarios");
  const badge      = gestorCard?.querySelector(".expand-badge");

  if (_expandedGestorId === gestorId) {
    limparLinhas("gestor-line");
    const nodes = levelUsers.querySelectorAll(".org-node");
    nodes.forEach(n => n.classList.add("leaving"));
    setTimeout(() => {
      levelUsers.classList.add("hidden");
      levelUsers.innerHTML = "";
      _expandedGestorId = null;
      atualizarSVGSize();
      StateManager.save('/organograma', {
        gestoresExpanded: true,
        expandedGestorId: null,
      });
    }, 300);
    gestorCard?.classList.remove("expanded");
    if (badge) badge.textContent = "+";
    return;
  }

  if (_expandedGestorId !== null) {
    const prevCard  = document.getElementById(`card-gestor-${_expandedGestorId}`);
    const prevBadge = prevCard?.querySelector(".expand-badge");
    prevCard?.classList.remove("expanded");
    if (prevBadge) prevBadge.textContent = "+";
  }

  limparLinhas("gestor-line");

  _expandedGestorId = gestorId;
  gestorCard?.classList.add("expanded");
  if (badge) badge.textContent = "−";

  const response = await fetch(`/api/organograma/hierarquia-usuarios/gestor?gestor_id=${gestorId}`);
  const json     = await response.json();
  const users    = json.data.children;

  levelUsers.innerHTML = users.map((u, i) => `
    <div class="org-node" id="node-user-${u.id}" style="animation-delay:${i * 0.07}s">
      ${cardHTML(u, "user")}
    </div>
  `).join("");
  levelUsers.classList.remove("hidden");
  StateManager.save('/organograma', {
    gestoresExpanded: true,
    expandedGestorId: gestorId,
  });
  setTimeout(() => {
    atualizarSVGSize();
    desenharLinhasParaUsuarios(gestorId, users);
    desenharLinhasParaGestores();
  }, 500);
}

function cardHTML(pessoa, tipo) {
  const nome  = formatarNome(pessoa.nome);
  const badge = (tipo !== "user")
    ? `<div class="expand-badge">+</div>`
    : "";
 
  const avatarInner = pessoa.avatar
    ? `<img
         src="${pessoa.avatar}"
         alt="${nome}"
         class="avatar-foto"
         onerror="this.parentElement.innerHTML=avatarIniciais('${nome}','${pessoa.cor ?? '#1E3A8A'}')"
       >`
    : avatarIniciais(nome, pessoa.cor ?? '#1E3A8A');
 
  return `
    <div class="person-card ${tipo}" id="card-${tipo}-${pessoa.id}" data-expanded="false">
      <div class="avatar-ring ${tipo !== 'user' ? '' : ''}" 
        ${tipo === 'user' || tipo === 'gestor' || tipo === 'admin' ? `onclick="event.stopPropagation(); abrirPerfilServidor(${pessoa.servidor_id})" title="Ver perfil completo"` : ''}
        style="cursor:pointer;">
        ${avatarInner}
      </div>
      <div class="person-name">${nome}</div>
      <div class="person-role">${pessoa.cargo_label}</div>
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
  if (!nomeCompleto || typeof nomeCompleto !== "string") return "—";
  const p = nomeCompleto.trim().split(" ");
  return p.length === 1 ? p[0] : `${p[0]} ${p[p.length - 1]}`;
}

function getRect(el) {
  const tree     = document.getElementById("org-tree");
  const treeRect = tree.getBoundingClientRect();
  const elRect   = el.getBoundingClientRect();

  return {
    cx:  (elRect.left - treeRect.left + elRect.width  / 2) / _scale,
    cy:  (elRect.top  - treeRect.top  + elRect.height / 2) / _scale,
    top: (elRect.top  - treeRect.top) / _scale,
    bot: (elRect.bottom - treeRect.top) / _scale,
    w:   elRect.width  / _scale,
    h:   elRect.height / _scale,
  };
}

function criarCurvaBezier(x1, y1, x2, y2, classe, delay = 0) {
  const svg   = document.getElementById("svg-lines");
  const midY  = (y1 + y2) / 2;
  const d = `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`;

  const path = document.createElementNS(SVG_NS, "path");
  path.setAttribute("d", d);
  path.classList.add("svg-conn", classe);
  svg.appendChild(path);

  const len = path.getTotalLength();
  path.style.strokeDasharray  = len;
  path.style.strokeDashoffset = len;
  path.getBoundingClientRect(); /* força reflow */
  path.style.transition = `stroke-dashoffset 0.55s cubic-bezier(0.4,0,0.2,1) ${delay}s`;
  path.style.strokeDashoffset = "0";

  return path;
}

function limparLinhas(classe) {
  document.querySelectorAll(`#svg-lines .${classe}`).forEach(p => p.remove());
}

function desenharLinhasParaGestores() {
  limparLinhas("admin-line");
  const adminNode = document.getElementById(`node-admin-${_adminData.id}`);
  if (!adminNode) return;
  const aRect = getRect(adminNode);

  document.querySelectorAll("#level-gestores .org-node").forEach((gNode, i) => {
    const gRect = getRect(gNode);
    criarCurvaBezier(
      aRect.cx, aRect.bot + 2,
      gRect.cx, gRect.top - 2,
      "admin-line",
      i * 0.045
    );
  });
}

function desenharLinhasParaUsuarios(gestorId, users) {
  limparLinhas("gestor-line");
  const gestorNode = document.getElementById(`node-gestor-${gestorId}`);
  if (!gestorNode) return;
  const gRect = getRect(gestorNode);

  users.forEach((u, i) => {
    const uNode = document.getElementById(`node-user-${u.id}`);
    if (!uNode) return;
    const uRect = getRect(uNode);
    criarCurvaBezier(
      gRect.cx, gRect.bot + 2,
      uRect.cx, uRect.top - 2,
      "gestor-line",
      i * 0.05
    );
  });
}

function collapseAll(callback) {
  limparLinhas("admin-line");
  limparLinhas("gestor-line");

  const levelG = document.getElementById("level-gestores");
  const levelU = document.getElementById("level-usuarios");

  [levelG, levelU].forEach(level => {
    level.querySelectorAll(".org-node").forEach(n => n.classList.add("leaving"));
  });

  setTimeout(() => {
    levelG.classList.add("hidden"); levelG.innerHTML = "";
    levelU.classList.add("hidden"); levelU.innerHTML = "";
    atualizarSVGSize();
    if (callback) callback();
  }, 310);
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
    if (_gestoresExpanded) {
      desenharLinhasParaGestores();
      if (_expandedGestorId !== null) {
        const g    = _adminData.children.find(x => x.id === _expandedGestorId);
        const levU = document.getElementById("level-usuarios");
        if (g && !levU.classList.contains("hidden")) {
          const users = Array.from(levU.querySelectorAll(".org-node")).map(n => {
            const uid = parseInt(n.id.replace("node-user-", ""));
            return { id: uid };
          });
          desenharLinhasParaUsuarios(_expandedGestorId, users);
        }
      }
    }
  }, 80);
});

document.addEventListener("DOMContentLoaded", () => {
  const tree = document.getElementById("org-tree");
  if (tree) _ro.observe(tree);
  atualizarSVGSize();
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