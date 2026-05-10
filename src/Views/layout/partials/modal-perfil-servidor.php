<?php
/* Modal de perfil profissional do servidor — exibido para admin e gestor.*/
?>
<div id="modalPerfilServidor" class="modal" role="dialog" aria-modal="true" aria-labelledby="mpsNome" style="display:none;">
    <div class="modal-content modal-content--large modal-content--perfil">

        <div class="modal-header modal-header--perfil">
            <div class="mps-identity">
                <div class="mps-avatar" id="mpsAvatar"></div>
                <div class="mps-identity-info">
                    <span class="mps-ra" id="mpsRa">—</span>
                    <h2 class="mps-nome" id="mpsNome">—</h2>
                    <span class="mps-cargo" id="mpsCargo">—</span>
                    <div class="mps-badges" id="mpsBadges"></div>
                </div>
            </div>
            <div class="mps-header-right">
                <div class="mps-tempo-servico" id="mpsTempoServico" style="display:none;">
                    <span class="mps-tempo-num" id="mpsTempoNum">—</span>
                    <span class="mps-tempo-label">anos de serviço</span>
                </div>
                <button type="button" class="close-btn" onclick="fecharPerfilServidor()" aria-label="Fechar">&times;</button>
            </div>
        </div>

        <div class="mps-tabs">
            <button class="mps-tab active" data-tab="funcional" onclick="mpsAltTab(this, 'funcional')">
                <i class="fas fa-id-badge"></i> Funcional
            </button>
            <button class="mps-tab" data-tab="trajetoria" onclick="mpsAltTab(this, 'trajetoria')">
                <i class="fas fa-chart-line"></i> Trajetória & Desempenho
            </button>
        </div>

        <div class="mps-loading" id="mpsLoading">
            <i class="fas fa-spinner fa-spin"></i> Carregando...
        </div>
        <div class="mps-error" id="mpsError" style="display:none;">
            <i class="fas fa-circle-exclamation"></i>
            <p>Não foi possível carregar os dados do servidor.</p>
            <button class="btn-new" onclick="mpsRecarregar()">
                <i class="fas fa-rotate-right"></i> Tentar novamente
            </button>
        </div>

        <div class="mps-tab-content" id="mpsTab-funcional" style="display:none;">
            <div class="mps-section-grid">

                <div class="perfil-info-card">
                    <div class="perfil-info-card-header">
                        <span class="perfil-info-icon perfil-info-icon--blue">
                            <i class="fas fa-id-badge"></i>
                        </span>
                        <h3>Dados do Cargo</h3>
                    </div>
                    <div class="perfil-fields">
                        <div class="perfil-field">
                            <span class="perfil-field-label">RA</span>
                            <span class="perfil-field-value" id="mps-ra">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Cargo</span>
                            <span class="perfil-field-value" id="mps-cargo">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Patente</span>
                            <span class="perfil-field-value" id="mps-patente">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Situação</span>
                            <span class="perfil-field-value" id="mps-situacao">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Data de Ingresso</span>
                            <span class="perfil-field-value" id="mps-ingresso">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Previsão de Aposentadoria</span>
                            <span class="perfil-field-value" id="mps-aposentadoria">—</span>
                        </div>
                    </div>
                </div>

                <div class="perfil-info-card">
                    <div class="perfil-info-card-header">
                        <span class="perfil-info-icon perfil-info-icon--green">
                            <i class="fas fa-building"></i>
                        </span>
                        <h3>Lotação</h3>
                    </div>
                    <div class="perfil-fields">
                        <div class="perfil-field">
                            <span class="perfil-field-label">Unidade</span>
                            <span class="perfil-field-value" id="mps-unidade">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Sigla</span>
                            <span class="perfil-field-value" id="mps-sigla">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Estado</span>
                            <span class="perfil-field-value" id="mps-estado">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Tipo</span>
                            <span class="perfil-field-value" id="mps-unidade-tipo">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">E-mail institucional</span>
                            <span class="perfil-field-value" id="mps-email">—</span>
                        </div>
                    </div>
                </div>

                <div class="perfil-info-card">
                    <div class="perfil-info-card-header">
                        <span class="perfil-info-icon perfil-info-icon--yellow">
                            <i class="fas fa-wallet"></i>
                        </span>
                        <h3>Remuneração</h3>
                    </div>
                    <div class="perfil-fields">
                        <div class="perfil-field">
                            <span class="perfil-field-label">Salário Base Atual</span>
                            <span class="perfil-field-value" id="mps-salario">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Vigente desde</span>
                            <span class="perfil-field-value" id="mps-salario-desde">—</span>
                        </div>
                        <div class="perfil-field">
                            <span class="perfil-field-label">Motivo da última alteração</span>
                            <span class="perfil-field-value" id="mps-salario-motivo">—</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mps-beneficios-wrap">
                <h3 class="mps-subsection-title">
                    <i class="fas fa-shield-heart"></i> Benefícios Ativos
                </h3>
                <div class="mps-beneficios-grid" id="mpsBeneficios">
                    <span class="mps-empty">Nenhum benefício cadastrado.</span>
                </div>
            </div>
        </div>

        <div class="mps-tab-content" id="mpsTab-trajetoria" style="display:none;">

            <div class="mps-subsection">
                <h3 class="mps-subsection-title">
                    <i class="fas fa-arrows-left-right"></i> Histórico de Movimentações
                </h3>
                <ul class="mps-timeline" id="mpsMovimentacoes">
                    <li class="mps-empty">Nenhuma movimentação registrada.</li>
                </ul>
            </div>

            <div class="mps-subsection">
                <h3 class="mps-subsection-title">
                    <i class="fas fa-star-half-stroke"></i> Avaliações de Desempenho
                </h3>
                <div class="mps-avaliacoes-lista" id="mpsAvaliacoes">
                    <span class="mps-empty">Nenhuma avaliação registrada.</span>
                </div>
            </div>

            <div class="mps-subsection">
                <h3 class="mps-subsection-title">
                    <i class="fas fa-graduation-cap"></i> Competências & Certificações
                </h3>
                <div class="mps-competencias-grid" id="mpsCompetencias">
                    <span class="mps-empty">Nenhuma competência registrada.</span>
                </div>
            </div>

            <div class="mps-subsection">
                <h3 class="mps-subsection-title">
                    <i class="fas fa-umbrella-beach"></i> Férias & Afastamentos
                </h3>
                <div class="mps-ferias-lista" id="mpsFerias">
                    <span class="mps-empty">Nenhum registro encontrado.</span>
                </div>
            </div>

            <div class="mps-subsection" id="mpsPdiWrap" style="display:none;">
                <h3 class="mps-subsection-title">
                    <i class="fas fa-bullseye"></i> Plano de Desenvolvimento Individual
                </h3>
                <div class="mps-pdi-wrap" id="mpsPdi"></div>
            </div>

        </div>

    </div>
</div>