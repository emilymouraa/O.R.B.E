<?php
/*
 * Views/dashboard/competencias.php
 * Admin/Gestor → vê todos os servidores da unidade
 * User         → vê só as suas, com botão de adicionar
 */
$pageTitle = 'Competências · ORBE';
$bodyClass = 'dashboard-page';
$roleAtual = $_SESSION['user']['role'] ?? 'user';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<div class="main-content">

    <!-- ── Cabeçalho ── -->
    <header class="header-section">
        <div class="title-group">
            <?php if (in_array($roleAtual, ['user', 'gestor'])): ?>
                <h1>Minhas Competências</h1>
                <p>Seu portfólio profissional de cursos, certificações e especializações</p>
            <?php else: ?>
                <h1>Gestão de Competências</h1>
                <p>Registro de cursos, certificações e especializações da equipe</p>
            <?php endif; ?>
        </div>
        <?php if (in_array($roleAtual, ['user', 'gestor'])): ?>
        <button class="btn-new" id="btnNovaCompetencia">
            <i class="fas fa-plus"></i> Nova Competência
        </button>
        <?php endif; ?>
    </header>

    <div id="banner-filtro-servidor" style="display:none;align-items:center;gap:.75rem;
        background:var(--surface);border:1px solid var(--border);border-radius:.75rem;
        padding:.85rem 1.25rem;margin-bottom:1.25rem;font-size:.88rem;color:var(--text)">
        <i class="fas fa-filter" style="color:var(--primary)"></i>
        <span>Exibindo competências de: <strong id="banner-nome-servidor"></strong></span>
        <a href="/competencias" style="margin-left:auto;display:flex;align-items:center;gap:.35rem;
        color:var(--primary);font-weight:600;text-decoration:none;font-size:.82rem">
            <i class="fas fa-xmark"></i> Limpar filtro
        </a>
    </div>

    <!-- ── Cards de resumo ── -->
    <section class="gc-cards">
       <div class="gc-card">
    <div class="gc-card-icon gc-card-icon--blue">
        <i class="fas fa-layer-group"></i>
    </div>
        <div class="gc-card-info"><span>Total</span><strong id="total">—</strong></div>
    </div>
    <div class="gc-card">
        <div class="gc-card-icon gc-card-icon--green">
            <i class="fas fa-book-open"></i>
        </div>
        <div class="gc-card-info"><span>Cursos</span><strong id="cursos">—</strong></div>
    </div>
    <div class="gc-card">
        <div class="gc-card-icon gc-card-icon--yellow">
            <i class="fas fa-medal"></i>
        </div>
        <div class="gc-card-info"><span>Certificações</span><strong id="certificacoes">—</strong></div>
    </div>
    <div class="gc-card">
        <div class="gc-card-icon gc-card-icon--purple">
            <i class="fas fa-star"></i>
        </div>
        <div class="gc-card-info"><span>Especializações</span><strong id="especializacoes">—</strong></div>
    </div>
    <div class="gc-card">
        <div class="gc-card-icon gc-card-icon--orange">
            <i class="fas fa-lightbulb"></i>
        </div>
        <div class="gc-card-info"><span>Habilidades</span><strong id="habilidades">—</strong></div>
    </div>
    </section>

    <!-- ── Filtros ── -->
    <section class="filters-container">
        <div class="filter-group">
            <input type="text" id="busca"
                placeholder="<?= $roleAtual === 'user' ? 'Buscar por competência...' : 'Buscar por competência ou servidor...' ?>"
                autocomplete="off">
        </div>
        <div class="filter-group">
            <select id="tipo">
                <option value="">Todos os tipos</option>
                <option value="curso">Curso</option>
                <option value="certificacao">Certificação</option>
                <option value="especializacao">Especialização</option>
                <option value="habilidade">Habilidade</option>
            </select>
        </div>
        <?php if (in_array($roleAtual, ['user', 'gestor'])): ?>
        <div class="filter-group">
            <select id="validade-filter">
                <option value="">Todas as validades</option>
                <option value="vencida">Vencidas</option>
                <option value="alerta">Vence em 90 dias</option>
                <option value="valida">Válidas</option>
                <option value="sem-validade">Sem validade</option>
            </select>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Lista ── -->
    <section class="gc-section">
        <div id="lista-competencias" class="gc-lista">
            <div class="table-feedback">
                <i class="fas fa-spinner fa-spin"></i> Carregando competências...
            </div>
        </div>
    </section>

