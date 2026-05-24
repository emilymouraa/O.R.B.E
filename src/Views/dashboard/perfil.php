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
            .notif-panel{
                display:none;position:fixed;top:0;right:0;height:100vh;width:380px;
                background:var(--card);border-left:1px solid var(--border);
                box-shadow:-8px 0 32px rgba(0,0,0,.18);z-index:500;
                flex-direction:column;overflow:hidden;
            }
            .notif-panel.open{display:flex}
            .notif-panel-header{
                display:flex;align-items:center;justify-content:space-between;
                padding:1rem 1.25rem;border-bottom:1px solid var(--border);flex-shrink:0;
            }
            .notif-panel-title{font-weight:700;font-size:1rem;color:var(--text)}
            .notif-panel-close{
                background:none;border:none;cursor:pointer;color:var(--muted);
                font-size:1.1rem;padding:.25rem;border-radius:.35rem;transition:background .2s;
            }
            .notif-panel-close:hover{background:var(--surface)}

            .notif-tabs{
                display:flex;border-bottom:1px solid var(--border);flex-shrink:0;
            }
            .notif-tab{
                flex:1;padding:.65rem;background:none;border:none;cursor:pointer;
                font-size:.83rem;font-weight:600;color:var(--muted);
                border-bottom:2px solid transparent;transition:all .2s;
                display:flex;align-items:center;justify-content:center;gap:.4rem;
            }
            .notif-tab.active{color:var(--primary);border-bottom-color:var(--primary)}
            .notif-tab:hover:not(.active){background:var(--surface)}
            .notif-tab-badge{
                background:#ef4444;color:#fff;border-radius:999px;
                font-size:.65rem;font-weight:700;min-width:1.1rem;height:1.1rem;
                display:inline-flex;align-items:center;justify-content:center;padding:0 .25rem;
            }

            .notif-tab-content{display:none;flex:1;flex-direction:column;overflow:hidden}
            .notif-tab-content.active{display:flex}

            .notif-toolbar{
                display:flex;justify-content:space-between;align-items:center;
                padding:.6rem 1rem;border-bottom:1px solid var(--border);flex-shrink:0;
                font-size:.8rem;color:var(--muted);
            }
            .notif-mark-all{
                background:none;border:none;color:var(--primary);cursor:pointer;
                font-size:.78rem;padding:.2rem .4rem;border-radius:.3rem;transition:background .2s;
            }
            .notif-mark-all:hover{background:var(--surface)}
            .notif-list{list-style:none;margin:0;padding:0;overflow-y:auto;flex:1}
            .notif-item{
                padding:.75rem 1rem;border-bottom:1px solid var(--border);
                font-size:.82rem;display:flex;gap:.6rem;align-items:flex-start;
                cursor:pointer;transition:background .15s;
            }
            .notif-item:hover{background:var(--surface)}
            .notif-item.unread{background:var(--surface)}
            .notif-item.unread .notif-item-title::after{
                content:'•';color:var(--primary);margin-left:.35rem;font-size:.9rem;
            }
            .notif-item-icon{font-size:1rem;margin-top:.1rem;flex-shrink:0}
            .notif-item-icon.warn{color:#f59e0b}
            .notif-item-icon.danger{color:#ef4444}
            .notif-item-icon.info{color:var(--primary)}
            .notif-item-icon.success{color:#22c55e}
            .notif-item-body{display:flex;flex-direction:column;gap:.2rem;flex:1}
            .notif-item-title{font-weight:600;color:var(--text)}
            .notif-item-desc{color:var(--muted)}
            .notif-item-time{color:var(--muted);font-size:.72rem;margin-top:.1rem}
            .notif-empty{padding:2rem 1rem;text-align:center;color:var(--muted);font-size:.85rem}

            .chat-toolbar{
                display:flex;justify-content:space-between;align-items:center;
                padding:.6rem 1rem;border-bottom:1px solid var(--border);flex-shrink:0;
            }
            .chat-new-btn{
                background:none;border:none;color:var(--primary);cursor:pointer;
                font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;
                padding:.2rem .4rem;border-radius:.3rem;transition:background .2s;
            }
            .chat-new-btn:hover{background:var(--surface)}
            .chat-lista{overflow-y:auto;flex:1}
            .chat-conversa-item{
                display:flex;gap:.75rem;align-items:center;
                padding:.75rem 1rem;border-bottom:1px solid var(--border);
                cursor:pointer;transition:background .15s;
            }
            .chat-conversa-item:hover{background:var(--surface)}
            .chat-conversa-item.active{background:var(--surface)}
            .chat-conv-avatar{
                width:2.2rem;height:2.2rem;border-radius:50%;
                background:var(--primary);color:#fff;font-weight:700;
                font-size:.8rem;display:flex;align-items:center;justify-content:center;
                flex-shrink:0;overflow:hidden;
            }
            .chat-conv-avatar img{
                width:100%;height:100%;object-fit:cover;border-radius:50%;
            }
            .chat-screen-avatar{
                width:2rem;height:2rem;border-radius:50%;
                background:var(--primary);color:#fff;font-weight:700;
                font-size:.78rem;display:flex;align-items:center;justify-content:center;
                flex-shrink:0;overflow:hidden;
            }
            .chat-screen-avatar img{
                width:100%;height:100%;object-fit:cover;border-radius:50%;
            }
            .chat-conv-info{flex:1;min-width:0}
            .chat-conv-nome{font-weight:600;font-size:.85rem;color:var(--text)}
            .chat-conv-preview{
                font-size:.75rem;color:var(--muted);
                white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
            }
            .chat-conv-meta{display:flex;flex-direction:column;align-items:flex-end;gap:.2rem;flex-shrink:0}
            .chat-conv-time{font-size:.7rem;color:var(--muted)}
            .chat-conv-badge{
                background:#ef4444;color:#fff;border-radius:999px;
                font-size:.65rem;font-weight:700;min-width:1.1rem;height:1.1rem;
                display:flex;align-items:center;justify-content:center;padding:0 .2rem;
            }

            .chat-screen{display:none;flex:1;flex-direction:column;overflow:hidden}
            .chat-screen.open{display:flex}
            .chat-screen-header{
                display:flex;align-items:center;gap:.6rem;
                padding:.65rem 1rem;border-bottom:1px solid var(--border);flex-shrink:0;
            }
            .chat-back-btn{
                background:none;border:none;color:var(--muted);cursor:pointer;
                font-size:1rem;padding:.2rem;border-radius:.3rem;transition:background .2s;
            }
            .chat-back-btn:hover{background:var(--surface)}
            .chat-screen-nome{font-weight:600;font-size:.9rem;color:var(--text)}
            .chat-screen-sub{font-size:.72rem;color:var(--muted)}
            .chat-msgs{flex:1;overflow-y:auto;padding:.75rem 1rem;display:flex;flex-direction:column;gap:.5rem}
            .chat-msg{display:flex;flex-direction:column;max-width:80%}
            .chat-msg.minha{align-self:flex-end;align-items:flex-end}
            .chat-msg.deles{align-self:flex-start;align-items:flex-start}
            .chat-msg-bubble{
                padding:.5rem .75rem;border-radius:.75rem;font-size:.83rem;line-height:1.4;
                word-break:break-word;
            }
            .chat-msg.minha .chat-msg-bubble{background:var(--primary);color:#fff;border-bottom-right-radius:.2rem}
            .chat-msg.deles .chat-msg-bubble{background:var(--surface);color:var(--text);border-bottom-left-radius:.2rem}
            .chat-msg-time{font-size:.68rem;color:var(--muted);margin-top:.15rem;padding:0 .25rem}
            .chat-input-wrap{
                display:flex;gap:.5rem;padding:.75rem 1rem;
                border-top:1px solid var(--border);flex-shrink:0;
            }
            .chat-input{
                flex:1;padding:.5rem .75rem;border-radius:.5rem;
                border:1px solid var(--border);background:var(--surface);
                color:var(--text);font-size:.85rem;resize:none;
                font-family:inherit;line-height:1.4;max-height:100px;
            }
            .chat-input:focus{outline:none;border-color:var(--primary)}
            .chat-send-btn{
                background:var(--primary);color:#fff;border:none;
                border-radius:.5rem;padding:.5rem .75rem;cursor:pointer;
                font-size:.9rem;transition:opacity .2s;flex-shrink:0;
            }
            .chat-send-btn:hover{opacity:.85}
            .chat-send-btn:disabled{opacity:.5;cursor:not-allowed}

            .notif-overlay{
                display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:499;
            }
            .notif-overlay.open{display:block}
            .transf-overlay{
                display:none;position:fixed;inset:0;
                background:rgba(0,0,0,.45);z-index:600;
                align-items:center;justify-content:center;
            }
            .transf-overlay.open{display:flex}
            .transf-modal{
                background:var(--card);border:1px solid var(--border);
                border-radius:.85rem;width:100%;max-width:420px;
                box-shadow:0 8px 32px rgba(0,0,0,.2);overflow:hidden;
            }
            .transf-modal-header{
                display:flex;justify-content:space-between;align-items:center;
                padding:.85rem 1.1rem;border-bottom:1px solid var(--border);
            }
            .transf-modal-header h3{font-size:.95rem;font-weight:700;color:var(--text);margin:0}
            .transf-modal-body{padding:1.1rem;display:flex;flex-direction:column;gap:.85rem}
            .transf-field label{
                display:block;font-size:.75rem;font-weight:600;
                color:var(--muted);margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.03em;
            }
            .transf-field select,
            .transf-field textarea{
                width:100%;padding:.55rem .75rem;border-radius:.5rem;
                border:1px solid var(--border);background:var(--surface);
                color:var(--text);font-size:.85rem;font-family:inherit;
                box-sizing:border-box;
            }
            .transf-field textarea{resize:vertical;min-height:80px}
            .transf-field select:focus,
            .transf-field textarea:focus{outline:none;border-color:var(--primary)}
            .transf-modal-footer{
                display:flex;gap:.6rem;justify-content:flex-end;
                padding:.85rem 1.1rem;border-top:1px solid var(--border);
            }
            .transf-feedback{
                font-size:.8rem;padding:.5rem .75rem;border-radius:.4rem;display:none;
            }
            .transf-feedback.error{display:block;background:#fee2e2;color:#dc2626}
            .transf-feedback.success{display:block;background:#dcfce7;color:#16a34a}
            .solic-item{
            padding:.85rem 1rem;border-bottom:1px solid var(--border);
                display:flex;flex-direction:column;gap:.5rem;
            }
            .solic-header{display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem}
            .solic-nome{font-weight:700;font-size:.85rem;color:var(--text)}
            .solic-status{
                font-size:.7rem;font-weight:700;padding:.15rem .45rem;
                border-radius:999px;white-space:nowrap;flex-shrink:0;
            }
            .solic-status--pendente{background:#fef9c3;color:#854d0e}
            .solic-status--aprovado_gestor_destino{background:#dbeafe;color:#1d4ed8}
            .solic-status--aprovado_admin{background:#dbeafe;color:#1d4ed8}
            .solic-status--aprovado{background:#dcfce7;color:#15803d}
            .solic-status--rejeitado{background:#fee2e2;color:#dc2626}
            .solic-status--executado{background:#f3f4f6;color:#6b7280}
            .solic-rota{font-size:.78rem;color:var(--muted)}
            .solic-meta{font-size:.72rem;color:var(--muted)}
            .solic-motivo{
                font-size:.78rem;color:var(--text);
                background:var(--surface);padding:.35rem .6rem;
                border-radius:.4rem;border-left:3px solid var(--primary);
            }
            .solic-acoes{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.25rem}
            .solic-btn{
                padding:.35rem .75rem;border-radius:.4rem;border:none;
                font-size:.78rem;font-weight:600;cursor:pointer;transition:opacity .2s;
            }
            .solic-btn:hover{opacity:.85}
            .solic-btn--aprovar{background:#22c55e;color:#fff}
            .solic-btn--rejeitar{background:#ef4444;color:#fff}
            .solic-btn--executar{background:var(--primary);color:#fff}
            .solic-btn--disabled{background:var(--surface);color:var(--muted);cursor:not-allowed}

            /* ── Modal rejeição ─────────────────────────────────────────── */
            .rejeitar-overlay{
                display:none;position:fixed;inset:0;
                background:rgba(0,0,0,.45);z-index:700;
                align-items:center;justify-content:center;
            }
            .rejeitar-overlay.open{display:flex}
            .rejeitar-modal{
                background:var(--card);border:1px solid var(--border);
                border-radius:.85rem;width:100%;max-width:380px;
                box-shadow:0 8px 32px rgba(0,0,0,.2);overflow:hidden;
            }
            .rejeitar-modal-header{
                display:flex;justify-content:space-between;align-items:center;
                padding:.85rem 1.1rem;border-bottom:1px solid var(--border);
            }
            .rejeitar-modal-header h3{font-size:.9rem;font-weight:700;color:var(--text);margin:0}
            .rejeitar-modal-body{padding:1rem;display:flex;flex-direction:column;gap:.75rem}
            .rejeitar-modal-footer{
                display:flex;gap:.6rem;justify-content:flex-end;
                padding:.85rem 1.1rem;border-top:1px solid var(--border);
            }
            .chat-msg.sistema{
                align-self:center;align-items:center;max-width:95%;
            }
            .chat-msg.sistema .chat-msg-bubble{
                background:var(--surface);color:var(--muted);
                border:1px solid var(--border);border-radius:.6rem;
                font-size:.78rem;text-align:center;padding:.5rem .85rem;
                border-left:3px solid var(--primary);
                white-space:pre-line;
            }
            .chat-msg.sistema .chat-msg-time{
                text-align:center;
            }
        </style>
        <div class="title-group">
            <h1>Meu Perfil</h1>
            <p>Consulte suas informações cadastrais e de acesso</p>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem">
            <div class="notif-overlay" id="notifOverlay"></div>
            <div style="position:relative" id="notifWrapper">
                <button id="notifBtn" title="Notificações e Mensagens"
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
            <div class="notif-panel" id="notifPanel">
                <div class="notif-panel-header">
                    <span class="notif-panel-title">Central de Avisos</span>
                    <button class="notif-panel-close" id="notifPanelClose" title="Fechar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="notif-tabs">
                    <button class="notif-tab active" data-tab="notificacoes">
                        <i class="fas fa-bell"></i> Notificações
                        <span class="notif-tab-badge" id="tabBadgeNotif" style="display:none"></span>
                    </button>
                    <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gestor'])): ?>
                    <button class="notif-tab" data-tab="mensagens">
                        <i class="fas fa-comments"></i> Mensagens
                        <span class="notif-tab-badge" id="tabBadgeChat" style="display:none"></span>
                    </button>
                    <button class="notif-tab" data-tab="solicitacoes">
                        <i class="fas fa-exchange-alt"></i> Solicitações
                        <span class="notif-tab-badge" id="tabBadgeSolic" style="display:none"></span>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="notif-tab-content active" id="tabContent-notificacoes">
                    <div class="notif-toolbar">
                        <span id="notifHeaderCount" style="font-size:.78rem"></span>
                        <div style="display:flex;gap:.5rem;align-items:center">
                            <button class="chat-new-btn" id="btnAbrirChamado" style="color:#a78bfa">
                                <i class="fas fa-pen-to-square"></i> Editar Perfil
                            </button>
                            <button class="notif-mark-all" id="notifMarkAll">Marcar todas lidas</button>
                        </div>
                    </div>
                    <ul class="notif-list" id="notifList">
                        <li class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</li>
                    </ul>
                </div>

                <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gestor'])): ?>
                <div class="notif-tab-content" id="tabContent-mensagens">
                    <div id="chatLista" style="display:flex;flex-direction:column;flex:1;overflow:hidden">
                        <div class="chat-toolbar" style="flex-wrap:wrap;gap:.4rem">
                            <span style="font-size:.8rem;color:var(--muted);flex:1">Conversas</span>
                            <button class="chat-new-btn" id="chatNovaBtn">
                                <i class="fas fa-plus"></i> Nova
                            </button>
                            <?php if (($_SESSION['user']['role'] ?? '') === 'gestor'): ?>
                            <button class="chat-new-btn" id="chatBtnSolicitarTransf" style="color:#60a5fa">
                                <i class="fas fa-exchange-alt"></i> Transferência
                            </button>
                            <?php endif; ?>
                            <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'gestor'])): ?>
                            <button class="chat-new-btn" id="chatBtnEnviarAviso" style="color:#f59e0b">
                                <i class="fas fa-bullhorn"></i> Aviso
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="chat-lista" id="chatConversaLista">
                            <div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
                        </div>
                    </div>
                    <div class="chat-screen" id="chatScreen">
                        <div class="chat-screen-header">
                            <button class="chat-back-btn" id="chatBackBtn">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <div class="chat-screen-avatar" id="chatScreenAvatar"></div>
                            <div style="flex:1">
                                <div class="chat-screen-nome" id="chatScreenNome">—</div>
                                <div class="chat-screen-sub" id="chatScreenSub"></div>
                            </div>
                            <?php if (($_SESSION['user']['role'] ?? '') === 'gestor'): ?>
                            <?php endif; ?>
                        </div>
                        <div class="chat-msgs" id="chatMsgs"></div>
                        <div class="chat-input-wrap">
                            <textarea class="chat-input" id="chatInput"
                                placeholder="Digite uma mensagem..." rows="1"></textarea>
                        </div>
                    </div>
                    <div class="chat-screen" id="chatNovoScreen">
                        <div class="chat-screen-header">
                            <button class="chat-back-btn" id="chatNovoBackBtn">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <div>
                                <div class="chat-screen-nome">Nova conversa</div>
                            </div>
                        </div>
                        <div class="chat-lista" id="chatContatosLista">
                            <div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
                        </div>
                    </div>
                </div>
                <div class="notif-tab-content" id="tabContent-solicitacoes">
                    <div class="chat-toolbar">
                        <span style="font-size:.8rem;color:var(--muted)">Transferências pendentes</span>
                        <button class="chat-new-btn" id="solicRefreshBtn">
                            <i class="fas fa-rotate-right"></i> Atualizar
                        </button>
                    </div>
                    <div style="overflow-y:auto;flex:1" id="solicLista">
                        <div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="transf-overlay" id="transfOverlay">
                <div class="transf-modal">
                    <div class="transf-modal-header">
                        <h3><i class="fas fa-exchange-alt" style="color:var(--primary);margin-right:.4rem"></i>Solicitar Transferência</h3>
                        <button class="chat-back-btn" id="transfFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="transf-modal-body">
                        <p style="font-size:.82rem;color:var(--muted);margin:0">
                            Selecione o colaborador, o gestor de destino e o motivo.
                            A solicitação será enviada ao gestor de destino e ao administrador para aprovação.
                        </p>
                        <div class="transf-field">
                            <label>Gestor de Destino *</label>
                            <select id="transfGestor">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                        <div class="transf-field">
                            <label>Colaborador *</label>
                            <select id="transfServidor">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                        <div class="transf-field">
                            <label>Motivo</label>
                            <textarea id="transfMotivo" placeholder="Descreva o motivo da solicitação..."></textarea>
                        </div>
                        <div class="transf-feedback" id="transfFeedback"></div>
                    </div>
                    <div class="transf-modal-footer">
                        <button class="btn-cancel" id="transfCancelarBtn">Cancelar</button>
                        <button class="btn-save" id="transfEnviarBtn">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitação
                        </button>
                    </div>
                </div>
            </div>
            <div class="rejeitar-overlay" id="rejeitarOverlay">
                <div class="rejeitar-modal">
                    <div class="rejeitar-modal-header">
                        <h3><i class="fas fa-times-circle" style="color:#ef4444;margin-right:.4rem"></i>Rejeitar Solicitação</h3>
                        <button class="chat-back-btn" id="rejeitarFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="rejeitar-modal-body">
                        <p style="font-size:.82rem;color:var(--muted);margin:0">
                            Informe o motivo da rejeição (opcional):
                        </p>
                        <div class="transf-field">
                            <textarea id="rejeitarMotivo" placeholder="Motivo da rejeição..."
                                style="min-height:80px"></textarea>
                        </div>
                        <div class="transf-feedback" id="rejeitarFeedback"></div>
                    </div>
                    <div class="rejeitar-modal-footer">
                        <button class="btn-cancel" id="rejeitarCancelarBtn">Cancelar</button>
                        <button class="solic-btn solic-btn--rejeitar" id="rejeitarConfirmarBtn">
                            <i class="fas fa-times-circle"></i> Confirmar Rejeição
                        </button>
                    </div>
                </div>
            </div>
            <div class="transf-overlay" id="avisoOverlay">
                <div class="transf-modal">
                    <div class="transf-modal-header">
                        <h3><i class="fas fa-bullhorn" style="color:#f59e0b;margin-right:.4rem"></i>
                            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                                Enviar Aviso aos Gestores
                            <?php else: ?>
                                Enviar Aviso à Equipe
                            <?php endif; ?>
                        </h3>
                        <button class="chat-back-btn" id="avisoFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="transf-modal-body">
                        <p style="font-size:.82rem;color:var(--muted);margin:0">
                            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                                Esta mensagem será enviada como notificação para todos os gestores ativos.
                            <?php else: ?>
                                Esta mensagem será enviada como notificação para todos os colaboradores da sua unidade.
                            <?php endif; ?>
                        </p>
                        <div class="transf-field">
                            <label>Título *</label>
                            <input type="text" id="avisoTitulo" placeholder="Título do aviso..."
                                style="width:100%;padding:.55rem .75rem;border-radius:.5rem;
                                    border:1px solid var(--border);background:var(--surface);
                                    color:var(--text);font-size:.85rem;box-sizing:border-box">
                        </div>
                        <div class="transf-field">
                            <label>Mensagem *</label>
                            <textarea id="avisoMensagem" placeholder="Digite a mensagem do aviso..."></textarea>
                        </div>
                        <div class="transf-feedback" id="avisoFeedback"></div>
                    </div>
                    <div class="transf-modal-footer">
                        <button class="btn-cancel" id="avisoCancelarBtn">Cancelar</button>
                        <button class="btn-save" id="avisoEnviarBtn">
                            <i class="fas fa-paper-plane"></i> Enviar Aviso
                        </button>
                    </div>
                </div>
            </div>
            <div class="transf-overlay" id="chamadoOverlay">
                <div class="transf-modal">
                    <div class="transf-modal-header">
                        <h3><i class="fas fa-pen-to-square" style="color:#a78bfa;margin-right:.4rem"></i>Solicitar Edição de Perfil</h3>
                        <button class="chat-back-btn" id="chamadoFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="transf-modal-body">
                        <p style="font-size:.82rem;color:var(--muted);margin:0">
                            Preencha apenas os campos que deseja alterar. Deixe em branco o que não precisa mudar.
                        </p>
                        <div class="transf-field">
                            <label>Nome Completo</label>
                            <input type="text" id="chamadoNome" placeholder="Novo nome completo..."
                                style="width:100%;padding:.55rem .75rem;border-radius:.5rem;
                                    border:1px solid var(--border);background:var(--surface);
                                    color:var(--text);font-size:.85rem;box-sizing:border-box">
                        </div>
                        <div class="transf-field">
                            <label>E-mail Institucional</label>
                            <input type="email" id="chamadoEmail" placeholder="novo@email.com"
                                style="width:100%;padding:.55rem .75rem;border-radius:.5rem;
                                    border:1px solid var(--border);background:var(--surface);
                                    color:var(--text);font-size:.85rem;box-sizing:border-box">
                        </div>
                        <div class="transf-field">
                            <label>Data de Nascimento</label>
                            <input type="date" id="chamadoNascimento"
                                style="width:100%;padding:.55rem .75rem;border-radius:.5rem;
                                    border:1px solid var(--border);background:var(--surface);
                                    color:var(--text);font-size:.85rem;box-sizing:border-box">
                        </div>
                        <div class="transf-field">
                            <label>Motivo da solicitação</label>
                            <textarea id="chamadoMotivo" placeholder="Explique o motivo da alteração..."></textarea>
                        </div>
                        <div class="transf-feedback" id="chamadoFeedback"></div>
                    </div>
                    <div class="transf-modal-footer">
                        <button class="btn-cancel" id="chamadoCancelarBtn">Cancelar</button>
                        <button class="btn-save" id="chamadoEnviarBtn">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitação
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Aprovar Chamado (gestor/admin) -->
            <div class="transf-overlay" id="chamadoAprovarOverlay">
                <div class="transf-modal" style="max-width:480px">
                    <div class="transf-modal-header">
                        <h3><i class="fas fa-clipboard-check" style="color:#22c55e;margin-right:.4rem"></i>Revisar Chamado</h3>
                        <button class="chat-back-btn" id="chamadoAprovarFecharBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="transf-modal-body">
                        <div id="chamadoAprovarInfo" style="font-size:.83rem;color:var(--muted);margin-bottom:.5rem"></div>
                        <div class="transf-field">
                            <label>Nome</label>
                            <input type="text" id="chamadoAprovarNome"
                                style="width:100%;padding:.55rem .75rem;border-radius:.5rem;
                                    border:1px solid var(--border);background:var(--surface);
                                    color:var(--text);font-size:.85rem;box-sizing:border-box">
                        </div>
                        <div class="transf-field">
                            <label>E-mail</label>
                            <input type="email" id="chamadoAprovarEmail"
                                style="width:100%;padding:.55rem .75rem;border-radius:.5rem;
                                    border:1px solid var(--border);background:var(--surface);
                                    color:var(--text);font-size:.85rem;box-sizing:border-box">
                        </div>
                        <div class="transf-field">
                            <label>Data de Nascimento</label>
                            <input type="date" id="chamadoAprovarNascimento"
                                style="width:100%;padding:.55rem .75rem;border-radius:.5rem;
                                    border:1px solid var(--border);background:var(--surface);
                                    color:var(--text);font-size:.85rem;box-sizing:border-box">
                        </div>
                        <div class="transf-field">
                            <label>Observação (opcional)</label>
                            <textarea id="chamadoAprovarObs" placeholder="Observação sobre a edição..."></textarea>
                        </div>
                        <div class="transf-feedback" id="chamadoAprovarFeedback"></div>
                    </div>
                    <div class="transf-modal-footer">
                        <button class="solic-btn solic-btn--rejeitar" id="chamadoRejeitarBtn">
                            <i class="fas fa-times"></i> Rejeitar
                        </button>
                        <button class="btn-save" id="chamadoConcluirBtn">
                            <i class="fas fa-check"></i> Confirmar Edição
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

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

        <div class="perfil-upload-preview" id="uploadPreview" style="display:none">
            <img id="previewImg" src="" alt="Preview">
            <div class="perfil-upload-actions">
                <button class="btn-new" id="btnSalvarFoto">
                    <i class="fas fa-check"></i> Salvar foto
                </button>
                <button class="btn-cancel" id="btnCancelarFoto">Cancelar</button>
            </div>
            <p class="perfil-upload-hint">JPG, PNG ou WEBP · máx. 2 MB</p>
        </div>

        <div class="perfil-info-grid">
            <!-- Card 1: Dados Funcionais -->
            <div class="perfil-info-card">
                <div class="perfil-info-card-header">
                    <span class="perfil-info-icon perfil-info-icon--blue">
                        <i class="fas fa-id-badge"></i>
                    </span>
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
                    <span class="perfil-info-icon perfil-info-icon--green">
                        <i class="fas fa-building"></i>
                    </span>
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
                    <span class="perfil-info-icon perfil-info-icon--yellow">
                        <i class="fas fa-user"></i>
                    </span>
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
                <span class="perfil-info-icon perfil-info-icon--shield">
                    <i class="fas fa-shield-alt"></i>
                </span>
                <h3>Segurança e Privacidade</h3>
            </div>
            <div class="perfil-security-row">
                <div class="security-item">
                    <div class="security-item-icon security-item-icon--green">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="security-item-text">
                        <strong>Proteção de dados</strong>
                        <p>Dados armazenados com segurança e utilizados apenas para fins institucionais, conforme a LGPD.</p>
                    </div>
                </div>
                <div class="security-item">
                    <div class="security-item-icon security-item-icon--yellow">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="security-item-text">
                        <strong>Alterações no perfil</strong>
                        <p>Alterações cadastrais são registradas em log de auditoria e requerem permissão de administrador.</p>
                    </div>
                </div>
                <div class="security-item">
                    <div class="security-item-icon security-item-icon--blue">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="security-item-text">
                        <strong>Senhas seguras</strong>
                        <p>Use senhas fortes e únicas. Em caso de comprometimento, solicite a redefinição ao administrador.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn-theme-fixed" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>
</div><!-- /main-content -->

<?php require __DIR__ . '/../layout/footer.php'; ?>

<script>
window.__userId = <?= (int) ($_SESSION['user']['id'] ?? 0) ?>;

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
function dataBRparaISO(str) {
    if (!str || str === '—') return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;
    const [d, m, y] = str.split('/');
    return `${y}-${m}-${d}`;
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

/* ── Carregar perfil ─────────────────────────────────────────── */
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
        if (u.perfil_raw === 'admin') {
            document.getElementById('avatarUploadLabel').style.display = 'flex';
        }

        loading.style.display = 'none';
        content.style.display = 'block';
    } catch (err) {
        console.error(err);
        loading.style.display = 'none';
        error.style.display   = 'flex';
    }
}

/* ── Toggle CPF ─────────────────────────────────────────────── */
const btnToggleCpf = document.getElementById('btnToggleCpf');
if (btnToggleCpf) {
    btnToggleCpf.addEventListener('click', () => {
        _cpfVisivel = !_cpfVisivel;
        document.getElementById('detCPF').textContent = _cpfVisivel ? _cpfReal : _cpfOculto;
        const icon = document.getElementById('iconCpf');
        if (icon) {
            icon.classList.toggle('fa-eye',      !_cpfVisivel);
            icon.classList.toggle('fa-eye-slash', _cpfVisivel);
        }
    });
}

/* ── Upload de foto ──────────────────────────────────────────── */
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
        const avatarEl = document.getElementById('perfilAvatar');
        avatarEl.innerHTML = `<img src="${json.foto_url}?t=${Date.now()}" alt="Avatar" class="perfil-avatar-img">`;
        document.getElementById('uploadPreview').style.display = 'none';
        input.value = '';
    } catch (err) {
        alert(err.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Salvar foto';
    }
});

let _lidasLocalmente = new Set();

const Notificacoes = (() => {
    const POLL_INTERVAL = 10_000;
    let _timer = null;
    let _dados = [];

    const iconMap = {
        competencia_vencida:       { cls: 'danger',  icon: 'fa-certificate'  },
        promocao:                  { cls: 'success', icon: 'fa-arrow-up'      },
        transferencia:             { cls: 'info',    icon: 'fa-exchange-alt'  },
        aviso_gestor:              { cls: 'warn',    icon: 'fa-bullhorn'       },
        aviso_admin:               { cls: 'warn',    icon: 'fa-bullhorn'       },
        solicitacao_transferencia: { cls: 'info',    icon: 'fa-paper-plane'   },
        aprovacao_transferencia:   { cls: 'success', icon: 'fa-check-circle'  },
        default:                   { cls: 'info',    icon: 'fa-bell'           },
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
            return `
            <li class="notif-item ${unread}" data-id="${n.id}" data-lida="${n.lida}"
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
                const role    = '<?= $_SESSION['user']['role'] ?? 'user' ?>';
                if (refTipo === 'chamados_edicao' && refId && (role === 'gestor' || role === 'admin')) {
                    Chamado.abrirAprovar(refId);
                }
            });
        });
    }

    async function _marcarTodasLidas() {
        try {
            await fetch(`${BASE_URL}/api/notificacoes/todas-lidas`, { method: 'PATCH' });
            document.querySelectorAll('.notif-item').forEach(el => {
                _lidasLocalmente.add(parseInt(el.dataset.id));
                el.classList.remove('unread');
                el.dataset.lida = 'true';
            });
            document.getElementById('notifBadge').style.display    = 'none';
            document.getElementById('notifHeaderCount').textContent = '';
            window.__notifNaoLidas = 0;
            const dot = document.getElementById('sidebarNotifDot');
            if (dot) dot.style.display = 'none';
            const list = document.getElementById('notifList');
            delete list.dataset.hash;
        } catch (e) {
            console.warn('Erro ao marcar todas lidas', e);
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

        const list = document.getElementById('notifList');
        delete list.dataset.hash;

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
            _dados = json.data ?? [];
            _renderizar(_dados);
        } catch (e) {
            console.warn('Polling notificações falhou', e);
        }
    }

    function iniciar() {
        buscar();
        _timer = setInterval(buscar, POLL_INTERVAL);

        const btn     = document.getElementById('notifBtn');
        const panel   = document.getElementById('notifPanel');
        const overlay = document.getElementById('notifOverlay');
        const closeBtn= document.getElementById('notifPanelClose');

        btn.addEventListener('click', e => {
            e.stopPropagation();
            const abrindo = !panel.classList.contains('open');
            panel.classList.toggle('open');
            overlay.classList.toggle('open');
            if (abrindo) Chat.iniciarSeNecessario();
        });
        closeBtn.addEventListener('click', () => {
            panel.classList.remove('open');
            overlay.classList.remove('open');
        });
        overlay.addEventListener('click', () => {
            panel.classList.remove('open');
            overlay.classList.remove('open');
            const cs = document.getElementById('chatScreen');
            if (cs) cs.classList.remove('open');
            const cn = document.getElementById('chatNovoScreen');
            if (cn) cn.classList.remove('open');
        });
        document.querySelectorAll('.notif-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.notif-tab-content').forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(`tabContent-${tab.dataset.tab}`).classList.add('active');
                if (tab.dataset.tab === 'mensagens') {
                    Chat.iniciarSeNecessario();
                    Chat.carregarConversas();
                }
                if (tab.dataset.tab === 'solicitacoes') {
                    Chat.iniciarSeNecessario();
                    Solicitacoes.carregar();
                }
            });
        });
        document.getElementById('notifMarkAll').addEventListener('click', _marcarTodasLidas);
    }

    return { iniciar, buscar };
})();

const Chat = (() => {
    let _conversaAtual      = null;
    let _nomeAtual          = '';
    let _unidadeDestino     = null;
    let _fotoDestino        = null;
    let _pollTimer          = null;
    let _iniciado           = false;
    let _carregando         = false;

    function _iniciais(nome) {
        return nome.trim().split(' ').filter(Boolean)
            .slice(0,2).map(w => w[0].toUpperCase()).join('');
    }
    function _avatar(nome, fotoUrl) {
        if (fotoUrl) {
            return `<img src="${fotoUrl}" alt="${nome}" loading="lazy">`;
        }
        return _iniciais(nome);
    }
    function _tempo(str) {
        if (!str) return '';
        const diff = Math.floor((Date.now() - new Date(str)) / 1000);
        if (diff < 60)    return 'agora';
        if (diff < 3600)  return `${Math.floor(diff/60)}min`;
        if (diff < 86400) return `${Math.floor(diff/3600)}h`;
        return new Date(str).toLocaleDateString('pt-BR', {day:'2-digit', month:'2-digit'});
    }
    function _roleLabel(role) {
        return role === 'admin' ? 'Administrador' : 'Gestor';
    }

    async function carregarConversas() {
        const lista = document.getElementById('chatConversaLista');
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/conversas`);
            const json = await res.json();
            const convs = json.data ?? [];

            const badge = document.getElementById('tabBadgeChat');
            const total = convs.reduce((s, c) => s + (parseInt(c.nao_lidas) || 0), 0);
            if (total > 0) { badge.textContent = total; badge.style.display = 'inline-flex'; }
            else badge.style.display = 'none';

            if (!convs.length) {
                lista.innerHTML = '<div class="notif-empty">Nenhuma conversa ainda.</div>';
                return;
            }
            lista.innerHTML = convs.map(c => `
                <div class="chat-conversa-item" data-id="${c.id}" data-nome="${c.nome_exibido ?? ''}" data-unidade="${c.unidade_outro ?? ''}" data-foto="${c.foto_outro ?? ''}">
                    <div class="chat-conv-avatar">${_avatar(c.nome_exibido ?? '?', c.foto_outro)}</div>
                    <div class="chat-conv-info">
                        <div class="chat-conv-nome">${c.nome_exibido ?? '—'}</div>
                        <div class="chat-conv-preview">${c.ultima_mensagem ?? 'Sem mensagens ainda'}</div>
                    </div>
                    <div class="chat-conv-meta">
                        <span class="chat-conv-time">${_tempo(c.ultima_at)}</span>
                        ${parseInt(c.nao_lidas) > 0
                            ? `<span class="chat-conv-badge">${c.nao_lidas}</span>`
                            : ''}
                    </div>
                </div>
            `).join('');
            lista.querySelectorAll('.chat-conversa-item').forEach(el => {
                el.addEventListener('click', () => {
                    _unidadeDestino = el.dataset.unidade ? parseInt(el.dataset.unidade) : null;
                    _fotoDestino    = el.dataset.foto    || null;
                    abrirConversa(parseInt(el.dataset.id), el.dataset.nome);
                });
            });
        } catch(e) {
            lista.innerHTML = '<div class="notif-empty">Erro ao carregar.</div>';
        }
    }

    async function abrirConversa(conversaId, nome) {
        _conversaAtual = conversaId;
        _nomeAtual     = nome;
        document.getElementById('chatScreenNome').textContent = nome;
        const avatarEl = document.getElementById('chatScreenAvatar');
        avatarEl.innerHTML = _avatar(nome, _fotoDestino);
        document.getElementById('chatScreenSub').textContent  = '';
        document.getElementById('chatLista').style.display    = 'none';
        document.getElementById('chatNovoScreen').classList.remove('open');

        const msgsEl = document.getElementById('chatMsgs');
        msgsEl.innerHTML = '<div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
        document.getElementById('chatScreen').classList.add('open');

        if (_pollTimer) clearInterval(_pollTimer);
        _pollTimer = null;
        _carregando = false;
        await _carregarMensagens();
        _pollTimer = setInterval(_carregarMensagens, 5000);
    }

    async function _carregarMensagens() {
        if (!_conversaAtual || _carregando) return;
        _carregando = true;
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/conversas/${_conversaAtual}/mensagens`);
            const json = await res.json();
            const msgs = json.data ?? [];
            const myId = window.__userId;
            const el   = document.getElementById('chatMsgs');
            const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 60;

            el.innerHTML = msgs.map(m => {
                const minha   = parseInt(m.user_id) === myId;
                const sistema = m.mensagem.startsWith('🔔') || m.mensagem.startsWith('❌') || m.mensagem.startsWith('✅');
                if (sistema) {
                    return `
                    <div class="chat-msg sistema">
                        <div class="chat-msg-bubble">${m.mensagem.replace(/\n/g,'<br>')}</div>
                        <span class="chat-msg-time">${_tempo(m.created_at)}</span>
                    </div>`;
                }
                return `
                <div class="chat-msg ${minha ? 'minha' : 'deles'}">
                    <div class="chat-msg-bubble">${m.mensagem.replace(/\n/g,'<br>')}</div>
                    <span class="chat-msg-time">${_tempo(m.created_at)}</span>
                </div>`;
            }).join('');

            if (atBottom || msgs.length < 5) el.scrollTop = el.scrollHeight;
            _atualizarBadgeChat();
        } catch(e) {
            console.error('Erro ao carregar mensagens:', e);
        } finally {
            _carregando = false;
        }
    }

    async function _atualizarBadgeChat() {
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/count`);
            const json = await res.json();
            const total = json.nao_lidas ?? 0;
            const badge = document.getElementById('tabBadgeChat');
            if (total > 0) { badge.textContent = total; badge.style.display = 'inline-flex'; }
            else badge.style.display = 'none';
        } catch(e) {}
    }

    async function _enviar() {
        const input = document.getElementById('chatInput');
        const msg   = input.value.trim();
        if (!msg || !_conversaAtual) return;
        input.disabled = true;
        try {
            await fetch(`${BASE_URL}/api/chat/conversas/${_conversaAtual}/mensagens`, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ mensagem: msg }),
            });
            input.value = '';
            input.style.height = 'auto';
            await _carregarMensagens();
        } catch(e) {}
        finally { input.disabled = false; input.focus(); }
    }

    async function _carregarContatos() {
        const lista = document.getElementById('chatContatosLista');
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/contatos`);
            const json = await res.json();
            const contatos = json.data ?? [];
            if (!contatos.length) {
                lista.innerHTML = '<div class="notif-empty">Nenhum contato disponível.</div>';
                return;
            }
            lista.innerHTML = contatos.map(c => `
                <div class="chat-conversa-item" data-uid="${c.id}" data-nome="${c.nome}" data-foto="${c.foto_url ?? ''}">
                    <div class="chat-conv-avatar">${_avatar(c.nome, c.foto_url)}</div>
                    <div class="chat-conv-info">
                        <div class="chat-conv-nome">${c.nome}</div>
                        <div class="chat-conv-preview">${_roleLabel(c.role)}${c.unidade_sigla ? ' · ' + c.unidade_sigla : ''}</div>
                    </div>
                </div>
            `).join('');
            lista.querySelectorAll('.chat-conversa-item').forEach(el => {
                el.addEventListener('click', async () => {
                    const res  = await fetch(`${BASE_URL}/api/chat/conversas`, {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ user_id: parseInt(el.dataset.uid) }),
                    });
                    const json = await res.json();
                    lista.querySelectorAll('.chat-conversa-item').forEach(el => {
                        el.addEventListener('click', async () => {
                            const res  = await fetch(`${BASE_URL}/api/chat/conversas`, {
                                method: 'POST',
                                headers: {'Content-Type':'application/json'},
                                body: JSON.stringify({ user_id: parseInt(el.dataset.uid) }),
                            });
                            const json = await res.json();
                            if (json.conversa_id) {
                                _unidadeDestino = json.unidade_destino_id ?? null;
                                _fotoDestino    = el.dataset.foto || null;
                                abrirConversa(json.conversa_id, el.dataset.nome);
                            }
                        });
                    });
                });
            });
        } catch(e) {
            lista.innerHTML = '<div class="notif-empty">Erro ao carregar contatos.</div>';
        }
    }

    function _voltarLista() {
        if (_pollTimer) clearInterval(_pollTimer);
        _pollTimer     = null;
        _conversaAtual = null;
        _carregando    = false;
        document.getElementById('chatScreen').classList.remove('open');
        document.getElementById('chatNovoScreen').classList.remove('open');
        document.getElementById('chatLista').style.display = 'flex';
        carregarConversas();
    }

    function iniciarSeNecessario() {
        if (_iniciado) return;
        _iniciado = true;

        document.getElementById('chatBackBtn').addEventListener('click', _voltarLista);
        document.getElementById('chatNovoBackBtn').addEventListener('click', _voltarLista);
        document.getElementById('chatNovaBtn').addEventListener('click', () => {
            document.getElementById('chatLista').style.display    = 'none';
            document.getElementById('chatScreen').classList.remove('open');
            document.getElementById('chatNovoScreen').classList.add('open');
            _carregarContatos();
        });

        const btnTransf = document.getElementById('chatBtnSolicitarTransf');
        if (btnTransf) {
            btnTransf.addEventListener('click', () => Transferencia.abrir());
        }
        const btnAviso = document.getElementById('chatBtnEnviarAviso');
        if (btnAviso) {
            btnAviso.addEventListener('click', () => Aviso.abrir());
        }
        Transferencia.iniciar();
        Solicitacoes.iniciar();
        Aviso.iniciar();

        const input = document.getElementById('chatInput');
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); _enviar(); }
        });
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 100) + 'px';
        });
    }

    return { iniciarSeNecessario, carregarConversas, abrirConversa, getUnidadeDestino: () => _unidadeDestino };
})();

const Transferencia = (() => {
    async function abrir() {
        const overlay = document.getElementById('transfOverlay');
        const fb      = document.getElementById('transfFeedback');
        fb.className   = 'transf-feedback';
        fb.textContent = '';
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

    async function enviar() {
        const gestorEl         = document.getElementById('transfGestor');
        const gestorUserId     = parseInt(gestorEl.value);
        const servidorId       = parseInt(document.getElementById('transfServidor').value);
        const motivo           = document.getElementById('transfMotivo').value.trim();
        const fb               = document.getElementById('transfFeedback');
        const btn              = document.getElementById('transfEnviarBtn');

        if (!gestorUserId || !servidorId) {
            fb.className   = 'transf-feedback error';
            fb.textContent = 'Selecione o gestor de destino e o colaborador.';
            return;
        }

        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        fb.className  = 'transf-feedback';

        try {
            // Abre ou cria conversa com o gestor de destino
            const resConv = await fetch(`${BASE_URL}/api/chat/conversas`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ user_id: gestorUserId }),
            });
            const jsonConv = await resConv.json();
            if (!jsonConv.conversa_id) throw new Error('Não foi possível abrir conversa.');

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

            fb.className   = 'transf-feedback success';
            fb.textContent = 'Solicitação enviada! Aguardando aprovações.';
            setTimeout(() => fechar(), 1800);
        } catch(e) {
            fb.className   = 'transf-feedback error';
            fb.textContent = e.message;
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitação';
        }
    }

    function fechar() {
        document.getElementById('transfOverlay').classList.remove('open');
    }

    function iniciar() {
        document.getElementById('transfFecharBtn').addEventListener('click', fechar);
        document.getElementById('transfCancelarBtn').addEventListener('click', fechar);
        document.getElementById('transfEnviarBtn').addEventListener('click', enviar);
        document.getElementById('transfOverlay').addEventListener('click', e => {
            if (e.target === document.getElementById('transfOverlay')) fechar();
        });
    }

    return { abrir, iniciar };
})();

const Solicitacoes = (() => {
    let _ultimaCarga = 0;
    const role = '<?= $_SESSION['user']['role'] ?? 'user' ?>';
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
            botoes.push(`
                <button class="solic-btn solic-btn--aprovar"
                    onclick="Solicitacoes.aprovar(${s.id})">
                    <i class="fas fa-check"></i> Aprovar
                </button>`);
        }
        if (podeRejeitar) {
            botoes.push(`
                <button class="solic-btn solic-btn--rejeitar"
                    onclick="Solicitacoes.abrirRejeitar(${s.id})">
                    <i class="fas fa-times"></i> Rejeitar
                </button>`);
        }
        if (podeExecutar) {
            botoes.push(`
                <button class="solic-btn solic-btn--executar"
                    onclick="Solicitacoes.executar(${s.id})">
                    <i class="fas fa-exchange-alt"></i> Executar Transferência
                </button>`);
        }
        return botoes.join('');
    }

    async function carregar(forcar = false) {
        const agora = Date.now();
        if (!forcar && agora - _ultimaCarga < 15_000) return;
        _ultimaCarga = agora;

        const lista = document.getElementById('solicLista');
        lista.innerHTML = '<div class="notif-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/solicitacoes`);
            const json = await res.json();
            const sols = json.data ?? [];

            const pendentes = sols.filter(s =>
                s.status !== 'executado' && s.status !== 'rejeitado'
            ).length;
            const badge = document.getElementById('tabBadgeSolic');
            if (pendentes > 0) { badge.textContent = pendentes; badge.style.display = 'inline-flex'; }
            else badge.style.display = 'none';

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
                    <div class="solic-meta">
                        Solicitado por ${s.solicitante_nome} · ${_tempoRelativo(s.created_at)}
                    </div>
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
            alert(e.message);
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Aprovar'; }
        }
    }

    function abrirRejeitar(id) {
        _rejeitarId = id;
        document.getElementById('rejeitarMotivo').value = '';
        document.getElementById('rejeitarFeedback').className = 'transf-feedback';
        document.getElementById('rejeitarOverlay').classList.add('open');
    }

    async function _confirmarRejeitar() {
        if (!_rejeitarId) return;
        const motivo = document.getElementById('rejeitarMotivo').value.trim();
        const btn    = document.getElementById('rejeitarConfirmarBtn');
        const fb     = document.getElementById('rejeitarFeedback');
        btn.disabled = true;
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/solicitacoes/${_rejeitarId}/rejeitar`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ motivo }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Erro ao rejeitar.');
            await carregar(true);
        } catch(e) {
            fb.className   = 'transf-feedback error';
            fb.textContent = e.message;
        } finally {
            btn.disabled = false;
        }
    }

    async function executar(id) {
        if (!confirm('Confirma a execução da transferência? O colaborador será movido imediatamente.')) return;
        const btn = document.querySelector(`#solic-${id} .solic-btn--executar`);
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Executando...'; }
        try {
            const res  = await fetch(`${BASE_URL}/api/chat/solicitacoes/${id}/executar`, { method: 'POST' });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error ?? 'Erro ao executar.');
            await carregar(true);
        } catch(e) {
            alert(e.message);
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Executar Transferência'; }
        }
    }

    function iniciar() {
        document.getElementById('solicRefreshBtn').addEventListener('click', () => carregar(true));

        document.getElementById('rejeitarFecharBtn').addEventListener('click', () => {
            document.getElementById('rejeitarOverlay').classList.remove('open');
        });
        document.getElementById('rejeitarCancelarBtn').addEventListener('click', () => {
            document.getElementById('rejeitarOverlay').classList.remove('open');
        });
        document.getElementById('rejeitarConfirmarBtn').addEventListener('click', _confirmarRejeitar);
        document.getElementById('rejeitarOverlay').addEventListener('click', e => {
            if (e.target === document.getElementById('rejeitarOverlay')) {
                document.getElementById('rejeitarOverlay').classList.remove('open');
            }
        });
    }

    return { carregar, aprovar, abrirRejeitar, executar, iniciar };
})();

    const Aviso = (() => {
        function abrir() {
            document.getElementById('avisoTitulo').value   = '';
            document.getElementById('avisoMensagem').value = '';
            const fb = document.getElementById('avisoFeedback');
            fb.className   = 'transf-feedback';
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
                fb.className   = 'transf-feedback error';
                fb.textContent = 'Título e mensagem são obrigatórios.';
                return;
            }

            btn.disabled  = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            fb.className  = 'transf-feedback';

            try {
                const res  = await fetch(`${BASE_URL}/api/notificacoes/aviso`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ titulo, mensagem }),
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.error ?? 'Erro ao enviar.');
                fb.className   = 'transf-feedback success';
                fb.textContent = json.mensagem;
                setTimeout(() => fechar(), 1800);
            } catch(e) {
                fb.className   = 'transf-feedback error';
                fb.textContent = e.message;
            } finally {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Aviso';
            }
        }

        function iniciar() {
            document.getElementById('avisoFecharBtn').addEventListener('click', fechar);
            document.getElementById('avisoCancelarBtn').addEventListener('click', fechar);
            document.getElementById('avisoEnviarBtn').addEventListener('click', enviar);
            document.getElementById('avisoOverlay').addEventListener('click', e => {
                if (e.target === document.getElementById('avisoOverlay')) fechar();
            });
        }

        return { abrir, iniciar };
    })();

    const Chamado = (() => {
        let _chamadoAtual = null;

        function abrir() {
            if (_perfilCache) {
                document.getElementById('chamadoNome').value       = _perfilCache.nome  ?? '';
                document.getElementById('chamadoEmail').value      = _perfilCache.email ?? '';
                const nasc = _perfilCache.data_nascimento;
                document.getElementById('chamadoNascimento').value = nasc
                    ? (nasc.includes('/') ? dataBRparaISO(nasc) : nasc)
                    : '';
            }
            document.getElementById('chamadoMotivo').value = '';
            const fb = document.getElementById('chamadoFeedback');
            fb.className = 'transf-feedback'; fb.textContent = '';
            document.getElementById('chamadoOverlay').classList.add('open');
        }

        function fechar() {
            document.getElementById('chamadoOverlay').classList.remove('open');
        }

        async function enviar() {
            const nome           = document.getElementById('chamadoNome').value.trim();
            const email          = document.getElementById('chamadoEmail').value.trim();
            const dataNascimento = document.getElementById('chamadoNascimento').value;
            const motivo         = document.getElementById('chamadoMotivo').value.trim();
            const fb             = document.getElementById('chamadoFeedback');
            const btn            = document.getElementById('chamadoEnviarBtn');

            if (!nome && !email && !dataNascimento) {
                fb.className   = 'transf-feedback error';
                fb.textContent = 'Preencha ao menos um campo para alterar.';
                return;
            }

            btn.disabled  = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            fb.className  = 'transf-feedback';

            try {
                const res  = await fetch(`${BASE_URL}/api/chamados`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ nome, email, data_nascimento: dataNascimento, motivo }),
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.error ?? 'Erro ao enviar.');
                fb.className   = 'transf-feedback success';
                fb.textContent = 'Solicitação enviada! Aguardando aprovação.';
                setTimeout(() => fechar(), 1800);
            } catch(e) {
                fb.className   = 'transf-feedback error';
                fb.textContent = e.message;
            } finally {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitação';
            }
        }

        async function abrirAprovar(chamadoId) {
            _chamadoAtual = chamadoId;
            const fb = document.getElementById('chamadoAprovarFeedback');
            fb.className = 'transf-feedback'; fb.textContent = '';

            try {
                const res  = await fetch(`${BASE_URL}/api/chamados/${chamadoId}`);
                const json = await res.json();
                const c    = json.data;

                document.getElementById('chamadoAprovarInfo').innerHTML = `
                    <strong>${c.user_nome}</strong> solicitou alteração em
                    <span style="color:var(--primary)">${new Date(c.created_at).toLocaleDateString('pt-BR')}</span>
                    ${c.motivo ? `<br><em>"${c.motivo}"</em>` : ''}
                `;
                document.getElementById('chamadoAprovarNome').value       = c.nome_solicitado  ?? c.user_nome  ?? '';
                document.getElementById('chamadoAprovarEmail').value      = c.email_solicitado ?? c.user_email ?? '';
                document.getElementById('chamadoAprovarNascimento').value = c.data_nascimento_solicitada ?? '';
                document.getElementById('chamadoAprovarObs').value        = '';
                document.getElementById('chamadoAprovarOverlay').classList.add('open');
            } catch(e) {
                alert('Erro ao carregar chamado.');
            }
        }

        function fecharAprovar() {
            document.getElementById('chamadoAprovarOverlay').classList.remove('open');
            _chamadoAtual = null;
        }

        async function concluir() {
            if (!_chamadoAtual) return;
            const obs = document.getElementById('chamadoAprovarObs').value.trim();
            const btn = document.getElementById('chamadoConcluirBtn');
            const fb  = document.getElementById('chamadoAprovarFeedback');
            btn.disabled  = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const res  = await fetch(`${BASE_URL}/api/chamados/${_chamadoAtual}/concluir`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ observacao: obs }),
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.error ?? 'Erro ao concluir.');
                fecharAprovar();
            } catch(e) {
                fb.className   = 'transf-feedback error';
                fb.textContent = e.message;
            } finally {
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Confirmar Edição';
            }
        }

        async function rejeitar() {
            if (!_chamadoAtual) return;
            const motivo = prompt('Motivo da rejeição (opcional):') ?? '';
            const btn    = document.getElementById('chamadoRejeitarBtn');
            btn.disabled = true;

            try {
                const res = await fetch(`${BASE_URL}/api/chamados/${_chamadoAtual}/rejeitar`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ motivo }),
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.error ?? 'Erro ao rejeitar.');
                fecharAprovar();
            } catch(e) {
                alert(e.message);
            } finally {
                btn.disabled = false;
            }
        }

        function iniciar() {
            document.getElementById('btnAbrirChamado')
                .addEventListener('click', abrir);
            document.getElementById('chamadoFecharBtn')
                .addEventListener('click', fechar);
            document.getElementById('chamadoCancelarBtn')
                .addEventListener('click', fechar);
            document.getElementById('chamadoEnviarBtn')
                .addEventListener('click', enviar);
            document.getElementById('chamadoOverlay')
                .addEventListener('click', e => {
                    if (e.target === document.getElementById('chamadoOverlay')) fechar();
                });
            document.getElementById('chamadoAprovarFecharBtn')
                .addEventListener('click', fecharAprovar);
            document.getElementById('chamadoConcluirBtn')
                .addEventListener('click', concluir);
            document.getElementById('chamadoRejeitarBtn')
                .addEventListener('click', rejeitar);
            document.getElementById('chamadoAprovarOverlay')
                .addEventListener('click', e => {
                    if (e.target === document.getElementById('chamadoAprovarOverlay')) fecharAprovar();
                });
        }

        return { abrir, iniciar, abrirAprovar };
    })();

document.addEventListener('DOMContentLoaded', () => {
    carregarPerfil();
    Notificacoes.iniciar();
    Chamado.iniciar();
});

window.Solicitacoes  = Solicitacoes;
window.Transferencia = Transferencia;
window.Chat          = Chat;
</script>