<?php
/*
 * Views/dashboard/painel.php
 * Painel de Recursos Humanos da PRF com indicadores e gráficos.
 */
$pageTitle = 'Painel de Monitoramento · ORBE';
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

        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
        <!-- ── BOTÃO EXPORTAR ── -->
        <button class="btn-exportar-relatorio" onclick="abrirModalRelatorio()">
            <i class="fas fa-file-arrow-down"></i>
            Exportar Relatório
        </button>
        <?php endif; ?>
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
                <span class="painel-card__label">Admissões no Ano</span>
                <span class="painel-card__valor" id="ind-admissoes">—</span>
                <span class="painel-card__sub">ingressados em <?= date('Y') ?></span>
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
                <span class="painel-card__sub">concluídas em <?= date('Y') ?>  </span>
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
<?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
<div class="modal-overlay" id="modal-relatorio" role="dialog" aria-modal="true" aria-labelledby="modal-relatorio-titulo">
    <div class="modal-box modal-box--relatorio">

        <div class="modal-header">
            <h2 id="modal-relatorio-titulo">
                <i class="fas fa-file-chart-column"></i>
                Exportar Relatório
            </h2>
            <button class="modal-close" onclick="fecharModalRelatorio()" aria-label="Fechar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="modal-body">

            <fieldset class="rel-fieldset">
                <legend><i class="fas fa-building"></i> Filtros Organizacionais</legend>
                <div class="rel-grid">
                    <div class="rel-field rel-field--full">
                        <label for="rel-unidades">Unidade(s)</label>
                        <select id="rel-unidades" multiple size="4" onchange="atualizarPreview()">
                        </select>
                        <span class="rel-hint">Segure Ctrl para selecionar múltiplas</span>
                    </div>
                    <div class="rel-field">
                        <label for="rel-cargo">Cargo</label>
                        <input type="text" id="rel-cargo" placeholder="Ex: Inspetor"
                               oninput="atualizarPreview()">
                    </div>
                    <div class="rel-field">
                        <label for="rel-role">Perfil de Acesso</label>
                        <select id="rel-role" onchange="atualizarPreview()">
                            <option value="">Todos</option>
                            <option value="admin">Administrador</option>
                            <option value="gestor">Gestor</option>
                            <option value="user">Usuário</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="rel-fieldset">
                <legend><i class="fas fa-circle-check"></i> Situação</legend>
                <div class="rel-checkgroup">
                    <?php
                    $situacoes = [
                        'ativo'               => 'Ativo',
                        'afastado'            => 'Afastado',
                        'aposentado'          => 'Aposentado',
                        'licenca_maternidade' => 'Licença',
                        'desligado'           => 'Desligado',
                        'outros'              => 'Outros',
                    ];
                    foreach ($situacoes as $val => $label):
                    ?>
                    <label class="rel-check">
                        <input type="checkbox" name="rel-situacao" value="<?= $val ?>"
                               onchange="atualizarPreview()">
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="rel-fieldset">
                <legend><i class="fas fa-calendar"></i> Tempo</legend>
                <div class="rel-grid">
                    <div class="rel-field">
                        <label>Faixa etária (anos)</label>
                        <div class="rel-range">
                            <input type="number" id="rel-idade-min" placeholder="Mín"
                                   min="18" max="80" oninput="atualizarPreview()">
                            <span>até</span>
                            <input type="number" id="rel-idade-max" placeholder="Máx"
                                   min="18" max="80" oninput="atualizarPreview()">
                        </div>
                    </div>
                    <div class="rel-field">
                        <label>Tempo de serviço (anos)</label>
                        <div class="rel-range">
                            <input type="number" id="rel-servico-min" placeholder="Mín"
                                   min="0" oninput="atualizarPreview()">
                            <span>até</span>
                            <input type="number" id="rel-servico-max" placeholder="Máx"
                                   min="0" oninput="atualizarPreview()">
                        </div>
                    </div>
                    <div class="rel-field">
                        <label>Período de admissão</label>
                        <div class="rel-range">
                            <input type="date" id="rel-admissao-de" onchange="atualizarPreview()">
                            <span>até</span>
                            <input type="date" id="rel-admissao-ate" onchange="atualizarPreview()">
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset class="rel-fieldset">
                <legend><i class="fas fa-graduation-cap"></i> Competências</legend>
                <div class="rel-grid">
                    <div class="rel-field">
                        <label for="rel-comp-nome">Nome da competência</label>
                        <input type="text" id="rel-comp-nome" placeholder="Ex: Liderança"
                               oninput="atualizarPreview()">
                    </div>
                    <div class="rel-field">
                        <label for="rel-comp-tipo">Tipo</label>
                        <select id="rel-comp-tipo" onchange="atualizarPreview()">
                            <option value="">Todos</option>
                            <option value="curso">Curso</option>
                            <option value="certificacao">Certificação</option>
                            <option value="especializacao">Especialização</option>
                            <option value="habilidade">Habilidade</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="rel-fieldset">
                <legend><i class="fas fa-sliders"></i> Configurações</legend>

                <div class="rel-subgroup">
                    <p class="rel-sublabel">Colunas do relatório</p>
                    <div class="rel-checkgroup">
                        <?php
                        $colunas = [
                            'nome'          => 'Nome',
                            'cargo'         => 'Cargo',
                            'unidade'       => 'Unidade',
                            'situacao'      => 'Situação',
                            'tempo_servico' => 'Tempo de Serviço',
                            'idade'         => 'Idade',
                            'competencias'  => 'Competências',
                        ];
                        $padrao = ['nome', 'cargo', 'unidade', 'situacao', 'tempo_servico'];
                        foreach ($colunas as $val => $label):
                            $checked = in_array($val, $padrao) ? 'checked' : '';
                        ?>
                        <label class="rel-check">
                            <input type="checkbox" name="rel-coluna" value="<?= $val ?>" <?= $checked ?>>
                            <?= $label ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rel-subgroup">
                    <label for="rel-ordenar" class="rel-sublabel">Ordenar por</label>
                    <select id="rel-ordenar" style="max-width:220px;">
                        <option value="nome">Nome</option>
                        <option value="idade">Idade</option>
                        <option value="tempo_servico">Tempo de Serviço</option>
                        <option value="admissao">Data de Admissão</option>
                    </select>
                </div>
            </fieldset>

            <div class="rel-preview">
                <span id="rel-preview-badge" class="rel-preview-badge">
                    Digite os filtros desejados para contar resultados
                </span>
            </div>

        </div>

        <div class="modal-footer">
            <button class="btn-secondary" onclick="fecharModalRelatorio()">Cancelar</button>
            <button class="btn-outline" id="btn-exportar-xlsx" onclick="exportarRelatorioXlsx()">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
            <button class="btn-primary" id="btn-exportar-pdf" onclick="exportarRelatorio()">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
        </div>

    </div>
</div>
<?php endif; ?>

<script src="/assets/js/relatorio.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>