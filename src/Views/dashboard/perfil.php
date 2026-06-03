<?php
/*
 * Tela de perfil do usuário logado.
 * Dados carregados via GET /api/perfil.
 * Upload de foto via POST /api/perfil/foto (apenas admin).
 * Edição de perfil via PUT /api/perfil (todos os usuários).
 */
$pageTitle = 'Meu Perfil · ORBE';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>
<div class="main-content">
    <header class="header-section">
        <style>
            /* ── Painel de notificações ───────────────────────────────────── */
            .notif-panel {
                display: none; position: fixed; top: 0; right: 0; height: 100vh; width: 380px;
                background: var(--card); border-left: 1px solid var(--border);
                box-shadow: -8px 0 32px rgba(0,0,0,.18); z-index: 500;
                flex-direction: column; overflow: hidden;
            }
            .notif-panel.open { display: flex; }
            .notif-panel-header {
                display: flex; align-items: center; justify-content: space-between;
                padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); flex-shrink: 0;
            }
            .notif-panel-title { font-weight: 700; font-size: 1rem; color: var(--text); }
            .notif-panel-close {
                background: none; border: none; cursor: pointer; color: var(--muted);
                font-size: 1.1rem; padding: .25rem; border-radius: .35rem; transition: background .2s;
            }
            .notif-panel-close:hover { background: var(--surface); }
            .notif-tabs {
                display: flex; border-bottom: 1px solid var(--border); flex-shrink: 0;
            }
            .notif-tab {
                flex: 1; padding: .65rem; background: none; border: none; cursor: pointer;
                font-size: .83rem; font-weight: 600; color: var(--muted);
                border-bottom: 2px solid transparent; transition: all .2s;
                display: flex; align-items: center; justify-content: center; gap: .4rem;
            }
            .notif-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
            .notif-tab:hover:not(.active) { background: var(--surface); }
            .notif-tab-badge {
                background: #ef4444; color: #fff; border-radius: 999px;
                font-size: .65rem; font-weight: 700; min-width: 1.1rem; height: 1.1rem;
                display: inline-flex; align-items: center; justify-content: center; padding: 0 .25rem;
            }
            .notif-tab-content { display: none; flex: 1; flex-direction: column; overflow: hidden; }
            .notif-tab-content.active { display: flex; }

            /* ── Toolbar de notificações com botão + ────────────────────── */
            .notif-toolbar {
                display: flex; justify-content: space-between; align-items: center;
                padding: .6rem 1rem; border-bottom: 1px solid var(--border); flex-shrink: 0;
                font-size: .8rem; color: var(--muted); gap: .5rem;
            }
            .notif-toolbar-actions {
                display: flex; align-items: center; gap: .4rem; position: relative;
            }
            .notif-mark-all {
                background: none; border: none; color: var(--primary); cursor: pointer;
                font-size: .78rem; padding: .2rem .4rem; border-radius: .3rem; transition: background .2s;
            }
            .notif-mark-all:hover { background: var(--surface); }

            /* ── Dropdown "+" ───────────────────────────────────────────── */
            .notif-add-btn {
                background: var(--primary); color: #fff; border: none; cursor: pointer;
                width: 1.55rem; height: 1.55rem; border-radius: 50%;
                font-size: .85rem; font-weight: 700;
                display: flex; align-items: center; justify-content: center;
                transition: opacity .2s; flex-shrink: 0;
            }
            .notif-add-btn:hover { opacity: .85; }
            .notif-dropdown {
                display: none; position: absolute; top: calc(100% + .4rem); right: 0;
                background: var(--card); border: 1px solid var(--border);
                border-radius: .6rem; min-width: 210px;
                box-shadow: 0 6px 20px rgba(0,0,0,.15); z-index: 600;
                flex-direction: column; overflow: hidden;
            }
            .notif-dropdown.open { display: flex; }
            .notif-dropdown-item {
                display: flex; align-items: center; gap: .55rem;
                padding: .65rem .9rem; background: none; border: none;
                font-size: .82rem; font-weight: 500; color: var(--text);
                cursor: pointer; transition: background .15s; text-align: left;
                width: 100%;
            }
            .notif-dropdown-item:hover { background: var(--surface); }
            .notif-dropdown-item i { width: 1rem; text-align: center; }
            .notif-dropdown-item--purple i { color: #a78bfa; }
            .notif-dropdown-item--blue   i { color: #60a5fa; }
            .notif-dropdown-item--yellow i { color: #f59e0b; }

            /* ── Lista de notificações ──────────────────────────────────── */
            .notif-list { list-style: none; margin: 0; padding: 0; overflow-y: auto; flex: 1; }
            .notif-item {
                padding: .75rem 1rem; border-bottom: 1px solid var(--border);
                font-size: .82rem; display: flex; gap: .6rem; align-items: flex-start;
                cursor: pointer; transition: background .15s;
            }
            .notif-item:hover { background: var(--surface); }
            .notif-item.unread { background: var(--surface); }
            .notif-item.unread .notif-item-title::after {
                content: '•'; color: var(--primary); margin-left: .35rem; font-size: .9rem;
            }
            .notif-item-icon { font-size: 1rem; margin-top: .1rem; flex-shrink: 0; }
            .notif-item-icon.warn    { color: #f59e0b; }
            .notif-item-icon.danger  { color: #ef4444; }
            .notif-item-icon.info    { color: var(--primary); }
            .notif-item-icon.success { color: #22c55e; }
            .notif-item-body { display: flex; flex-direction: column; gap: .2rem; flex: 1; }
            .notif-item-title { font-weight: 600; color: var(--text); }
            .notif-item-desc  { color: var(--muted); }
            .notif-item-time  { color: var(--muted); font-size: .72rem; margin-top: .1rem; }
            .notif-empty { padding: 2rem 1rem; text-align: center; color: var(--muted); font-size: .85rem; }
            .notif-overlay {
                display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 499;
            }
            .notif-overlay.open { display: block; }

            /* ── Solicitações ───────────────────────────────────────────── */
            .solic-item {
                padding: .85rem 1rem; border-bottom: 1px solid var(--border);
                display: flex; flex-direction: column; gap: .5rem;
            }
            .solic-header { display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem; }
            .solic-nome   { font-weight: 700; font-size: .85rem; color: var(--text); }
            .solic-status {
                font-size: .7rem; font-weight: 700; padding: .15rem .45rem;
                border-radius: 999px; white-space: nowrap; flex-shrink: 0;
            }
            .solic-status--pendente                 { background: #fef9c3; color: #854d0e; }
            .solic-status--aprovado_gestor_destino  { background: #dbeafe; color: #1d4ed8; }
            .solic-status--aprovado_admin           { background: #dbeafe; color: #1d4ed8; }
            .solic-status--aprovado                 { background: #dcfce7; color: #15803d; }
            .solic-status--rejeitado                { background: #fee2e2; color: #dc2626; }
            .solic-status--executado                { background: #f3f4f6; color: #6b7280; }
            .solic-rota  { font-size: .78rem; color: var(--muted); }
            .solic-meta  { font-size: .72rem; color: var(--muted); }
            .solic-motivo {
                font-size: .78rem; color: var(--text);
                background: var(--surface); padding: .35rem .6rem;
                border-radius: .4rem; border-left: 3px solid var(--primary);
            }
            .solic-acoes { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .25rem; }
            .solic-btn {
                padding: .35rem .75rem; border-radius: .4rem; border: none;
                font-size: .78rem; font-weight: 600; cursor: pointer; transition: opacity .2s;
            }
            .solic-btn:hover        { opacity: .85; }
            .solic-btn--aprovar     { background: #22c55e; color: #fff; }
            .solic-btn--rejeitar    { background: #ef4444; color: #fff; }
            .solic-btn--executar    { background: var(--primary); color: #fff; }

            /* ── Modais genéricos ───────────────────────────────────────── */
            .modal-overlay {
                display: none; position: fixed; inset: 0;
                background: rgba(0,0,0,.45); z-index: 600;
                align-items: center; justify-content: center;
            }
            .modal-overlay.open { display: flex; }
            .modal-box {
                background: var(--card); border: 1px solid var(--border);
                border-radius: .85rem; width: 100%; max-width: 420px;
                box-shadow: 0 8px 32px rgba(0,0,0,.2); overflow: hidden;
            }
            .modal-header {
                display: flex; justify-content: space-between; align-items: center;
                padding: .85rem 1.1rem; border-bottom: 1px solid var(--border);
            }
            .modal-header h3 { font-size: .95rem; font-weight: 700; color: var(--text); margin: 0; }
            .modal-close-btn {
                background: none; border: none; cursor: pointer; color: var(--muted);
                font-size: 1rem; padding: .25rem; border-radius: .3rem; transition: background .2s;
            }
            .modal-close-btn:hover { background: var(--surface); }
            .modal-body   { padding: 1.1rem; display: flex; flex-direction: column; gap: .85rem; }
            .modal-footer {
                display: flex; gap: .6rem; justify-content: flex-end;
                padding: .85rem 1.1rem; border-top: 1px solid var(--border);
            }
            .modal-field label {
                display: block; font-size: .75rem; font-weight: 600;
                color: var(--muted); margin-bottom: .35rem;
                text-transform: uppercase; letter-spacing: .03em;
            }
            .modal-field select,
            .modal-field textarea,
            .modal-field input[type="text"] {
                width: 100%; padding: .55rem .75rem; border-radius: .5rem;
                border: 1px solid var(--border); background: var(--surface);
                color: var(--text); font-size: .85rem; font-family: inherit;
                box-sizing: border-box;
            }
            .modal-field textarea { resize: vertical; min-height: 80px; }
            .modal-field select:focus,
            .modal-field textarea:focus,
            .modal-field input[type="text"]:focus { outline: none; border-color: var(--primary); }
            .modal-feedback {
                font-size: .8rem; padding: .5rem .75rem; border-radius: .4rem; display: none;
            }
            .modal-feedback.error   { display: block; background: #fee2e2; color: #dc2626; }
            .modal-feedback.success { display: block; background: #dcfce7; color: #16a34a; }

            /* ── Modal rejeição ─────────────────────────────────────────── */
            .rejeitar-overlay {
                display: none; position: fixed; inset: 0;
                background: rgba(0,0,0,.45); z-index: 700;
                align-items: center; justify-content: center;
            }
            .rejeitar-overlay.open { display: flex; }
            .notif-item--boas-vindas {
                background: linear-gradient(135deg, rgba(34,197,94,.08), rgba(var(--primary-rgb),.06));
                border-left: 3px solid #22c55e;
            }
            .notif-item--boas-vindas:hover {
                background: linear-gradient(135deg, rgba(34,197,94,.14), rgba(var(--primary-rgb),.10));
            }
        </style>

        <div class="title-group">
            <h1>Meu Perfil</h1>
            <p>Consulte suas informações cadastrais e de acesso</p>
        </div>

        <div style="display:flex;align-items:center;gap:.75rem">
            <div class="notif-overlay" id="notifOverlay"></div>

            <!-- Botão sino -->
            <div style="position:relative" id="notifWrapper">
                <button id="notifBtn" title="Notificações"
                    style="background:none;border:none;cursor:pointer;font-size:1.25rem;
                           color:var(--muted);padding:.25rem;display:flex;align-items:center;position:relative">
                    <i class="fas fa-bell"></i>
                    <span id="notifBadge"
                        style="display:none;position:absolute;top:-.2rem;right:-.2rem;
                               background:#ef4444;color:#fff;border-radius:50%;font-size:.6rem;
                               font-weight:700;min-width:1rem;height:1rem;align-items:center;
                               justify-content:center;padding:0 .15rem">
                    </span>
                </button>
            </div>

            <!-- Painel lateral -->
            <div class="notif-panel" id="notifPanel">
                <div class="notif-panel-header">
                    <span class="notif-panel-title">Central de Avisos</span>
                    <button class="notif-panel-close" id="notifPanelClose" title="Fechar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Tabs: Notificações | Solicitações (admin/gestor) -->
                <div class="notif-tabs">
                    <button class="notif-tab active" data-tab="notificacoes">
                        <i class="fas fa-bell"></i> Notificações
                        <span class="notif-tab-badge" id="tabBadgeNotif" style="display:none"></span>
                    </button>
                    <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gestor'])): ?>
                    <button class="notif-tab" data-tab="solicitacoes">
                        <i class="fas fa-exchange-alt"></i> Solicitações
                        <span class="notif-tab-badge" id="tabBadgeSolic" style="display:none"></span>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Tab: Notificações -->
                <div class="notif-tab-content active" id="tabContent-notificacoes">
                    <div class="notif-toolbar">
                        <span id="notifHeaderCount" style="font-size:.78rem"></span>
                        <div class="notif-toolbar-actions">
                            <button class="notif-mark-all" id="notifMarkAll">Marcar todas lidas</button>

                            <!-- Botão + com dropdown de ações -->
                            <div style="position:relative">
                                <button class="notif-add-btn" id="notifAddBtn" title="Ações">+</button>
                                <div class="notif-dropdown" id="notifDropdown">
                                    <?php
                                    $role = $_SESSION['user']['role'] ?? 'user';
                                    if ($role === 'user' || $role === 'gestor'): ?>
                                    <button class="notif-dropdown-item notif-dropdown-item--purple" id="ddBtnChamado">
                                        <i class="fas fa-pen-to-square"></i> Solicitar Edição de Perfil
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($role === 'gestor'): ?>
                                    <button class="notif-dropdown-item notif-dropdown-item--blue" id="ddBtnTransf">
                                        <i class="fas fa-exchange-alt"></i> Solicitar Transferência
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($role === 'gestor' || $role === 'admin'): ?>
                                    <button class="notif-dropdown-item notif-dropdown-item--yellow" id="ddBtnAviso">
                                        <i class="fas fa-bullhorn"></i>
                                        <?= $role === 'admin' ? 'Enviar Aviso aos Gestores' : 'Enviar Aviso à Equipe' ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <ul class="notif-list" id="notifList">
                        <li class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</li>
                    </ul>
                </div>

                <!-- Tab: Solicitações (admin/gestor) -->
                <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gestor'])): ?>
                <div class="notif-tab-content" id="tabContent-solicitacoes">
                    <div class="notif-toolbar">
                        <span style="font-size:.8rem;color:var(--muted)">Transferências pendentes</span>
                        <button class="notif-mark-all" id="solicRefreshBtn">
                            <i class="fas fa-rotate-right"></i> Atualizar
                        </button>
                    </div>
                    <div style="overflow-y:auto;flex:1" id="solicLista">
                        <div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
                    </div>
                </div>
                <?php endif; ?>
            </div><!-- /notif-panel -->

            <!-- ── Modal: Solicitar Edição de Perfil ────────────────────── -->
            <div class="modal-overlay" id="chamadoOverlay">
                <div class="modal-box" style="max-width:400px">
                    <div class="modal-header">
                        <h3><i class="fas fa-pen-to-square" style="color:#a78bfa;margin-right:.4rem"></i>Solicitar Edição de Perfil</h3>
                        <button class="modal-close-btn" id="chamadoFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <p style="font-size:.82rem;color:var(--muted);margin:0">
                            Descreva o que precisa ser alterado no seu perfil.
                            <?= ($_SESSION['user']['role'] ?? '') === 'user'
                                ? 'Seu gestor receberá a solicitação.'
                                : 'O administrador receberá a solicitação.' ?>
                        </p>
                        <div class="modal-field">
                            <label>Motivo / O que deseja alterar *</label>
                            <textarea id="chamadoMotivo" rows="4"
                                placeholder="Ex: Preciso corrigir meu nome, atualizar e-mail..."></textarea>
                        </div>
                        <div class="modal-feedback" id="chamadoFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" id="chamadoCancelarBtn">Cancelar</button>
                        <button class="btn-save" id="chamadoEnviarBtn">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitação
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Modal: Solicitar Transferência (gestor) ──────────────── -->
            <?php if (($_SESSION['user']['role'] ?? '') === 'gestor'): ?>
            <div class="modal-overlay" id="transfOverlay">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3><i class="fas fa-exchange-alt" style="color:var(--primary);margin-right:.4rem"></i>Solicitar Transferência</h3>
                        <button class="modal-close-btn" id="transfFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <p style="font-size:.82rem;color:var(--muted);margin:0">
                            Selecione o colaborador, o gestor de destino e o motivo.
                            A solicitação será enviada ao gestor de destino e ao administrador para aprovação.
                        </p>
                        <div class="modal-field">
                            <label>Gestor de Destino *</label>
                            <select id="transfGestor">
                                <option value="">Selecione o gestor de destino...</option>
                            </select>
                        </div>
                        <div class="modal-field">
                            <label>Colaborador *</label>
                            <select id="transfServidor">
                                <option value="">Selecione o colaborador...</option>
                            </select>
                        </div>
                        <div class="modal-field">
                            <label>Motivo</label>
                            <textarea id="transfMotivo" placeholder="Descreva o motivo da solicitação..."></textarea>
                        </div>
                        <div class="modal-feedback" id="transfFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" id="transfCancelarBtn">Cancelar</button>
                        <button class="btn-save" id="transfEnviarBtn">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitação
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Modal: Enviar Aviso (admin/gestor) ───────────────────── -->
            <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gestor'])): ?>
            <div class="modal-overlay" id="avisoOverlay">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3><i class="fas fa-bullhorn" style="color:#f59e0b;margin-right:.4rem"></i>
                            <?= ($_SESSION['user']['role'] ?? '') === 'admin'
                                ? 'Enviar Aviso aos Gestores'
                                : 'Enviar Aviso à Equipe' ?>
                        </h3>
                        <button class="modal-close-btn" id="avisoFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <p style="font-size:.82rem;color:var(--muted);margin:0">
                            <?= ($_SESSION['user']['role'] ?? '') === 'admin'
                                ? 'Esta mensagem será enviada como notificação para todos os gestores ativos.'
                                : 'Esta mensagem será enviada como notificação para todos os colaboradores da sua unidade.' ?>
                        </p>
                        <div class="modal-field">
                            <label>Título *</label>
                            <input type="text" id="avisoTitulo" placeholder="Título do aviso...">
                        </div>
                        <div class="modal-field">
                            <label>Mensagem *</label>
                            <textarea id="avisoMensagem" placeholder="Digite a mensagem do aviso..."></textarea>
                        </div>
                        <div class="modal-feedback" id="avisoFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" id="avisoCancelarBtn">Cancelar</button>
                        <button class="btn-save" id="avisoEnviarBtn">
                            <i class="fas fa-paper-plane"></i> Enviar Aviso
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Modal: Rejeitar Solicitação (admin/gestor) ───────────── -->
            <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gestor'])): ?>
            <div class="rejeitar-overlay" id="rejeitarOverlay">
                <div class="modal-box" style="max-width:380px">
                    <div class="modal-header">
                        <h3><i class="fas fa-times-circle" style="color:#ef4444;margin-right:.4rem"></i>Rejeitar Solicitação</h3>
                        <button class="modal-close-btn" id="rejeitarFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <p style="font-size:.82rem;color:var(--muted);margin:0">Informe o motivo da rejeição (opcional):</p>
                        <div class="modal-field">
                            <textarea id="rejeitarMotivo" placeholder="Motivo da rejeição..." style="min-height:80px"></textarea>
                        </div>
                        <div class="modal-feedback" id="rejeitarFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" id="rejeitarCancelarBtn">Cancelar</button>
                        <button class="solic-btn solic-btn--rejeitar" id="rejeitarConfirmarBtn">
                            <i class="fas fa-times-circle"></i> Confirmar Rejeição
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- ── Conteúdo do perfil ──────────────────────────────────────────── -->
    <div class="perfil-loading" id="perfilLoading">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Carregando informações...</span>
    </div>
    <div class="perfil-feedback-error" id="perfilError" style="display:none">
        <i class="fas fa-circle-exclamation"></i>
        <p>Não foi possível carregar as informações do perfil.</p>
        <button class="btn-new" onclick="carregarPerfil()">
            <i class="fas fa-rotate-right"></i> Tentar novamente
        </button>
    </div>

    <div id="perfilContent" style="display:none">
        <!-- Card identidade -->
        <div class="perfil-identity-card">
            <div class="perfil-avatar-wrap" id="avatarWrap">
                <div class="perfil-avatar" id="perfilAvatar"></div>
                <label class="perfil-avatar-upload" id="avatarUploadLabel" style="display:none" title="Alterar foto">
                    <i class="fas fa-camera"></i>
                    <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp" style="display:none">
                </label>
            </div>
            <div class="perfil-identity-info">
                <p class="perfil-identity-ra"    id="perfilRA">—</p>
                <p class="perfil-identity-name"  id="perfilNome">—</p>
                <p class="perfil-identity-cargo" id="perfilCargo">—</p>
                <div class="perfil-identity-badges" id="perfilBadges"></div>
            </div>
            <div class="perfil-tempo-servico" id="perfilTempoServico" style="display:none">
                <span class="perfil-tempo-numero" id="tempoServicoAnos">—</span>
                <span class="perfil-tempo-label">anos na PRF</span>
            </div>
        </div>

        <!-- Preview de upload de foto -->
        <div class="perfil-upload-preview" id="uploadPreview" style="display:none">
            <img id="previewImg" src="" alt="Preview">
            <div class="perfil-upload-actions">
                <button class="btn-new"    id="btnSalvarFoto"><i class="fas fa-check"></i> Salvar foto</button>
                <button class="btn-cancel" id="btnCancelarFoto">Cancelar</button>
            </div>
            <p class="perfil-upload-hint">JPG, PNG ou WEBP · máx. 2 MB</p>
        </div>

        <!-- Grid de informações -->
        <div class="perfil-info-grid">
            <!-- Card 1: Dados Funcionais -->
            <div class="perfil-info-card">
                <div class="perfil-info-card-header">
                    <span class="perfil-info-icon perfil-info-icon--blue"><i class="fas fa-id-badge"></i></span>
                    <h3>Dados Funcionais</h3>
                </div>
                <div class="perfil-fields">
                    <div class="perfil-field">
                        <span class="perfil-field-label">Registro (RA)</span>
                        <span class="perfil-field-value" id="detRA">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Cargo</span>
                        <span class="perfil-field-value" id="detCargo">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Patente</span>
                        <span class="perfil-field-value" id="detPatente">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Data de Ingresso</span>
                        <span class="perfil-field-value" id="detIngresso">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Previsão de Aposentadoria</span>
                        <span class="perfil-field-value" id="detAposentadoria">—</span>
                    </div>
                </div>
            </div>
            <!-- Card 2: Lotação -->
            <div class="perfil-info-card">
                <div class="perfil-info-card-header">
                    <span class="perfil-info-icon perfil-info-icon--green"><i class="fas fa-building"></i></span>
                    <h3>Lotação</h3>
                </div>
                <div class="perfil-fields">
                    <div class="perfil-field">
                        <span class="perfil-field-label">Unidade</span>
                        <span class="perfil-field-value" id="detUnidade">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Sigla</span>
                        <span class="perfil-field-value" id="detSigla">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Estado</span>
                        <span class="perfil-field-value" id="detEstado">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Situação</span>
                        <span class="perfil-field-value" id="detSituacao">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Perfil de Acesso</span>
                        <span class="perfil-field-value" id="detPerfil">—</span>
                    </div>
                </div>
            </div>
            <!-- Card 3: Dados Pessoais -->
            <div class="perfil-info-card">
                <div class="perfil-info-card-header">
                    <span class="perfil-info-icon perfil-info-icon--yellow"><i class="fas fa-user"></i></span>
                    <h3>Dados Pessoais</h3>
                </div>
                <div class="perfil-fields">
                    <div class="perfil-field">
                        <span class="perfil-field-label">E-mail Institucional</span>
                        <span class="perfil-field-value" id="detEmail">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">CPF</span>
                        <span class="perfil-field-value">
                            <span id="detCPF">—</span>
                            <button class="perfil-cpf-toggle" id="btnToggleCpf" title="Mostrar/ocultar CPF" style="display:none">
                                <i class="fas fa-eye" id="iconCpf"></i>
                            </button>
                        </span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Data de Nascimento</span>
                        <span class="perfil-field-value" id="detNascimento">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Status da Conta</span>
                        <span class="perfil-field-value" id="detStatus">—</span>
                    </div>
                    <div class="perfil-field">
                        <span class="perfil-field-label">Membro desde</span>
                        <span class="perfil-field-value" id="detDataCadastro">—</span>
                    </div>
                </div>
            </div>
        </div><!-- /perfil-info-grid -->

        <!-- Segurança e Privacidade -->
        <div class="perfil-security-card">
            <div class="perfil-info-card-header">
                <span class="perfil-info-icon perfil-info-icon--shield"><i class="fas fa-shield-alt"></i></span>
                <h3>Segurança e Privacidade</h3>
            </div>
            <div class="perfil-security-row">
                <div class="security-item">
                    <div class="security-item-icon security-item-icon--green"><i class="fas fa-database"></i></div>
                    <div class="security-item-text">
                        <strong>Proteção de dados</strong>
                        <p>Dados armazenados com segurança e utilizados apenas para fins institucionais, conforme a LGPD.</p>
                    </div>
                </div>
                <div class="security-item">
                    <div class="security-item-icon security-item-icon--yellow"><i class="fas fa-user-edit"></i></div>
                    <div class="security-item-text">
                        <strong>Alterações no perfil</strong>
                        <p>Alterações cadastrais são registradas em log de auditoria e requerem permissão de administrador.</p>
                    </div>
                </div>
                <div class="security-item">
                    <div class="security-item-icon security-item-icon--blue"><i class="fas fa-lock"></i></div>
                    <div class="security-item-text">
                        <strong>Senhas seguras</strong>
                        <p>Use senhas fortes e únicas. Em caso de comprometimento, solicite a redefinição ao administrador.</p>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /perfilContent -->

    <button class="btn-theme-fixed" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>
</div><!-- /main-content -->

<?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gestor'])): ?>
<?php require __DIR__ . '/../layout/modal-edicao-colaborador.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>

<script>
window.__userId = <?= (int) ($_SESSION['user']['id'] ?? 0) ?>;

/* ── Helpers de formatação ───────────────────────────────────────────── */
let _cpfReal    = '';
let _cpfOculto  = '';
let _cpfVisivel = false;
let _perfilCache = null;

function formatarDataBR(str) {
    if (!str) return '—';
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(str)) return str;
    const [y, m, d] = str.split('-');
    return `${d}/${m}/${y}`;
}

function calcularTempoServico(dataIngressoStr) {
    if (!dataIngressoStr) return null;
    let ingresso;
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dataIngressoStr)) {
        const [d, m, y] = dataIngressoStr.split('/');
        ingresso = new Date(`${y}-${m}-${d}`);
    } else {
        ingresso = new Date(dataIngressoStr);
    }
    const hoje   = new Date();
    const anos   = hoje.getFullYear() - ingresso.getFullYear();
    const ajuste = (hoje.getMonth() < ingresso.getMonth() ||
        (hoje.getMonth() === ingresso.getMonth() && hoje.getDate() < ingresso.getDate())) ? 1 : 0;
    return anos - ajuste;
}

function mascaraCPF(cpf) {
    if (!cpf) return '—';
    const digits = cpf.replace(/\D/g, '');
    if (digits.length !== 11) return cpf;
    return `${digits.slice(0,3)}.***.***-${digits.slice(9)}`;
}

function formatarCpfCompleto(cpf) {
    if (!cpf) return cpf;
    const d = cpf.replace(/\D/g, '');
    if (d.length !== 11) return cpf;
    return `${d.slice(0,3)}.${d.slice(3,6)}.${d.slice(6,9)}-${d.slice(9)}`;
}

function badgeSituacao(situacao) {
    const map = {
        'ativo':      ['pill-active',   'Ativo'],
        'afastado':   ['pill-inactive', 'Afastado'],
        'aposentado': ['pill-inactive', 'Aposentado'],
    };
    const [cls, label] = map[situacao?.toLowerCase()] ?? ['pill-inactive', situacao];
    return `<span class="pill ${cls}">${label}</span>`;
}

/* ── Carregar perfil ─────────────────────────────────────────────────── */
async function carregarPerfil() {
    const loading = document.getElementById('perfilLoading');
    const content = document.getElementById('perfilContent');
    const error   = document.getElementById('perfilError');
    loading.style.display = 'flex';
    content.style.display = 'none';
    error.style.display   = 'none';
    try {
        const res = await fetch(`${BASE_URL}/api/perfil`);
        if (res.status === 401) { window.location.href = `${BASE_URL}/login`; return; }
        if (!res.ok) throw new Error('Erro na API');
        const json = await res.json();
        const u    = json.data;
        _perfilCache = u;

        const avatarEl = document.getElementById('perfilAvatar');
        if (u.foto_url) {
            avatarEl.innerHTML = `<img src="${u.foto_url}" alt="${u.nome}" class="perfil-avatar-img">`;
        } else {
            const initials = u.nome.trim().split(' ')
                .filter(Boolean).slice(0, 2)
                .map(w => w[0].toUpperCase()).join('');
            avatarEl.textContent = initials;
        }

        document.getElementById('perfilRA').textContent    = u.ra    ?? '';
        document.getElementById('perfilNome').textContent  = u.nome;
        document.getElementById('perfilCargo').textContent =
            [u.patente, u.cargo].filter(Boolean).join(' · ');
        document.getElementById('perfilBadges').innerHTML =
            badgePerfil(u.perfil_raw, u.perfil) +
            ` <span class="pill ${u.status_raw ? 'pill-active' : 'pill-inactive'}">${u.status_raw ? 'Ativo' : 'Inativo'}</span>`;

        const anos = calcularTempoServico(u.data_ingresso);
        if (anos !== null && anos >= 0) {
            document.getElementById('tempoServicoAnos').textContent = anos;
            document.getElementById('perfilTempoServico').style.display = 'flex';
        }

        document.getElementById('detRA').textContent            = u.ra                      ?? '—';
        document.getElementById('detCargo').textContent         = u.cargo                  ?? '—';
        document.getElementById('detPatente').textContent       = u.patente                ?? '—';
        document.getElementById('detIngresso').textContent      = formatarDataBR(u.data_ingresso);
        document.getElementById('detAposentadoria').textContent = formatarDataBR(u.previsao_aposentadoria);
        document.getElementById('detUnidade').textContent  = u.unidade       ?? '—';
        document.getElementById('detSigla').textContent    = u.unidade_sigla ?? '—';
        document.getElementById('detEstado').textContent   = u.estado        ?? '—';
        document.getElementById('detSituacao').innerHTML   = badgeSituacao(u.situacao);
        document.getElementById('detPerfil').innerHTML     = badgePerfil(u.perfil_raw, u.perfil);
        document.getElementById('detEmail').textContent        = u.email;
        document.getElementById('detNascimento').textContent   = formatarDataBR(u.data_nascimento);
        document.getElementById('detStatus').innerHTML         =
            `<span class="pill ${u.status_raw ? 'pill-active' : 'pill-inactive'}">${u.status_raw ? 'Ativo' : 'Inativo'}</span>`;
        document.getElementById('detDataCadastro').textContent = u.data_cadastro ?? '—';

        if (u.cpf) {
            _cpfReal   = formatarCpfCompleto(u.cpf);
            _cpfOculto = mascaraCPF(u.cpf);
            document.getElementById('detCPF').textContent         = _cpfOculto;
            document.getElementById('btnToggleCpf').style.display = 'inline-flex';
        }
        document.getElementById('avatarUploadLabel').style.display = 'flex';
        loading.style.display = 'none';
        content.style.display = 'block';
    } catch (err) {
        console.error(err);
        loading.style.display = 'none';
        error.style.display   = 'flex';
    }
}

/* ── Toggle CPF ──────────────────────────────────────────────────────── */
document.getElementById('btnToggleCpf')?.addEventListener('click', () => {
    _cpfVisivel = !_cpfVisivel;
    document.getElementById('detCPF').textContent = _cpfVisivel ? _cpfReal : _cpfOculto;
    const icon = document.getElementById('iconCpf');
    if (icon) {
        icon.classList.toggle('fa-eye',       !_cpfVisivel);
        icon.classList.toggle('fa-eye-slash',  _cpfVisivel);
    }
});

/* ── Upload de foto ──────────────────────────────────────────────────── */
document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('Arquivo muito grande. Máximo: 2 MB.');
        this.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('uploadPreview').style.display = 'flex';
    };
    reader.readAsDataURL(file);
});

document.getElementById('avatarUploadLabel').addEventListener('click', function () {
    document.getElementById('avatarInput').click();
});

document.getElementById('btnCancelarFoto').addEventListener('click', () => {
    document.getElementById('uploadPreview').style.display = 'none';
    document.getElementById('avatarInput').value = '';
});

document.getElementById('btnSalvarFoto').addEventListener('click', async () => {
    const input = document.getElementById('avatarInput');
    if (!input.files[0]) return;
    const btn = document.getElementById('btnSalvarFoto');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    const formData = new FormData();
    formData.append('foto', input.files[0]);
    try {
        const res  = await fetch(`${BASE_URL}/api/perfil/foto`, { method: 'POST', body: formData });
        const json = await res.json();
        if (!res.ok) throw new Error(json.error ?? 'Erro ao salvar foto.');
        document.getElementById('perfilAvatar').innerHTML =
            `<img src="${json.foto_url}?t=${Date.now()}" alt="Avatar" class="perfil-avatar-img">`;
        document.getElementById('uploadPreview').style.display = 'none';
        input.value = '';
    } catch (err) {
        alert(err.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Salvar foto';
    }
});

/* ══════════════════════════════════════════════════════════════════════
   NOTIFICAÇÕES
   ══════════════════════════════════════════════════════════════════════ */
let _lidasLocalmente = new Set();

const Notificacoes = (() => {
    const POLL_INTERVAL = 10_000;
    let _timer = null;

    const iconMap = {
        competencia_vencida:       { cls: 'danger',  icon: 'fa-certificate'  },
        promocao:                  { cls: 'success', icon: 'fa-arrow-up'      },
        transferencia:             { cls: 'info',    icon: 'fa-exchange-alt'  },
        aviso_gestor:              { cls: 'warn',    icon: 'fa-bullhorn'      },
        aviso_admin:               { cls: 'warn',    icon: 'fa-bullhorn'      },
        solicitacao_transferencia: { cls: 'info',    icon: 'fa-paper-plane'   },
        aprovacao_transferencia:   { cls: 'success', icon: 'fa-check-circle'  },
        boas_vindas:               { cls: 'success', icon: 'fa-hand-wave' },
        default:                   { cls: 'info',    icon: 'fa-bell'          },
    };

    function _tempoRelativo(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)    return 'agora';
        if (diff < 3600)  return `${Math.floor(diff / 60)}min atrás`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
        return `${Math.floor(diff / 86400)}d atrás`;
    }

    function _renderizar(notifs) {
        const list   = document.getElementById('notifList');
        const badge  = document.getElementById('notifBadge');
        const header = document.getElementById('notifHeaderCount');

        const notifsFinal = notifs.map(n => ({
            ...n,
            lida: n.lida || _lidasLocalmente.has(n.id),
        }));

        const naoLidas = notifsFinal.filter(n => !n.lida).length;
        window.__notifNaoLidas = naoLidas;

        const dot = document.getElementById('sidebarNotifDot');
        if (dot) dot.style.display = naoLidas > 0 ? 'block' : 'none';

        if (naoLidas > 0) {
            badge.textContent   = naoLidas > 99 ? '99+' : naoLidas;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }

        header.textContent = naoLidas > 0
            ? `(${naoLidas} nova${naoLidas > 1 ? 's' : ''})`
            : '';

        if (!notifsFinal.length) {
            list.innerHTML = '<li class="notif-empty"><i class="fas fa-check-circle" style="color:#22c55e"></i> Tudo em dia!</li>';
            return;
        }

        const novoHash = JSON.stringify(
            notifsFinal.map(n => ({ id: n.id, lida: n.lida, mensagem: n.mensagem }))
        );
        if (list.dataset.hash === novoHash) return;
        list.dataset.hash = novoHash;

        list.innerHTML = notifsFinal.map(n => {
            const map    = iconMap[n.tipo] ?? iconMap.default;
            const unread = !n.lida ? 'unread' : '';
            const itemExtra = n.tipo === 'boas_vindas' ? ' notif-item--boas-vindas' : '';
            return `
            <li class="notif-item ${unread}${itemExtra}" data-id="${n.id}" data-lida="${n.lida}"
                data-ref-id="${n.referencia_id ?? ''}" data-ref-tipo="${n.referencia_tipo ?? ''}">
                <span class="notif-item-icon ${map.cls}"><i class="fas ${map.icon}"></i></span>
                <div class="notif-item-body">
                    <span class="notif-item-title">${n.titulo}</span>
                    <span class="notif-item-desc">${n.mensagem}</span>
                    <span class="notif-item-time">${_tempoRelativo(n.created_at)}</span>
                </div>
            </li>`;
        }).join('');

        list.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', async () => {
                await _marcarLida(el);
                const refTipo = el.dataset.refTipo;
                const refId   = parseInt(el.dataset.refId);
                const role = '<?= $_SESSION["user"]["role"] ?? "user" ?>';
                if (refTipo === 'chamados_edicao' && refId && (role === 'gestor' || role === 'admin')) {
                    Chamado.abrirAprovar(refId);
                }
            });
        });
    }

    async function _marcarTodasLidas() {
        const btn = document.getElementById('notifMarkAll');
        if (btn) { btn.disabled = true; btn.textContent = 'Aguarde...'; }
        try {
            const res = await fetch(`${BASE_URL}/api/notificacoes/todas-lidas`, { method: 'PATCH' });
            if (!res.ok) throw new Error('Falha ao marcar lidas.');

            // Marca todos os itens visíveis como lidos
            document.querySelectorAll('.notif-item').forEach(el => {
                _lidasLocalmente.add(parseInt(el.dataset.id));
                el.classList.remove('unread');
                el.dataset.lida = 'true';
            });

            // Zera badges e contadores
            document.getElementById('notifBadge').style.display    = 'none';
            document.getElementById('notifHeaderCount').textContent = '';
            window.__notifNaoLidas = 0;

            const tabBadge = document.getElementById('tabBadgeNotif');
            if (tabBadge) tabBadge.style.display = 'none';

            const dot = document.getElementById('sidebarNotifDot');
            if (dot) dot.style.display = 'none';

            // Invalida o hash para o próximo poll não re-renderizar como não lidas
            const list = document.getElementById('notifList');
            delete list.dataset.hash;
        } catch (e) {
            console.warn('Erro ao marcar todas lidas', e);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Marcar todas lidas'; }
        }
    }

    async function _marcarLida(el) {
        if (el.dataset.lida === 'true') return;
        const id = parseInt(el.dataset.id);
        _lidasLocalmente.add(id);
        el.dataset.lida = 'true';
        el.classList.remove('unread');

        const badge  = document.getElementById('notifBadge');
        const header = document.getElementById('notifHeaderCount');
        let atual = parseInt(badge.textContent) || 0;
        atual = Math.max(0, atual - 1);
        window.__notifNaoLidas = atual;
        const dot = document.getElementById('sidebarNotifDot');
        if (dot) dot.style.display = atual > 0 ? 'block' : 'none';

        if (atual === 0) {
            badge.style.display = 'none';
            header.textContent  = '';
        } else {
            badge.textContent  = atual;
            header.textContent = `(${atual} nova${atual > 1 ? 's' : ''})`;
        }
        delete document.getElementById('notifList').dataset.hash;
        try {
            await fetch(`${BASE_URL}/api/notificacoes/${id}/lida`, { method: 'PATCH' });
        } catch (e) {
            console.warn('Erro ao marcar lida', e);
        }
    }

    async function buscar() {
        try {
            const res  = await fetch(`${BASE_URL}/api/notificacoes`);
            if (!res.ok) return;
            const json = await res.json();
            _renderizar(json.data ?? []);
        } catch (e) {
            console.warn('Polling notificações falhou', e);
        }
    }

    function iniciar() {
        buscar();
        _timer = setInterval(buscar, POLL_INTERVAL);

        /* Abrir/fechar painel */
        const btn     = document.getElementById('notifBtn');
        const panel   = document.getElementById('notifPanel');
        const overlay = document.getElementById('notifOverlay');
        const closeBtn= document.getElementById('notifPanelClose');

        btn.addEventListener('click', e => {
            e.stopPropagation();
            panel.classList.toggle('open');
            overlay.classList.toggle('open');
        });
        closeBtn.addEventListener('click', () => {
            panel.classList.remove('open');
            overlay.classList.remove('open');
        });
        overlay.addEventListener('click', () => {
            panel.classList.remove('open');
            overlay.classList.remove('open');
        });

        /* Tabs */
        document.querySelectorAll('.notif-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.notif-tab-content').forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(`tabContent-${tab.dataset.tab}`).classList.add('active');
                if (tab.dataset.tab === 'solicitacoes') Solicitacoes.carregar();
            });
        });

        document.getElementById('notifMarkAll').addEventListener('click', _marcarTodasLidas);

        /* Dropdown "+" */
        const addBtn   = document.getElementById('notifAddBtn');
        const dropdown = document.getElementById('notifDropdown');

        addBtn.addEventListener('click', e => {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });
        document.addEventListener('click', () => dropdown.classList.remove('open'));
        dropdown.addEventListener('click', e => e.stopPropagation());

        /* Botões do dropdown */
        document.getElementById('ddBtnChamado')?.addEventListener('click', () => {
            dropdown.classList.remove('open');
            Chamado.abrir();
        });
        document.getElementById('ddBtnTransf')?.addEventListener('click', () => {
            dropdown.classList.remove('open');
            Transferencia.abrir();
        });
        document.getElementById('ddBtnAviso')?.addEventListener('click', () => {
            dropdown.classList.remove('open');
            Aviso.abrir();
        });
    }

    return { iniciar, buscar };
})();

/* ══════════════════════════════════════════════════════════════════════
   SOLICITAÇÕES DE TRANSFERÊNCIA
   ══════════════════════════════════════════════════════════════════════ */
const Solicitacoes = (() => {
    let _ultimaCarga = 0;
    const role = '<?= $_SESSION["user"]["role"] ?? "user" ?>';
    const myId = window.__userId;
    let _rejeitarId = null;

    const statusLabel = {
        pendente:                'Pendente',
        aprovado_gestor_destino: 'Aguard. Admin',
        aprovado_admin:          'Aguard. Gestor Destino',
        aprovado:                'Aprovado ✓',
        rejeitado:               'Rejeitado',
        executado:               'Executado',
    };

    function _tempoRelativo(str) {
        if (!str) return '';
        const diff = Math.floor((Date.now() - new Date(str)) / 1000);
        if (diff < 60)    return 'agora';
        if (diff < 3600)  return `${Math.floor(diff/60)}min atrás`;
        if (diff < 86400) return `${Math.floor(diff/3600)}h atrás`;
        return `${Math.floor(diff/86400)}d atrás`;
    }

    function _botoesAcao(s) {
        if (s.status === 'rejeitado' || s.status === 'executado') return '';
        const botoes = [];
        const podeAprovarGestor = role === 'gestor'
            && parseInt(s.gestor_destino_user_id) === myId
            && !s.aprovado_gestor_destino_at;
        const podeAprovarAdmin  = role === 'admin' && !s.aprovado_admin_at;
        const podeRejeitar      = podeAprovarGestor || podeAprovarAdmin;
        const podeExecutar      = role === 'gestor'
            && parseInt(s.solicitante_user_id) === myId
            && s.status === 'aprovado';

        if (podeAprovarGestor || podeAprovarAdmin) {
            botoes.push(`<button class="solic-btn solic-btn--aprovar" onclick="Solicitacoes.aprovar(${s.id})">
                <i class="fas fa-check"></i> Aprovar</button>`);
        }
        if (podeRejeitar) {
            botoes.push(`<button class="solic-btn solic-btn--rejeitar" onclick="Solicitacoes.abrirRejeitar(${s.id})">
                <i class="fas fa-times"></i> Rejeitar</button>`);
        }
        if (podeExecutar) {
            botoes.push(`<button class="solic-btn solic-btn--executar" onclick="Solicitacoes.executar(${s.id})">
                <i class="fas fa-exchange-alt"></i> Executar Transferência</button>`);
        }
        return botoes.join('');
    }

    async function carregar(forcar = false) {
        const agora = Date.now();
        if (!forcar && agora - _ultimaCarga < 15_000) return;
        _ultimaCarga = agora;

        const lista = document.getElementById('solicLista');
        if (!lista) return;
        lista.innerHTML = '<div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

        try {
            const res  = await fetch(`${BASE_URL}/api/chat/solicitacoes`);
            const json = await res.json();
            const sols = json.data ?? [];

            const pendentes = sols.filter(s => s.status !== 'executado' && s.status !== 'rejeitado').length;
            const badge = document.getElementById('tabBadgeSolic');
            if (badge) {
                if (pendentes > 0) { badge.textContent = pendentes; badge.style.display = 'inline-flex'; }
                else badge.style.display = 'none';
            }

            if (!sols.length) {
                lista.innerHTML = '<div class="notif-empty">Nenhuma solicitação encontrada.</div>';
                return;
            }

            lista.innerHTML = sols.map(s => `
                <div class="solic-item" id="solic-${s.id}">
                    <div class="solic-header">
                        <span class="solic-nome">
                            <i class="fas fa-user" style="color:var(--primary);margin-right:.3rem"></i>
                            ${s.servidor_nome}
                        </span>
                        <span class="solic-status solic-status--${s.status}">
                            ${statusLabel[s.status] ?? s.status}
                        </span>
                    </div>
                    <div class="solic-rota">
                        <i class="fas fa-building" style="margin-right:.3rem"></i>
                        ${s.unidade_origem_nome}
                        <i class="fas fa-arrow-right" style="margin:0 .4rem;color:var(--primary)"></i>
                        ${s.unidade_destino_nome}
                    </div>
                    ${s.motivo ? `<div class="solic-motivo">${s.motivo}</div>` : ''}
                    <div class="solic-meta">Solicitado por ${s.solicitante_nome} · ${_tempoRelativo(s.created_at)}</div>
                    <div class="solic-acoes">${_botoesAcao(s)}</div>
                </div>
            `).join('');
        } catch(e) {
            lista.innerHTML = '<div class="notif-empty">Erro ao carregar solicitações.</div>';
        }
    }

    async function aprovar(id) {
        const btn = document.querySelector(`#solic-${id} .solic-btn--aprovar`);
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/solicitacoes/${id}/aprovar`, { method: 'POST' });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Erro ao aprovar.');
            await carregar(true);
        } catch(e) {
            Toast.error('Erro ao aprovar', e.message);
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Aprovar'; }
        }
    }

    function abrirRejeitar(id) {
        _rejeitarId = id;
        document.getElementById('rejeitarMotivo').value = '';
        document.getElementById('rejeitarFeedback').className = 'modal-feedback';
        document.getElementById('rejeitarOverlay').classList.add('open');
    }

    async function _confirmarRejeitar() {
        if (!_rejeitarId) return;
        const motivo    = document.getElementById('rejeitarMotivo').value.trim();
        const btn       = document.getElementById('rejeitarConfirmarBtn');
        const fb        = document.getElementById('rejeitarFeedback');
        const idAtual   = _rejeitarId;

        // Feedback visual imediato na linha da solicitação
        const itemEl    = document.getElementById(`solic-${idAtual}`);
        const acoesEl   = itemEl?.querySelector('.solic-acoes');
        const statusEl  = itemEl?.querySelector('.solic-status');
        if (acoesEl)  acoesEl.innerHTML  = '<i class="fas fa-spinner fa-spin" style="color:var(--muted)"></i>';
        if (statusEl) { statusEl.textContent = 'Rejeitando...'; statusEl.className = 'solic-status solic-status--rejeitado'; }

        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rejeitando...';
        fb.className  = 'modal-feedback';

        try {
            const res  = await fetch(`${BASE_URL}/api/chat/solicitacoes/${idAtual}/rejeitar`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ motivo }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Erro ao rejeitar.');

            // Fecha modal imediatamente e reseta estado
            document.getElementById('rejeitarOverlay').classList.remove('open');
            _rejeitarId = null;

            // Recarrega a lista com um pequeno delay para o banco confirmar
            setTimeout(() => carregar(true), 300);
        } catch(e) {
            fb.className   = 'modal-feedback error';
            fb.textContent = e.message;
            // Restaura botões da linha em caso de erro
            if (acoesEl) acoesEl.innerHTML = _botoesAcao({ id: idAtual, status: 'pendente',
                gestor_destino_user_id: null, aprovado_gestor_destino_at: null,
                aprovado_admin_at: null, solicitante_user_id: null });
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-times-circle"></i> Confirmar Rejeição';
        }
    }

    async function executar(id) {
        const btn = document.querySelector(`#solic-${id} .solic-btn--executar`);
        if (!btn) return;
        const textoOriginal = btn.innerHTML;
        btn.innerHTML = `
            <span style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
                Confirmar?
                <button class="solic-btn solic-btn--aprovar" style="padding:.2rem .5rem;font-size:.72rem"
                    id="confirmarExec-${id}">Sim</button>
                <button class="solic-btn" style="padding:.2rem .5rem;font-size:.72rem;background:var(--surface);color:var(--text)"
                    id="cancelarExec-${id}">Não</button>
            </span>`;

        document.getElementById(`cancelarExec-${id}`)?.addEventListener('click', () => {
            btn.innerHTML = textoOriginal;
        });
        document.getElementById(`confirmarExec-${id}`)?.addEventListener('click', async () => {
            btn.disabled  = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Executando...';
            try {
                const res  = await fetch(`${BASE_URL}/api/chat/solicitacoes/${id}/executar`, { method: 'POST' });
                const json = await res.json();
                if (!res.ok) throw new Error(json.error ?? 'Erro ao executar.');
                Toast.success('Transferência executada!', 'O colaborador foi movido para a nova unidade.');
                await carregar(true);
            } catch(e) {
                Toast.error('Erro ao executar', e.message);
                btn.disabled  = false;
                btn.innerHTML = textoOriginal;
            }
        });
    }

    function iniciar() {
        document.getElementById('solicRefreshBtn')?.addEventListener('click', () => carregar(true));

        document.getElementById('rejeitarFecharBtn')?.addEventListener('click', () => {
            document.getElementById('rejeitarOverlay').classList.remove('open');
        });
        document.getElementById('rejeitarCancelarBtn')?.addEventListener('click', () => {
            document.getElementById('rejeitarOverlay').classList.remove('open');
        });
        document.getElementById('rejeitarConfirmarBtn')?.addEventListener('click', _confirmarRejeitar);
        document.getElementById('rejeitarOverlay')?.addEventListener('click', e => {
            if (e.target === document.getElementById('rejeitarOverlay'))
                document.getElementById('rejeitarOverlay').classList.remove('open');
        });
    }

    return { carregar, aprovar, abrirRejeitar, executar, iniciar };
})();

/* ══════════════════════════════════════════════════════════════════════
   MODAL: SOLICITAR TRANSFERÊNCIA
   ══════════════════════════════════════════════════════════════════════ */
const Transferencia = (() => {
    async function abrir() {
        const overlay = document.getElementById('transfOverlay');
        if (!overlay) return;
        document.getElementById('transfFeedback').className   = 'modal-feedback';
        document.getElementById('transfFeedback').textContent = '';
        document.getElementById('transfMotivo').value = '';
        overlay.classList.add('open');
        await Promise.all([_carregarGestores(), _carregarColaboradores()]);
    }

    async function _carregarGestores() {
        const sel = document.getElementById('transfGestor');
        sel.innerHTML = '<option value="">Carregando...</option>';
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/contatos`);
            const json = await res.json();
            const gest = (json.data ?? []).filter(c => c.role === 'gestor');
            if (!gest.length) {
                sel.innerHTML = '<option value="">Nenhum gestor disponível</option>';
                return;
            }
            sel.innerHTML = '<option value="">Selecione o gestor de destino...</option>'
                + gest.map(g => `<option value="${g.id}" data-unidade="${g.unidade_id ?? ''}">${g.nome}${g.unidade_sigla ? ' · ' + g.unidade_sigla : ''}</option>`).join('');
        } catch { sel.innerHTML = '<option value="">Erro ao carregar</option>'; }
    }

    async function _carregarColaboradores() {
        const sel = document.getElementById('transfServidor');
        sel.innerHTML = '<option value="">Carregando...</option>';
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/colaboradores`);
            const json = await res.json();
            const cols = json.data ?? [];
            if (!cols.length) {
                sel.innerHTML = '<option value="">Nenhum colaborador encontrado</option>';
                return;
            }
            sel.innerHTML = '<option value="">Selecione o colaborador...</option>'
                + cols.map(c => `<option value="${c.servidor_id}">${c.nome}</option>`).join('');
        } catch { sel.innerHTML = '<option value="">Erro ao carregar</option>'; }
    }

    async function _enviar() {
        const gestorEl     = document.getElementById('transfGestor');
        const gestorUserId = parseInt(gestorEl.value);
        const servidorId   = parseInt(document.getElementById('transfServidor').value);
        const motivo       = document.getElementById('transfMotivo').value.trim();
        const fb           = document.getElementById('transfFeedback');
        const btn          = document.getElementById('transfEnviarBtn');

        if (!gestorUserId || !servidorId) {
            fb.className   = 'modal-feedback error';
            fb.textContent = 'Selecione o gestor de destino e o colaborador.';
            return;
        }

        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        fb.className  = 'modal-feedback';

        try {
            /* Abre/cria a conversa para obter unidade_destino_id (backend ainda usa essa lógica) */
            const resConv = await fetch(`${BASE_URL}/api/chat/conversas`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ user_id: gestorUserId }),
            });
            const jsonConv = await resConv.json();
            if (!jsonConv.conversa_id) throw new Error('Não foi possível iniciar a solicitação.');

            const res  = await fetch(`${BASE_URL}/api/chat/solicitacao-transferencia`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    servidor_id:        servidorId,
                    unidade_destino_id: jsonConv.unidade_destino_id,
                    conversa_id:        jsonConv.conversa_id,
                    motivo,
                }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Erro ao enviar.');

            fb.className   = 'modal-feedback success';
            fb.textContent = 'Solicitação enviada! Aguardando aprovações.';
            setTimeout(() => fechar(), 1800);
        } catch(e) {
            fb.className   = 'modal-feedback error';
            fb.textContent = e.message;
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitação';
        }
    }

    function fechar() {
        document.getElementById('transfOverlay')?.classList.remove('open');
    }

    function iniciar() {
        document.getElementById('transfFecharBtn')?.addEventListener('click', fechar);
        document.getElementById('transfCancelarBtn')?.addEventListener('click', fechar);
        document.getElementById('transfEnviarBtn')?.addEventListener('click', _enviar);
        document.getElementById('transfOverlay')?.addEventListener('click', e => {
            if (e.target === document.getElementById('transfOverlay')) fechar();
        });
    }

    return { abrir, iniciar };
})();

