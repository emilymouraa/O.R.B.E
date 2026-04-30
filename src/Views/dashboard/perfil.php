<?php
/*
 * Views/dashboard/perfil.php
 * Tela de perfil do usuário logado.
 * Dados carregados via GET /api/perfil.
 * Upload de foto via POST /api/perfil/foto (apenas admin).
 */
$pageTitle = 'Meu Perfil · ORBE';
$bodyClass = 'dashboard-page';
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>
<div class="main-content">

    <!-- ── Cabeçalho ── -->
    <header class="header-section">
        <div class="title-group">
            <h1></i>Meu Perfil</h1>
            <p>Consulte suas informações cadastrais e de acesso</p>
        </div>
        <button class="btn-new" id="btnEditarPerfil" style="display:none">
            <i class="fas fa-pencil-alt"></i> Editar Perfil
        </button>
    </header>

    <!-- ── Loading ── -->
    <div class="perfil-loading" id="perfilLoading">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Carregando informações...</span>
    </div>

    <!-- ── Erro ── -->
    <div class="perfil-feedback-error" id="perfilError" style="display:none">
        <i class="fas fa-circle-exclamation"></i>
        <p>Não foi possível carregar as informações do perfil.</p>
        <button class="btn-new" onclick="carregarPerfil()">
            <i class="fas fa-rotate-right"></i> Tentar novamente
        </button>
    </div>

    <!-- ── Conteúdo principal ── -->
    <div id="perfilContent" style="display:none">

        <!-- ══ ZONA 1 — Faixa de identidade (crachá) ══ -->
        <div class="perfil-identity-card">
            <!-- Avatar com upload -->
            <div class="perfil-avatar-wrap" id="avatarWrap">
                <div class="perfil-avatar" id="perfilAvatar"></div>
                <label class="perfil-avatar-upload" id="avatarUploadLabel" style="display:none" title="Alterar foto">
                    <i class="fas fa-camera"></i>
                    <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp" style="display:none">
                </label>
            </div>

            <!-- Informações de identidade -->
            <div class="perfil-identity-info">
                <p class="perfil-identity-ra" id="perfilRA">—</p>
                <p class="perfil-identity-name" id="perfilNome">—</p>
                <p class="perfil-identity-cargo" id="perfilCargo">—</p>
                <div class="perfil-identity-badges" id="perfilBadges"></div>
            </div>

            <!-- Tempo de serviço -->
            <div class="perfil-tempo-servico" id="perfilTempoServico" style="display:none">
                <span class="perfil-tempo-numero" id="tempoServicoAnos">—</span>
                <span class="perfil-tempo-label">anos na PRF</span>
            </div>
        </div>

        <!-- Preview de upload -->
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

        <!-- ══ ZONA 2 — Grid de 3 cards ══ -->
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

        <!-- ══ ZONA 3 — Segurança e Privacidade ══ -->
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

    </div><!-- /perfilContent -->

    <button class="btn-theme-fixed" onclick="toggleTheme()" id="btnTema" title="Alternar tema">🌙</button>

</div><!-- /main-content -->

<?php require __DIR__ . '/../layout/footer.php'; ?>

<script>
/* ═══════════════════════════════════════════════════════════════
   perfil.js — inline
   ═══════════════════════════════════════════════════════════════ */
let _cpfReal    = '';
let _cpfOculto  = '';
let _cpfVisivel = false;

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

async function carregarPerfil() {
    const loading = document.getElementById('perfilLoading');
    const content = document.getElementById('perfilContent');
    const error   = document.getElementById('perfilError');
    const btnEdit = document.getElementById('btnEditarPerfil');

    loading.style.display = 'flex';
    content.style.display = 'none';
    error.style.display   = 'none';

    try {
        const res = await fetch(`${BASE_URL}/api/perfil`);
        if (res.status === 401) { window.location.href = `${BASE_URL}/login`; return; }
        if (!res.ok) throw new Error('Erro na API');
        const json = await res.json();
        const u    = json.data;

        /* Avatar */
        const avatarEl = document.getElementById('perfilAvatar');
        if (u.foto_url) {
            avatarEl.innerHTML = `<img src="${u.foto_url}" alt="${u.nome}" class="perfil-avatar-img">`;
        } else {
            const initials = u.nome.trim().split(' ')
                .filter(Boolean).slice(0, 2)
                .map(w => w[0].toUpperCase()).join('');
            avatarEl.textContent = initials;
        }

        /* Zona 1: identidade */
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

        /* Zona 2: cards */
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
            btnEdit.style.display = 'flex';
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

document.getElementById('btnToggleCpf').addEventListener('click', () => {
    _cpfVisivel = !_cpfVisivel;
    document.getElementById('detCPF').textContent = _cpfVisivel ? _cpfReal : _cpfOculto;
    const icon = document.getElementById('iconCpf');
    icon.classList.toggle('fa-eye',       !_cpfVisivel);
    icon.classList.toggle('fa-eye-slash',  _cpfVisivel);
});

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
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Salvar foto';
    }
});

document.addEventListener('DOMContentLoaded', carregarPerfil);
</script>