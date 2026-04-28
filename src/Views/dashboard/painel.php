<?php
/*
 * Views/dashboard/painel.php
 * Painel de Recursos Humanos da PRF com indicadores e gráficos.
 */
$pageTitle = 'Painel de RH';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="main-content">

    <header class="header-section">
        <div class="title-group">
            <h1>Painel de Monitoramento</h1>
            <p>Principais indicadores e gráficos para controle</p>
        </div>
    </header>

    <section class="painel-indicadores" id="painel-indicadores">

        <div class="painel-card painel-card--total">
            <div class="painel-card__icon"><i class="fas fa-users"></i></div>
            <div class="painel-card__info">
                <span class="painel-card__label">Total de Servidores</span>
                <span class="painel-card__valor" id="ind-total">—</span>
            </div>
        </div>

        <div class="painel-card painel-card--crescimento">
            <div class="painel-card__icon"><i class="fas fa-arrow-trend-up"></i></div>
            <div class="painel-card__info">
                <span class="painel-card__label">Crescimento</span>
                <span class="painel-card__valor" id="ind-crescimento">—</span>
                <span class="painel-card__sub">em relação ao período anterior</span>
            </div>
        </div>

        <div class="painel-card painel-card--aposentadoria">
            <div class="painel-card__icon"><i class="fas fa-person-cane"></i></div>
            <div class="painel-card__info">
                <span class="painel-card__label">Próximos da Aposentadoria</span>
                <span class="painel-card__valor" id="ind-aposentadoria">—</span>
                <span class="painel-card__sub">nos próximos 2 anos</span>
            </div>
        </div>

        <div class="painel-card painel-card--capacitacao">
            <div class="painel-card__icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="painel-card__info">
                <span class="painel-card__label">Capacitações no Ano</span>
                <span class="painel-card__valor" id="ind-capacitacoes">—</span>
                <span class="painel-card__sub" id="ind-crescimento-cap">—</span>
            </div>
        </div>

        <div class="painel-card painel-card--tempo">
            <div class="painel-card__icon"><i class="fas fa-clock"></i></div>
            <div class="painel-card__info">
                <span class="painel-card__label">Tempo Médio de Serviço</span>
                <span class="painel-card__valor" id="ind-tempo-medio">—</span>
                <span class="painel-card__sub">anos em média</span>
            </div>
        </div>

    </section>

    <section class="painel-graficos">

        <div class="painel-grafico-card" id="card-barras">
            <h2 class="painel-grafico-titulo">
                <i class="fas fa-chart-bar"></i> Servidores por Unidade
            </h2>
            <div class="painel-grafico-loading" id="loading-barras">
                <i class="fas fa-spinner fa-spin"></i> Carregando...
            </div>
            <div class="painel-grafico-wrap" id="wrap-barras" style="display:none;">
                <canvas id="grafico-barras"></canvas>
            </div>
        </div>

        <div class="painel-grafico-card" id="card-pizza">
            <h2 class="painel-grafico-titulo">
                <i class="fas fa-chart-pie"></i> Distribuição por Status
            </h2>
            <div class="painel-grafico-loading" id="loading-pizza">
                <i class="fas fa-spinner fa-spin"></i> Carregando...
            </div>
            <div class="painel-grafico-wrap painel-grafico-wrap--pizza" id="wrap-pizza" style="display:none;">
                <canvas id="grafico-pizza"></canvas>
            </div>
        </div>

    </section>

    <button class="btn-theme-fixed" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>