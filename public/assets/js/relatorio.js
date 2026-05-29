/* ═══════════════════════════════════════════════════════════════
   Módulo de Exportação de Relatórios.
   Responsável por: modal de filtros, preview de contagem e
   disparo do download PDF via RelatorioController.
═══════════════════════════════════════════════════════════════ */
function abrirModalRelatorio() {
    document.getElementById('modal-relatorio').classList.add('open');
    document.body.style.overflow = 'hidden';
    carregarUnidadesRelatorio();
}

function fecharModalRelatorio() {
    document.getElementById('modal-relatorio').classList.remove('open');
    document.body.style.overflow = '';
    limparPreview();
}

document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('modal-relatorio');
    if (!overlay) return;

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) fecharModalRelatorio();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fecharModalRelatorio();
    });
});

async function carregarUnidadesRelatorio() {
    const select = document.getElementById('rel-unidades');
    if (!select || select.dataset.carregado === '1') return;

    try {
        const res  = await fetch(`${BASE_URL}/api/unidades`);
        const json = await res.json();
        const lista = json.data ?? json ?? [];

        lista.forEach(u => {
            const opt    = document.createElement('option');
            opt.value    = u.id;
            opt.textContent = `${u.sigla ?? ''}${u.sigla ? ' — ' : ''}${u.nome}`;
            select.appendChild(opt);
        });

        select.dataset.carregado = '1';
    } catch (err) {
        console.error('Erro ao carregar unidades:', err);
    }
}

function coletarFiltros() {
    const g = id => document.getElementById(id);

    const unidadesEl = g('rel-unidades');
    const unidades   = unidadesEl
        ? Array.from(unidadesEl.selectedOptions).map(o => o.value).filter(Boolean)
        : [];

    const situacao = Array.from(
        document.querySelectorAll('input[name="rel-situacao"]:checked')
    ).map(c => c.value);

    return {
        unidades:          unidades.length   ? unidades   : undefined,
        cargo:             g('rel-cargo')?.value.trim()   || undefined,
        role:              g('rel-role')?.value            || undefined,
        situacao:          situacao.length   ? situacao   : undefined,
        idade_min:         g('rel-idade-min')?.value       || undefined,
        idade_max:         g('rel-idade-max')?.value       || undefined,
        servico_min:       g('rel-servico-min')?.value     || undefined,
        servico_max:       g('rel-servico-max')?.value     || undefined,
        admissao_de:       g('rel-admissao-de')?.value     || undefined,
        admissao_ate:      g('rel-admissao-ate')?.value    || undefined,
        competencia_nome:  g('rel-comp-nome')?.value.trim() || undefined,
        competencia_tipo:  g('rel-comp-tipo')?.value        || undefined,
    };
}

function coletarColunas() {
    return Array.from(
        document.querySelectorAll('input[name="rel-coluna"]:checked')
    ).map(c => c.value);
}

function coletarOrdenacao() {
    return document.getElementById('rel-ordenar')?.value ?? 'nome';
}

let _previewTimer = null;

async function atualizarPreview() {
    clearTimeout(_previewTimer);
    _previewTimer = setTimeout(async () => {
        const badge = document.getElementById('rel-preview-badge');
        if (!badge) return;

        badge.textContent = '…';
        badge.className   = 'rel-preview-badge loading';

        try {
            const res  = await fetch(`${BASE_URL}/api/relatorio/preview`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ filtros: coletarFiltros() }),
            });
            const json = await res.json();
            const total = json.total ?? 0;

            badge.textContent = `${total} colaborador${total !== 1 ? 'es' : ''} encontrado${total !== 1 ? 's' : ''}`;
            badge.className   = total > 0 ? 'rel-preview-badge has-results' : 'rel-preview-badge no-results';
        } catch {
            badge.textContent = 'Erro ao contar resultados';
            badge.className   = 'rel-preview-badge no-results';
        }
    }, 400);
}

function limparPreview() {
    const badge = document.getElementById('rel-preview-badge');
    if (badge) {
        badge.textContent = 'Clique em "Visualizar" para contar resultados';
        badge.className   = 'rel-preview-badge';
    }
}
async function exportarRelatorio() {
    const colunas = coletarColunas();

    if (colunas.length === 0) {
        mostrarToastRelatorio('warning', 'Relatório', 'Selecione ao menos uma coluna.');
        return;
    }

    const btnExportar = document.getElementById('btn-exportar-pdf');
    const textoOriginal = btnExportar.innerHTML;

    btnExportar.disabled = true;
    btnExportar.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Gerando PDF…';

    try {
        const res = await fetch(`${BASE_URL}/api/relatorio/exportar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                filtros: coletarFiltros(),
                colunas,
                ordenar: coletarOrdenacao(),
            }),
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const blob = await res.blob();

        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.download = `relatorio_orbe_${_dataHoje()}.pdf`;

        document.body.appendChild(link);
        link.click();
        link.remove();

        URL.revokeObjectURL(url);

        fecharModalRelatorio();

        mostrarToastRelatorio(
            'success',
            'Relatório exportado',
            'O PDF foi gerado com sucesso.'
        );

    } catch (err) {
        console.error('Erro ao exportar relatório:', err);

        mostrarToastRelatorio(
            'error',
            'Erro ao exportar',
            'Não foi possível gerar o PDF.'
        );

    } finally {
        btnExportar.disabled = false;
        btnExportar.innerHTML = textoOriginal;
    }
}

async function exportarRelatorioXlsx() {
    const colunas = coletarColunas();
    if (colunas.length === 0) {
        mostrarToastRelatorio('warning', 'Relatório', 'Selecione ao menos uma coluna.');
        return;
    }
    const btn = document.getElementById('btn-exportar-xlsx');
    const textoOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando Excel…';
    try {
        const res = await fetch(`${BASE_URL}/api/relatorio/exportar-xlsx`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                filtros: coletarFiltros(),
                colunas,
                ordenar: coletarOrdenacao(),
            }),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href     = url;
        link.download = `relatorio_orbe_${_dataHoje()}.xlsx`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        fecharModalRelatorio();
        mostrarToastRelatorio('success', 'Relatório exportado', 'O Excel foi gerado com sucesso.');
    } catch (err) {
        console.error('Erro ao exportar Excel:', err);
        mostrarToastRelatorio('error', 'Erro ao exportar', 'Não foi possível gerar o Excel.');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = textoOriginal;
    }
}

function _dataHoje() {
    const d = new Date();
    return `${d.getFullYear()}${String(d.getMonth()+1).padStart(2,'0')}${String(d.getDate()).padStart(2,'0')}`;
}

function mostrarToastRelatorio(tipo, titulo, descricao = '') {
    if (typeof Toast !== 'undefined') {
        if (typeof Toast[tipo] === 'function') {
            Toast[tipo](titulo, descricao);
            return;
        }
    }

    alert(`${titulo}\n${descricao}`);
}