</div>

<button class="btn-theme-fixed" onclick="toggleTheme()">🌙</button>

<!-- ═══════════════════════════════════════════════════════════
     MODAL — Nova Competência (só para user)
═══════════════════════════════════════════════════════════ -->
<?php if (in_array($roleAtual, ['user', 'gestor'])): ?>
<div id="modalCompetencia" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalCompTitulo" style="display:none">
    <div class="modal-content modal-content--large">

        <div class="modal-header">
            <h2 id="modalCompTitulo">
                <i class="fas fa-plus-circle" style="color:#3b82f6;margin-right:.4rem"></i>
                Nova Competência
            </h2>
            <button type="button" class="close-btn" id="btnFecharModal" aria-label="Fechar">&times;</button>
        </div>

        <!-- Stepper -->
        <div class="comp-stepper">
            <div class="comp-step active" data-step="1">
                <div class="comp-step-circle">1</div>
                <span>Identificação</span>
            </div>
            <div class="comp-step-line"></div>
            <div class="comp-step" data-step="2">
                <div class="comp-step-circle">2</div>
                <span>Detalhes</span>
            </div>
            <div class="comp-step-line"></div>
            <div class="comp-step" data-step="3">
                <div class="comp-step-circle">3</div>
                <span>Certificado</span>
            </div>
        </div>

        <form id="formCompetencia" novalidate>

            <!-- ── Step 1: Identificação ── -->
            <div class="comp-passo" id="passo-1">
                <div class="perfil-info-card">
                    <div class="perfil-info-card-header">
                        <span class="perfil-info-icon perfil-info-icon--blue">
                            <i class="fas fa-id-card"></i>
                        </span>
                        <h3>Identificação</h3>
                    </div>
                    <div class="perfil-fields">
                        <div class="perfil-field" style="grid-column:1/-1">
                            <label class="perfil-field-label" for="comp-nome">Nome da Competência *</label>
                            <input type="text" id="comp-nome" class="perfil-field-input"
                                placeholder="Ex: Direção Defensiva Avançada" required>
                        </div>
                        <div class="perfil-field">
                            <label class="perfil-field-label" for="comp-tipo">Tipo *</label>
                            <select id="comp-tipo" class="perfil-field-input" required>
                                <option value="">Selecione...</option>
                                <option value="curso">🎓 Curso</option>
                                <option value="certificacao">🏅 Certificação</option>
                                <option value="especializacao">⭐ Especialização</option>
                                <option value="habilidade">💡 Habilidade</option>
                            </select>
                        </div>
                        <div class="perfil-field">
                            <label class="perfil-field-label" for="comp-visibilidade">Visibilidade</label>
                            <select id="comp-visibilidade" class="perfil-field-input">
                                <option value="privada">🔒 Privada (só eu vejo)</option>
                                <option value="publica">🌐 Pública (colegas podem ver)</option>
                            </select>
                        </div>
                        <div class="perfil-field">
                            <label class="perfil-field-label" for="comp-instituicao">Instituição</label>
                            <input type="text" id="comp-instituicao" class="perfil-field-input"
                                placeholder="Ex: SENASP, CNPQ, Coursera...">
                        </div>
                        <div class="perfil-field">
                            <label class="perfil-field-label" for="comp-carga">Carga Horária (h)</label>
                            <input type="number" id="comp-carga" class="perfil-field-input"
                                min="1" max="9999" placeholder="Ex: 40">
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <span></span>
                    <button type="button" class="btn-save" id="btnPasso1">
                        Próximo <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ── Step 2: Detalhes ── -->
            <div class="comp-passo" id="passo-2" style="display:none">
                <div class="perfil-info-card">
                    <div class="perfil-info-card-header">
                        <span class="perfil-info-icon perfil-info-icon--green">
                            <i class="fas fa-calendar-check"></i>
                        </span>
                        <h3>Datas e Descrição</h3>
                    </div>
                    <div class="perfil-fields">
                        <div class="perfil-field">
                            <label class="perfil-field-label" for="comp-conclusao">Data de Conclusão</label>
                            <input type="date" id="comp-conclusao" class="perfil-field-input">
                        </div>
                        <div class="perfil-field">
                            <label class="perfil-field-label" for="comp-validade">
                                Validade
                                <span style="font-size:.75rem;color:#94a3b8">(deixe em branco se não expira)</span>
                            </label>
                            <input type="date" id="comp-validade" class="perfil-field-input">
                        </div>
                        <div class="perfil-field" style="grid-column:1/-1">
                            <label class="perfil-field-label" for="comp-descricao">
                                Descrição / Experiência
                                <span style="font-size:.75rem;color:#94a3b8">(o que aprendeu, como aplica no trabalho...)</span>
                            </label>
                            <textarea id="comp-descricao" class="perfil-field-input"
                                rows="4"
                                placeholder="Descreva o que foi abordado, suas aprendizagens e como isso contribui para seu trabalho na PRF..."
                                style="resize:vertical;font-family:inherit"></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" id="btnVoltarPasso2">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="button" class="btn-save" id="btnPasso2">
                        Próximo <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ── Step 3: Certificado ── -->
            <div class="comp-passo" id="passo-3" style="display:none">
                <div class="perfil-info-card">
                    <div class="perfil-info-card-header">
                        <span class="perfil-info-icon perfil-info-icon--yellow">
                            <i class="fas fa-file-certificate"></i>
                        </span>
                        <h3>Certificado / Anexo</h3>
                    </div>
                    <div style="padding:1.5rem">

                        <!-- Resumo do que foi preenchido -->
                        <div class="comp-resumo" id="comp-resumo"></div>

                        <!-- Drop zone -->
                        <div class="comp-dropzone" id="compDropzone">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Arraste o certificado aqui ou <strong>clique para selecionar</strong></p>
                            <span>PDF, JPG ou PNG — máximo 5 MB</span>
                            <input type="file" id="comp-arquivo" accept=".pdf,.jpg,.jpeg,.png" style="display:none">
                        </div>
                        <div class="comp-file-preview" id="compFilePreview" style="display:none">
                            <i class="fas fa-file-alt"></i>
                            <span id="compFileName"></span>
                            <button type="button" id="btnRemoverArquivo" title="Remover">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <p style="font-size:.8rem;color:#94a3b8;margin-top:1rem;text-align:center">
                            O anexo é opcional. Você pode adicionar depois clicando no card da competência.
                        </p>
                    </div>
                </div>

                <div id="feedbackComp" class="feedback-msg" style="display:none"></div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" id="btnVoltarPasso3">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="submit" class="btn-save" id="btnSalvarComp">
                        <i class="fas fa-save"></i> Salvar Competência
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     ESTILOS ESPECÍFICOS desta view
═══════════════════════════════════════════════════════════ -->
<style>
/* ── Cards de resumo ── */
.gc-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem}
.gc-card{display:flex;align-items:center;gap:.85rem;background:var(--card);border:1px solid var(--border);border-radius:.75rem;padding:1rem 1.1rem}
.gc-card-icon{width:2.5rem;height:2.5rem;border-radius:.6rem;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.gc-card-icon--blue  {background:rgba(59,130,246,.15);color:#3b82f6}
.gc-card-icon--green {background:rgba(34,197,94,.15);color:#22c55e}
.gc-card-icon--yellow{background:rgba(234,179,8,.15);color:#eab308}
.gc-card-icon--purple{background:rgba(168,85,247,.15);color:#a855f7}
.gc-card-icon--orange{background:rgba(249,115,22,.15);color:#f97316}
.gc-card-info{display:flex;flex-direction:column;gap:.1rem}
.gc-card-info span{font-size:.78rem;color:var(--muted)}
.gc-card-info strong{font-size:1.4rem;color:var(--text);line-height:1}

/* ── Lista de competências ── */
.gc-lista{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1.25rem}

/* ── Item de competência ── */
.gc-item{background:var(--card);border:1px solid var(--border);border-radius:.85rem;overflow:hidden;transition:box-shadow .2s,transform .2s;display:flex;flex-direction:column}
.gc-item:hover{box-shadow:0 4px 20px rgba(0,0,0,.08);transform:translateY(-2px)}
.gc-item-top{padding:1.1rem 1.25rem .75rem;display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem}
.gc-item-titulo{font-weight:700;font-size:.95rem;color:var(--text);line-height:1.3;flex:1}
.gc-item-badges{display:flex;flex-direction:column;gap:.35rem;align-items:flex-end;flex-shrink:0}
.gc-item-meta{padding:0 1.25rem .75rem;display:flex;flex-wrap:wrap;gap:.5rem .9rem;font-size:.78rem;color:var(--muted)}
.gc-item-meta span{display:flex;align-items:center;gap:.3rem}
.gc-item-desc{padding:0 1.25rem .75rem;font-size:.82rem;color:var(--muted);line-height:1.5;border-top:1px solid var(--border);padding-top:.75rem;display:none}
.gc-item-desc.aberta{display:block}
.gc-item-footer{padding:.6rem 1.25rem;background:var(--surface);border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-top:auto}
.gc-item-footer-btns{display:flex;gap:.5rem}

/* ── Botões internos do card ── */
.gc-btn{background:none;border:1px solid var(--border);border-radius:.4rem;padding:.28rem .65rem;font-size:.75rem;cursor:pointer;color:var(--muted);display:flex;align-items:center;gap:.3rem;transition:background .15s,color .15s}
.gc-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.gc-btn.danger:hover{background:var(--danger);border-color:var(--danger)}

/* ── Badges ── */
.badge-tipo{display:inline-flex;align-items:center;gap:.25rem;padding:.2rem .6rem;border-radius:999px;font-size:.72rem;font-weight:600;white-space:nowrap}
.badge-tipo.curso{background:rgba(59,130,246,.15);color:#3b82f6}
.badge-tipo.certificacao{background:rgba(234,179,8,.15);color:#ca8a04}
.badge-tipo.especializacao{background:rgba(168,85,247,.15);color:#a855f7}
.badge-tipo.habilidade{background:rgba(249,115,22,.15);color:#ea580c}
.badge-validade{display:inline-flex;align-items:center;gap:.25rem;padding:.18rem .55rem;border-radius:999px;font-size:.7rem;font-weight:600;white-space:nowrap}
.badge-validade.valida{background:rgba(34,197,94,.15);color:#16a34a}
.badge-validade.alerta{background:rgba(234,179,8,.15);color:#ca8a04}
.badge-validade.vencida{background:rgba(239,68,68,.15);color:#dc2626}
.badge-validade.sem-validade{background:rgba(100,116,139,.15);color:var(--muted)}
.badge-vis{display:inline-flex;align-items:center;gap:.2rem;padding:.15rem .45rem;border-radius:999px;font-size:.68rem;font-weight:500}
.badge-vis.publica{background:rgba(34,197,94,.15);color:#16a34a}
.badge-vis.privada{background:rgba(100,116,139,.15);color:var(--muted)}

/* ── Stepper ── */
.comp-stepper{display:flex;align-items:center;justify-content:center;gap:0;margin:1.25rem 0 1.75rem;padding:0 1rem}
.comp-step{display:flex;flex-direction:column;align-items:center;gap:.3rem;cursor:default}
.comp-step-circle{width:2rem;height:2rem;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:var(--muted);background:var(--card);transition:all .3s}
.comp-step span{font-size:.72rem;color:var(--muted);white-space:nowrap}
.comp-step.active .comp-step-circle{background:var(--primary);border-color:var(--primary);color:#fff}
.comp-step.active span{color:var(--primary);font-weight:600}
.comp-step.concluido .comp-step-circle{background:#22c55e;border-color:#22c55e;color:#fff}
.comp-step-line{flex:1;height:2px;background:var(--border);max-width:80px;margin:0 .25rem;margin-bottom:1.2rem}

/* ── Drop zone ── */
.comp-dropzone{border:2px dashed var(--border);border-radius:.75rem;padding:2.5rem 1rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;color:var(--muted)}
.comp-dropzone:hover,.comp-dropzone.drag{border-color:var(--primary);background:var(--surface)}
.comp-dropzone i{font-size:2.5rem;color:var(--primary);margin-bottom:.75rem;display:block}
.comp-dropzone p{font-size:.9rem;margin:.25rem 0}
.comp-dropzone span{font-size:.75rem}
.comp-file-preview{display:flex;align-items:center;gap:.75rem;background:var(--surface);border:1px solid var(--border);border-radius:.6rem;padding:.75rem 1rem;margin-top:.75rem}
.comp-file-preview i{color:var(--primary);font-size:1.4rem}
.comp-file-preview span{flex:1;font-size:.85rem;color:var(--text);word-break:break-all}
.comp-file-preview button{background:none;border:none;color:var(--danger);cursor:pointer;font-size:1rem;padding:.2rem}

/* ── Resumo no step 3 ── */
.comp-resumo{background:var(--surface);border:1px solid var(--border);border-radius:.6rem;padding:.9rem 1rem;margin-bottom:1.25rem;font-size:.83rem;display:flex;flex-wrap:wrap;gap:.4rem .9rem;color:var(--muted)}
.comp-resumo strong{color:var(--text)}

/* ── Ícones por tipo ── */
.gc-tipo-icon{width:2.2rem;height:2.2rem;border-radius:.5rem;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
</style>

<?php require __DIR__ . '/../layout/footer.php'; ?>
<script>
(function () {

const ROLE = '<?= $roleAtual ?>';

// ── Referências ──────────────────────────────────────────────
const listEl    = document.getElementById('lista-competencias');
const buscaEl   = document.getElementById('busca');
const tipoEl    = document.getElementById('tipo');
const validadeEl= document.getElementById('validade-filter');

// ── Estado ───────────────────────────────────────────────────
let dadosOriginais = [];

// ════════════════════════════════════════════════════════════
// CARREGAMENTO
// ════════════════════════════════════════════════════════════
async function carregar() {
    listEl.innerHTML = '<div class="table-feedback"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
    try {
        const url  = servidorIdFiltro
            ? `/api/competencias?servidor_id=${servidorIdFiltro}`
            : '/api/competencias';
        const res  = await fetch(url);
        const json = await res.json();  
        dadosOriginais = json.data ?? [];
        atualizarCards(dadosOriginais);
        renderizar(dadosOriginais);
    } catch {
        listEl.innerHTML = '<div class="table-feedback"><i class="fas fa-circle-exclamation"></i> Erro ao carregar dados.</div>';
    }
}

// ════════════════════════════════════════════════════════════
// CARDS DE RESUMO
// ════════════════════════════════════════════════════════════
function atualizarCards(data) {
    document.getElementById('total').textContent         = data.length;
    document.getElementById('cursos').textContent        = data.filter(c => c.tipo === 'curso').length;
    document.getElementById('certificacoes').textContent = data.filter(c => c.tipo === 'certificacao').length;
    document.getElementById('especializacoes').textContent = data.filter(c => c.tipo === 'especializacao').length;
    document.getElementById('habilidades').textContent   = data.filter(c => c.tipo === 'habilidade').length;
}

// ════════════════════════════════════════════════════════════
// RENDERIZAÇÃO DOS CARDS
// ════════════════════════════════════════════════════════════
function renderizar(data) {
    if (!data.length) {
        listEl.innerHTML = `
            <div class="table-feedback" style="grid-column:1/-1">
                <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:.5rem;display:block"></i>
                ${ROLE === 'user' ? 'Nenhuma competência cadastrada. Clique em <strong>Nova Competência</strong> para começar!' : 'Nenhuma competência encontrada.'}
            </div>`;
        return;
    }

    listEl.innerHTML = data.map((c, idx) => {
        const tipoConf  = tipoConfig(c.tipo);
        const valBadge  = validadeBadge(c.validade);
        const temDesc   = c.descricao && c.descricao.trim();
        const temAnexo  = c.anexo_url;

        return `
        <div class="gc-item" data-idx="${idx}">
            <div class="gc-item-top">
                <div style="display:flex;gap:.75rem;align-items:flex-start;flex:1;min-width:0">
                    <div class="gc-tipo-icon" style="background:${tipoConf.bg}">
                        <i class="${tipoConf.icon}" style="color:${tipoConf.cor}"></i>
                    </div>
                    <span class="gc-item-titulo">${escHtml(c.nome)}</span>
                </div>
                <div class="gc-item-badges">
                    <span class="badge-tipo ${c.tipo}">${tipoConf.label}</span>
                    ${ROLE === 'user' ? `<span class="badge-vis ${c.visibilidade}">${c.visibilidade === 'publica' ? '🌐 Pública' : '🔒 Privada'}</span>` : ''}
                </div>
            </div>

            <div class="gc-item-meta">
                ${c.instituicao ? `<span><i class="fas fa-building"></i>${escHtml(c.instituicao)}</span>` : ''}
                ${c.data_conclusao ? `<span><i class="fas fa-calendar-check"></i>${formatarData(c.data_conclusao)}</span>` : ''}
                ${c.carga_horaria  ? `<span><i class="fas fa-clock"></i>${c.carga_horaria}h</span>` : ''}
                ${ROLE !== 'user' && c.servidor ? `<span><i class="fas fa-user"></i>${escHtml(c.servidor)}</span>` : ''}
            </div>

            <div class="gc-item-badges" style="padding:0 1.25rem .75rem;flex-direction:row;gap:.4rem">
                <span class="badge-validade ${valBadge.cls}">
                    <i class="${valBadge.icon}"></i>${valBadge.label}
                </span>
                ${temAnexo ? `<span class="badge-tipo certificacao" style="cursor:pointer" onclick="abrirAnexo('${escHtml(c.anexo_url)}')"><i class="fas fa-paperclip"></i> Certificado</span>` : ''}
            </div>

            ${temDesc ? `
            <div class="gc-item-desc" id="desc-${idx}">
                ${escHtml(c.descricao)}
            </div>` : ''}

            <div class="gc-item-footer">
                <span style="font-size:.72rem;color:var(--text-secondary,#64748b)">
                    <i class="fas fa-calendar-plus"></i>
                    ${formatarData(c.created_at ?? c.data_conclusao)}
                </span>
                <div class="gc-item-footer-btns">
                    ${temDesc ? `
                    <button class="gc-btn" onclick="toggleDesc(${idx})">
                        <i class="fas fa-align-left"></i> Descrição
                    </button>` : ''}
                    ${ROLE === 'user' && !temAnexo ? `
                    <button class="gc-btn" onclick="abrirUpload(${c.id})">
                        <i class="fas fa-paperclip"></i> Anexar
                    </button>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');
}

// ════════════════════════════════════════════════════════════
// FILTROS
// ════════════════════════════════════════════════════════════
function filtrar() {
    const busca   = buscaEl.value.toLowerCase();
    const tipo    = tipoEl.value;
    const valFil  = validadeEl?.value ?? '';

    const filtrado = dadosOriginais.filter(c => {
        const matchBusca = c.nome.toLowerCase().includes(busca) ||
            (c.servidor ?? '').toLowerCase().includes(busca) ||
            (c.instituicao ?? '').toLowerCase().includes(busca);
        const matchTipo  = tipo ? c.tipo === tipo : true;

        let matchVal = true;
        if (valFil) {
            const hoje  = new Date(); hoje.setHours(0,0,0,0);
            const nov90 = new Date(hoje); nov90.setDate(hoje.getDate() + 90);
            if (!c.validade) {
                matchVal = valFil === 'sem-validade';
            } else {
                const val = new Date(c.validade);
                if (valFil === 'vencida')     matchVal = val < hoje;
                else if (valFil === 'alerta') matchVal = val >= hoje && val <= nov90;
                else if (valFil === 'valida') matchVal = val > nov90;
                else matchVal = true;
            }
        }
        return matchBusca && matchTipo && matchVal;
    });

    atualizarCards(filtrado);
    renderizar(filtrado);
}

const estComp = StateManager.load('/competencias');
if (estComp) {
    if (estComp.busca)    buscaEl.value   = estComp.busca;
    if (estComp.tipo)     tipoEl.value    = estComp.tipo;
    if (estComp.validade && validadeEl) validadeEl.value = estComp.validade;
}

const params      = new URLSearchParams(window.location.search);
const servidorIdFiltro = params.get('servidor_id');

if (servidorIdFiltro) {
    const banner = document.getElementById('banner-filtro-servidor');
    banner.style.display = 'flex';
    carregar().then(() => {
        const nome = dadosOriginais[0]?.servidor ?? 'Servidor';
        document.getElementById('banner-nome-servidor').textContent = nome;
    });
}

function salvarEstadoComp() {
    StateManager.save('/competencias', {
        busca:    buscaEl.value,
        tipo:     tipoEl.value,
        validade: validadeEl?.value ?? '',
    });
}

buscaEl.addEventListener('input', () => { salvarEstadoComp(); filtrar(); });
tipoEl.addEventListener('change', () => { salvarEstadoComp(); filtrar(); });
validadeEl?.addEventListener('change', () => { salvarEstadoComp(); filtrar(); });

function tipoConfig(tipo) {
    const map = {
        curso:         { icon:'fas fa-book-open', cor:'#3b82f6', bg:'#eff6ff', label:'Curso' },
        certificacao:  { icon:'fas fa-medal',     cor:'#ca8a04', bg:'#fefce8', label:'Certificação' },
        especializacao:{ icon:'fas fa-star',      cor:'#a855f7', bg:'#fdf4ff', label:'Especialização' },
        habilidade:    { icon:'fas fa-lightbulb', cor:'#ea580c', bg:'#fff7ed', label:'Habilidade' },
    };
    return map[tipo] ?? { icon:'fas fa-circle', cor:'#64748b', bg:'#f1f5f9', label: tipo };
}

function validadeBadge(validade) {
    if (!validade) return { cls:'sem-validade', icon:'fas fa-infinity',           label:'Sem validade' };
    const hoje  = new Date(); hoje.setHours(0,0,0,0);
    const nov90 = new Date(hoje); nov90.setDate(hoje.getDate() + 90);
    const val   = new Date(validade);
    if (val < hoje)       return { cls:'vencida',     icon:'fas fa-circle-exclamation', label:'Vencida em '    + formatarData(validade) };
    if (val <= nov90)     return { cls:'alerta',      icon:'fas fa-triangle-exclamation',label:'Vence em '     + formatarData(validade) };
    return                       { cls:'valida',      icon:'fas fa-circle-check',        label:'Válida até '   + formatarData(validade) };
}

function formatarData(data) {
    if (!data) return '—';
    const d = new Date(data);
    if (isNaN(d)) return data;
    return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

window.toggleDesc = function(idx) {
    document.getElementById('desc-' + idx)?.classList.toggle('aberta');
};

window.abrirAnexo = function(url) {
    window.open(url, '_blank');
};

carregar().then(() => {
    if (servidorIdFiltro) return;
    const estComp = StateManager.load('/competencias');
    if (estComp?.busca || estComp?.tipo || estComp?.validade) {
        filtrar();
    }
});

if (!['user', 'gestor'].includes(ROLE)) { 
    return; 
}

const modal       = document.getElementById('modalCompetencia');
const form        = document.getElementById('formCompetencia');
const feedbackEl  = document.getElementById('feedbackComp');
let   novaCompId  = null;

document.getElementById('btnNovaCompetencia').addEventListener('click', () => {
    irPasso(1);
    form.reset();
    resetArquivo();
    novaCompId = null;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
});
document.getElementById('btnFecharModal').addEventListener('click', fecharModal);
modal.addEventListener('click', e => { if (e.target === modal) fecharModal(); });

function fecharModal() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
    if (novaCompId) carregar(); // recarrega se salvou
}

// ── Stepper ──────────────────────────────────────────────────
function irPasso(n) {
    document.querySelectorAll('.comp-passo').forEach(p => p.style.display = 'none');
    document.getElementById('passo-' + n).style.display = 'block';
    document.querySelectorAll('.comp-step').forEach(s => {
        const sn = parseInt(s.dataset.step);
        s.classList.toggle('active',    sn === n);
        s.classList.toggle('concluido', sn < n);
    });
}

document.getElementById('btnPasso1').addEventListener('click', () => {
    const nome = document.getElementById('comp-nome').value.trim();
    const tipo = document.getElementById('comp-tipo').value;
    if (!nome || !tipo) { alert('Preencha o nome e o tipo da competência.'); return; }
    irPasso(2);
});

document.getElementById('btnVoltarPasso2').addEventListener('click', () => irPasso(1));
document.getElementById('btnPasso2').addEventListener('click', () => {
    atualizarResumo();
    irPasso(3);
});
document.getElementById('btnVoltarPasso3').addEventListener('click', () => irPasso(2));

// ── Resumo no step 3 ─────────────────────────────────────────
function atualizarResumo() {
    const nome   = document.getElementById('comp-nome').value;
    const tipo   = document.getElementById('comp-tipo').value;
    const inst   = document.getElementById('comp-instituicao').value;
    const carga  = document.getElementById('comp-carga').value;
    const conc   = document.getElementById('comp-conclusao').value;
    const valid  = document.getElementById('comp-validade').value;

    const tipoLabel = { curso:'Curso', certificacao:'Certificação', especializacao:'Especialização', habilidade:'Habilidade' };
    document.getElementById('comp-resumo').innerHTML = `
        <span><strong>Nome:</strong> ${escHtml(nome)}</span>
        <span><strong>Tipo:</strong> ${tipoLabel[tipo] ?? tipo}</span>
        ${inst   ? `<span><strong>Instituição:</strong> ${escHtml(inst)}</span>` : ''}
        ${carga  ? `<span><strong>Carga:</strong> ${carga}h</span>` : ''}
        ${conc   ? `<span><strong>Conclusão:</strong> ${formatarData(conc)}</span>` : ''}
        ${valid  ? `<span><strong>Validade:</strong> ${formatarData(valid)}</span>` : ''}
    `;
}

// ── Drop zone ────────────────────────────────────────────────
const dropzone   = document.getElementById('compDropzone');
const fileInput  = document.getElementById('comp-arquivo');
const filePreview= document.getElementById('compFilePreview');
const fileNameEl = document.getElementById('compFileName');

dropzone.addEventListener('click',      () => fileInput.click());
dropzone.addEventListener('dragover',   e => { e.preventDefault(); dropzone.classList.add('drag'); });
dropzone.addEventListener('dragleave',  () => dropzone.classList.remove('drag'));
dropzone.addEventListener('drop', e => {
    e.preventDefault(); dropzone.classList.remove('drag');
    if (e.dataTransfer.files[0]) setArquivo(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) setArquivo(fileInput.files[0]);
});
document.getElementById('btnRemoverArquivo').addEventListener('click', resetArquivo);

function setArquivo(file) {
    fileNameEl.textContent = file.name;
    dropzone.style.display  = 'none';
    filePreview.style.display = 'flex';
}
function resetArquivo() {
    fileInput.value = '';
    filePreview.style.display = 'none';
    dropzone.style.display    = 'block';
}

// ── Submit ───────────────────────────────────────────────────
form.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('btnSalvarComp');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    feedbackEl.style.display = 'none';

    const payload = {
        nome:          document.getElementById('comp-nome').value.trim(),
        tipo:          document.getElementById('comp-tipo').value,
        visibilidade:  document.getElementById('comp-visibilidade').value,
        instituicao:   document.getElementById('comp-instituicao').value.trim(),
        carga_horaria: document.getElementById('comp-carga').value || null,
        data_conclusao:document.getElementById('comp-conclusao').value || null,
        validade:      document.getElementById('comp-validade').value || null,
        descricao:     document.getElementById('comp-descricao').value.trim(),
    };

    try {
        // 1. Salva competência
        const res  = await fetch('/api/competencias', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();

        if (!res.ok) {
            mostrarFeedback(json.error ?? 'Erro ao salvar.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Salvar Competência';
            return;
        }

        novaCompId = json.id;

        // 2. Upload do anexo se houver arquivo
        if (fileInput.files[0]) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando certificado...';
            const fd = new FormData();
            fd.append('anexo', fileInput.files[0]);
            await fetch(`/api/competencias/${novaCompId}/anexo`, { method:'POST', body: fd });
        }

        mostrarFeedback('Competência salva com sucesso!', 'success');
        btn.innerHTML = '<i class="fas fa-check"></i> Salvo!';

        setTimeout(fecharModal, 1200);

    } catch {
        mostrarFeedback('Erro de conexão. Tente novamente.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar Competência';
    }
});

function mostrarFeedback(msg, tipo) {
    feedbackEl.textContent    = msg;
    feedbackEl.className      = `feedback-msg ${tipo === 'error' ? 'feedback-error' : 'feedback-success'}`;
    feedbackEl.style.display  = 'block';
}

// ── Upload avulso (botão "Anexar" no card) ───────────────────
window.abrirUpload = function(compId) {
    const inp = document.createElement('input');
    inp.type   = 'file';
    inp.accept = '.pdf,.jpg,.jpeg,.png';
    inp.onchange = async () => {
        if (!inp.files[0]) return;
        const fd = new FormData();
        fd.append('anexo', inp.files[0]);
        const res = await fetch(`/api/competencias/${compId}/anexo`, { method:'POST', body:fd });
        if (res.ok) { carregar(); }
        else { alert('Erro ao enviar o arquivo.'); }
    };
    inp.click();
};

})();
</script>