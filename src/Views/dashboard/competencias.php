<?php
$pageTitle = 'Gestão de Competências';
$bodyClass = 'dashboard-page';

require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<div class="main-content">

    <!-- 🔹 HEADER -->
    <header class="header-section">
        <div class="title-group">
            <h1>Gestão de Competências</h1>
            <p>Registro de cursos, certificações e especializações</p>
        </div>
    </header>

    <!-- 🔹 CARDS -->
    <section class="gc-cards">

        <div class="gc-card">
            <span>Total</span>
            <strong id="total">—</strong>
        </div>

        <div class="gc-card">
            <span>Cursos</span>
            <strong id="cursos">—</strong>
        </div>

        <div class="gc-card">
            <span>Certificações</span>
            <strong id="certificacoes">—</strong>
        </div>

        <div class="gc-card">
            <span>Especializações</span>
            <strong id="especializacoes">—</strong>
        </div>

    </section>

    <!-- 🔹 FILTROS -->
    <section class="filters-container">

        <div class="filter-group">
            <input
                type="text"
                id="busca"
                placeholder="Buscar por competência ou servidor..."
            >
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

    </section>

    <!-- 🔹 LISTA -->
    <section class="gc-section">
        <div id="lista-competencias" class="gc-lista">

            <div class="table-feedback">
                <i class="fas fa-spinner fa-spin"></i> Carregando competências...
            </div>

        </div>
    </section>

</div>

<button class="btn-theme-fixed" onclick="toggleTheme()">🌙</button>

<script>
let dadosOriginais = [];

// 🔹 CARREGAR DADOS
async function carregarCompetencias() {
    try {
        const res = await fetch('/api/competencias');
        const response = await res.json();

        const data = response.data || [];

        dadosOriginais = data;

        atualizarCards(data);
        renderizarLista(data);

    } catch (e) {
        console.error(e);
        document.getElementById('lista-competencias').innerHTML =
            '<div class="table-feedback">Erro ao carregar dados</div>';
    }
}

// 🔹 CARDS
function atualizarCards(data) {
    document.getElementById('total').textContent = data.length;

    document.getElementById('cursos').textContent =
        data.filter(c => c.tipo === 'curso').length;

    document.getElementById('certificacoes').textContent =
        data.filter(c => c.tipo === 'certificacao').length;

    document.getElementById('especializacoes').textContent =
        data.filter(c => c.tipo === 'especializacao').length;
}

// 🔹 LISTA
function renderizarLista(data) {
    const container = document.getElementById('lista-competencias');

    if (!data.length) {
        container.innerHTML =
            '<div class="table-feedback">Nenhuma competência encontrada</div>';
        return;
    }

    container.innerHTML = data.map(c => `
        <div class="gc-item">

            <div class="gc-header">

                <div class="gc-title">
                    ${c.nome}
                </div>

                <span class="badge ${c.tipo}">
                    ${formatarTipo(c.tipo)}
                </span>

            </div>

            <div class="gc-info">

                <span>
                    <strong>Servidor:</strong>
                    ${c.servidor}
                </span>

                <span>
                    <i class="fas fa-calendar-alt"></i>
                    ${formatarData(c.data_conclusao)}
                </span>

                ${c.validade ? `
                    <span>
                        <i class="fas fa-clock"></i>
                        Validade: ${formatarData(c.validade)}
                    </span>
                ` : ''}

            </div>

        </div>
    `).join('');
}

// 🔹 FORMATADORES
function formatarTipo(tipo) {
    switch (tipo) {
        case 'curso': return 'Curso';
        case 'certificacao': return 'Certificação';
        case 'especializacao': return 'Especialização';
        case 'habilidade': return 'Habilidade';
        default: return tipo;
    }
}

function formatarData(data) {
    if (!data) return '-';
    return new Date(data).toLocaleDateString('pt-BR');
}

// 🔹 FILTRO
function filtrar() {
    const busca = document.getElementById('busca').value.toLowerCase();
    const tipo = document.getElementById('tipo').value;

    const filtrado = dadosOriginais.filter(c => {

        const matchBusca =
            c.nome.toLowerCase().includes(busca) ||
            c.servidor.toLowerCase().includes(busca);

        const matchTipo =
            tipo ? c.tipo === tipo : true;

        return matchBusca && matchTipo;
    });

    renderizarLista(filtrado);
}

// 🔹 EVENTOS
document.getElementById('busca').addEventListener('input', filtrar);
document.getElementById('tipo').addEventListener('change', filtrar);

// 🔹 INIT
carregarCompetencias();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>