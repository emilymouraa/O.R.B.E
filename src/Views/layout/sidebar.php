<?php
/*
 * Views/layout/sidebar.php
 * Sidebar de navegação do sistema ORBE.
 * Incluir APENAS nas páginas autenticadas (após o header.php).
 * Requer que $_SESSION['user'] esteja definido.
 * A rota ativa é detectada automaticamente via $_SERVER['REQUEST_URI'].
 */

$currentPath = strtok($_SERVER['REQUEST_URI'], '?');
 
$navItems = [
    ['href' => '/painel',       'icon' => 'fa-chart-line',  'label' => 'Painel'],
    ['href' => '/usuarios',        'icon' => 'fa-users',       'label' => 'Usuários'],
    ['href' => '/organograma',     'icon' => 'fa-sitemap',     'label' => 'Organograma'],
    ['href' => '/competencias',    'icon' => 'fa-star',        'label' => 'Competências'],
    ['href' => '/banco-talentos',  'icon' => 'fa-briefcase',   'label' => 'Banco de Talentos'],
    ['href' => '/perfil',          'icon' => 'fa-user-circle', 'label' => 'Meu Perfil'],
];
?>
 
<aside class="sidebar" id="sidebar">
 
    <!-- ── Logo sempre no topo, centralizada ── -->
    <div class="sidebar-logo-wrap">
        <img src="/assets/images/orbe_logo.jpeg" alt="ORBE" class="sidebar-logo">
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
            $active = ($currentPath === $item['href']) ? 'active' : '';
        ?>
        <a href="<?= $item['href'] ?>" class="sidebar-link <?= $active ?>" title="<?= htmlspecialchars($item['label']) ?>">
            <i class="fas <?= $item['icon'] ?> sidebar-icon"></i>
            <span class="sidebar-label"><?= htmlspecialchars($item['label']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
 
    <!-- ── Logout no rodapé ── -->
    <div class="sidebar-footer">
        <form method="POST" action="/logout">
            <button type="submit" class="sidebar-link sidebar-logout" title="Sair">
                <i class="fas fa-sign-out-alt sidebar-icon"></i>
                <span class="sidebar-label">Sair</span>
            </button>
        </form>
    </div>
 
</aside>