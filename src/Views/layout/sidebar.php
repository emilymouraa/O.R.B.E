<?php
/*
 * Views/layout/sidebar.php
 * Sidebar de navegação do sistema ORBE.
 * Incluir APENAS nas páginas autenticadas (após o header.php).
 * Requer que $_SESSION['user'] esteja definido.
 * A rota ativa é detectada automaticamente via $_SERVER['REQUEST_URI'].
 */
$currentPath = strtok($_SERVER['REQUEST_URI'], '?');

$role     = $_SESSION['user']['role'] ?? 'user';
$navItems = [
    [
        'href'  => '/painel',
        'icon'  => 'fa-chart-line',
        'label' => 'Painel',
        'roles' => ['admin', 'gestor'],
    ],
    [
        'href'  => '/usuarios',
        'icon'  => 'fa-users',
        'label' => 'Usuários',
        'roles' => ['admin'],
    ],
    [
        'href'  => '/colaboradores',
        'icon'  => 'fa-users',
        'label' => 'Colaboradores',
        'roles' => ['user', 'gestor'],
    ],
    [
        'href'  => '/organograma',
        'icon'  => 'fa-sitemap',
        'label' => 'Organograma',
        'roles' => ['admin', 'gestor', 'user'],
    ],
    [
        'href'  => '/competencias',
        'icon'  => 'fa-star',
        'label' => 'Competências',
        'roles' => ['admin', 'gestor', 'user'],
    ],
    [
        'href'  => '/banco-talentos',
        'icon'  => 'fa-briefcase',
        'label' => 'Banco de Talentos',
        'roles' => ['admin', 'gestor'],
    ],
    [
        'href'  => '/perfil',
        'icon'  => 'fa-user-circle',
        'label' => 'Meu Perfil',
        'roles' => ['admin', 'gestor', 'user'],
        'notif' => true,   /* <-- flag: este item recebe a bolinha */
    ],
];
?>

<aside class="sidebar" id="sidebar">

    <!-- ── Logo sempre no topo, centralizada ── -->
    <div class="sidebar-logo-wrap">
        <img src="/assets/images/orbe_logo.png" alt="ORBE" class="sidebar-logo">
    </div>

    <!-- ── Hambúrguer sempre abaixo da logo ── -->
    <div class="sidebar-toggle-wrap">
        <button class="sidebar-toggle" id="sidebarToggle" title="Expandir / Recolher menu" aria-label="Alternar sidebar">
            <span class="bar bar-top"></span>
            <span class="bar bar-mid"></span>
            <span class="bar bar-bot"></span>
        </button>
    </div>

    <!-- ── Itens de navegação ── -->
    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item):
            if (!in_array($role, $item['roles'])) continue;
            $active = ($currentPath === $item['href']) ? 'active' : '';
        ?>
        <a href="<?= $item['href'] ?>"
           class="sidebar-link <?= $active ?>"
           title="<?= htmlspecialchars($item['label']) ?>"
           <?= !empty($item['notif']) ? 'id="sidebarPerfilLink"' : '' ?>>

            <!-- Ícone com posição relativa para a bolinha flutuar sobre ele -->
            <span class="sidebar-icon-wrap" style="position:relative;display:inline-flex;align-items:center;justify-content:center">
                <i class="fas <?= $item['icon'] ?> sidebar-icon"></i>
                <?php if (!empty($item['notif'])): ?>
                <!-- Bolinha: oculta por padrão, JS acende quando nao_lidas > 0 -->
                <span id="sidebarNotifDot"
                      style="display:none;
                             position:absolute;
                             top:-.25rem;
                             right:-.35rem;
                             width:.55rem;
                             height:.55rem;
                             background:#ef4444;
                             border-radius:50%;
                             border:2px solid var(--sidebar-bg, #1a1a2e);
                             pointer-events:none;
                             z-index:10">
                </span>
                <?php endif; ?>
            </span>

            <span class="sidebar-label"><?= htmlspecialchars($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- ── Logout no rodapé ── -->
    <div class="sidebar-footer">
        <form method="POST" action="/logout" id="formLogout">
            <button type="submit" class="sidebar-link sidebar-logout" title="Sair" id="btnLogout">
                <i class="fas fa-sign-out-alt sidebar-icon"></i>
                <span class="sidebar-label">Sair</span>
            </button>
        </form>
    </div>

</aside>

<script>
(function SidebarNotif() {

    const POLL_MS  = 30_000;
    const dot      = document.getElementById('sidebarNotifDot');

    if (!dot) return;

    function _aplicar(naoLidas) {
        dot.style.display = naoLidas > 0 ? 'block' : 'none';
    }

    async function _buscar() {
        if (typeof window.__notifNaoLidas === 'number') {
            _aplicar(window.__notifNaoLidas);
            return;
        }
        try {
            const res  = await fetch(BASE_URL + '/api/notificacoes/count');
            if (!res.ok) return;
            const json = await res.json();
            _aplicar(json.nao_lidas ?? 0);
        } catch (e) {
        }
    }

    function _iniciar() {
        _buscar();
        setInterval(_buscar, POLL_MS);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _iniciar);
    } else {
        _iniciar();
    }

}());
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const formLogout = document.getElementById('formLogout');
    if (formLogout) {
        formLogout.addEventListener('submit', () => {
            StateManager.clearAll();
        });
    }
});
</script>