/* ══════════════════════════════════════════════════════════════════════
   MODAL: ENVIAR AVISO
   ══════════════════════════════════════════════════════════════════════ */
const Aviso = (() => {
    function abrir() {
        document.getElementById('avisoTitulo').value   = '';
        document.getElementById('avisoMensagem').value = '';
        const fb = document.getElementById('avisoFeedback');
        fb.className   = 'modal-feedback';
        fb.textContent = '';
        document.getElementById('avisoOverlay').classList.add('open');
        setTimeout(() => document.getElementById('avisoTitulo').focus(), 100);
    }

    function fechar() {
        document.getElementById('avisoOverlay').classList.remove('open');
    }

    async function enviar() {
        const titulo   = document.getElementById('avisoTitulo').value.trim();
        const mensagem = document.getElementById('avisoMensagem').value.trim();
        const fb       = document.getElementById('avisoFeedback');
        const btn      = document.getElementById('avisoEnviarBtn');

        if (!titulo || !mensagem) {
            fb.className   = 'modal-feedback error';
            fb.textContent = 'Título e mensagem são obrigatórios.';
            return;
        }

        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        fb.className  = 'modal-feedback';

        try {
            const res  = await fetch(`${BASE_URL}/api/notificacoes/aviso`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ titulo, mensagem }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Erro ao enviar.');
            fb.className   = 'modal-feedback success';
            fb.textContent = json.mensagem;
            setTimeout(() => fechar(), 1800);
        } catch(e) {
            fb.className   = 'modal-feedback error';
            fb.textContent = e.message;
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Aviso';
        }
    }

    function iniciar() {
        document.getElementById('avisoFecharBtn')?.addEventListener('click', fechar);
        document.getElementById('avisoCancelarBtn')?.addEventListener('click', fechar);
        document.getElementById('avisoEnviarBtn')?.addEventListener('click', enviar);
        document.getElementById('avisoOverlay')?.addEventListener('click', e => {
            if (e.target === document.getElementById('avisoOverlay')) fechar();
        });
    }

    return { abrir, iniciar };
})();

