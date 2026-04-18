<?php
$pageTitle = 'Organograma';
$bodyClass = 'dashboard-page';

require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<link rel="stylesheet" href="/assets/css/organograma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="main-content">

    <!-- Cabeçalho padrão -->
    <header class="header-section">
        <div class="title-group">
            <h1>Organograma Institucional PRF</h1>
            <p>Visualize a hierarquia organizacional de todos os servidores públicos separados por suas unidades de funções.</p>
        </div>
    </header>

    <section class="filters-container" style="display:block;">
        <div id="organograma-root"></div>

        <div id="org-hint">
            Clique nos cards para expandir o organograma.
        </div>
    </section>

    <button class="btn-theme-fixed" onclick="toggleTheme()" id="btnTema" title="Alternar tema">
        🌙
    </button>

</div>

<script src="/assets/js/organograma.js"></script>

<?php require __DIR__ . '/../layout/footer.php'; ?>