<?php
/*
 * Views/dashboard/banco-talentos.php
 * Tela do Banco de Talentos — ranking, indicadores e busca.
 * Consome os endpoints:
 *   GET /api/banco-talentos/indicadores
 *   GET /api/banco-talentos/ranking
 *   GET /api/banco-talentos/busca?q=termo
 */
$pageTitle = 'Banco de Talentos · ORBE';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<div class="main-content">

    <header class="header-section">
        <div class="title-group">
            <h1>Banco de Talentos</h1>
            <p>Identificação e gestão de talentos com base em competências e experiência</p>
        </div>
    </header>

    <section class="bt-indicadores" id="bt-indicadores">
        <div class="bt-card bt-card--alto">
            <div class="bt-card__label">
                <i class="fas fa-arrow-trend-up"></i> Alto Potencial
            </div>
            <div class="bt-card__valor" id="ind-alto">—</div>
            <div class="bt-card__sub" id="ind-alto-pct">—</div>
        </div>

        <div class="bt-card bt-card--medio">
            <div class="bt-card__label">
                <i class="fas fa-minus"></i> Médio Potencial
            </div>
            <div class="bt-card__valor" id="ind-medio">—</div>
            <div class="bt-card__sub" id="ind-medio-pct">—</div>
        </div>

        <div class="bt-card bt-card--media">
            <div class="bt-card__label">
                <i class="fas fa-star-half-stroke"></i> Pontuação Média
            </div>
            <div class="bt-card__valor" id="ind-media">—</div>
            <div class="bt-card__sub">de 100 pontos possíveis</div>
        </div>

        <div class="bt-card bt-card--total">
            <div class="bt-card__label">
                <i class="fas fa-users"></i> Total de Talentos
            </div>
            <div class="bt-card__valor" id="ind-total">—</div>
            <div class="bt-card__sub">servidores ativos avaliados</div>
        </div>
    </section>

    <section class="filters-container">
        <div class="filter-group bt-search-wrap">
            <input
                type="text"
                id="bt-search"
                class="bt-search-input"
                placeholder="Buscar por nome, cargo ou especialidade..."
                autocomplete="off"
            >
            <button class="bt-search-clear" id="bt-search-clear" title="Limpar busca" style="display:none;">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </section>

    <section class="bt-ranking-section">
        <div class="bt-ranking-header">
            <h2 class="bt-ranking-title">Ranking de Talentos</h2>
            <span class="counter" id="bt-contador">Carregando...</span>
        </div>

        <div id="bt-lista" class="bt-lista">
            <div class="table-feedback">
                <i class="fas fa-spinner fa-spin"></i> Carregando ranking...
            </div>
        </div>
    </section>

    <button class="btn-theme-fixed" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>

</div>
<?php require __DIR__ . '/../layout/partials/modal-perfil-servidor.php'; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>