/* ══════════════════════════════════════════════════════════════════════
   MODAL: CHAMADO DE EDIÇÃO DE PERFIL
   ══════════════════════════════════════════════════════════════════════ */
const Chamado = (() => {
    function abrir() {
        const motivoEl  = document.getElementById('chamadoMotivo');
        const fbEl      = document.getElementById('chamadoFeedback');
        const overlayEl = document.getElementById('chamadoOverlay');
        if (!motivoEl || !overlayEl) return;
        motivoEl.value   = '';
        fbEl.className   = 'modal-feedback';
        fbEl.textContent = '';
        overlayEl.classList.add('open');
        setTimeout(() => motivoEl.focus(), 100);
    }

    function fechar() {
        document.getElementById('chamadoOverlay').classList.remove('open');
    }

    async function enviar() {
        const motivo = document.getElementById('chamadoMotivo').value.trim();
        const fb     = document.getElementById('chamadoFeedback');
        const btn    = document.getElementById('chamadoEnviarBtn');
        if (!motivo) {
            fb.className   = 'modal-feedback error';
            fb.textContent = 'Descreva o que precisa ser alterado.';
            return;
        }
        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        fb.className  = 'modal-feedback';
        try {
            const res  = await fetch(`${BASE_URL}/api/chamados`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ motivo }),
            });
            const text = await res.text();
            let json;
            try {
                json = JSON.parse(text);
            } catch (_) {
                fb.className   = 'modal-feedback error';
                fb.textContent = 'Erro do servidor: ' + text.substring(0, 300);
                console.error('Resposta não-JSON:', text);
                return;
            }
            if (!res.ok) throw new Error(json.error ?? 'Erro ao enviar.');
            fb.className   = 'modal-feedback success';
            fb.textContent = 'Solicitação enviada! Aguardando aprovação.';
            setTimeout(() => fechar(), 1800);
        } catch(e) {
            fb.className   = 'modal-feedback error';
            fb.textContent = e.message;
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitação';
        }
    }

    async function abrirAprovar(chamadoId) {
        try {
            const res  = await fetch(`${BASE_URL}/api/chamados/${chamadoId}`);
            const json = await res.json();
            if (!res.ok) throw new Error('Chamado não encontrado.');
            const c = json.data;
            if (c.status === 'concluido' || c.status === 'rejeitado') {
                Toast.info('Chamado já processado',
                    `Este chamado já foi ${c.status === 'concluido' ? 'concluído' : 'rejeitado'}.`);
                return;
            }
            document.getElementById('modalEdicao').dataset.chamadoId = chamadoId;
            await editarUsuario(c.user_id, false);
            const card = document.getElementById('editIdentityCard');
            let banner = document.getElementById('chamadoBanner');
            if (!banner) {
                banner = document.createElement('div');
                banner.id = 'chamadoBanner';
                banner.style.cssText = `
                    background:var(--surface);border-left:3px solid #a78bfa;
                    padding:.6rem 1rem;font-size:.82rem;color:var(--text);
                    margin-bottom:.75rem;border-radius:.4rem;`;
                card.parentNode.insertBefore(banner, card.nextSibling);
            }
            banner.innerHTML = `
                <i class="fas fa-pen-to-square" style="color:#a78bfa;margin-right:.4rem"></i>
                <strong>Chamado de edição</strong> — ${c.user_nome} solicitou:
                <em style="color:var(--muted)">"${c.motivo ?? 'sem descrição'}"</em>`;
        } catch(e) {
            Toast.error('Erro ao carregar chamado: ' + e.message);
        }
    }

    function iniciar() {
        document.getElementById('chamadoFecharBtn')?.addEventListener('click', fechar);
        document.getElementById('chamadoCancelarBtn')?.addEventListener('click', fechar);
        document.getElementById('chamadoEnviarBtn')?.addEventListener('click', enviar);
        document.getElementById('chamadoOverlay')?.addEventListener('click', e => {
            if (e.target === document.getElementById('chamadoOverlay')) fechar();
        });
    }

    return { abrir, fechar, enviar, abrirAprovar, iniciar };
})();

/* ── Init ────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    carregarPerfil();
    Notificacoes.iniciar();
    Solicitacoes.iniciar();
    Transferencia.iniciar();
    Aviso.iniciar();
    Chamado.iniciar();
});

window.Solicitacoes  = Solicitacoes;
window.Transferencia = Transferencia;
</script>