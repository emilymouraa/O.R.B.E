document.addEventListener('DOMContentLoaded', async () => {

    const mapaDiv = document.getElementById('mapa-piaui');
    const loading = document.getElementById('loading-mapa');
    const sidebar = document.getElementById('sidebar-unidade');

    const sidebarConteudo =
        document.getElementById('sidebar-conteudo');

    const btnFecharSidebar =
        document.getElementById('fechar-sidebar');

    if (!mapaDiv) return;

    const mapa = L.map('mapa-piaui').setView([-7.7183, -42.7289], 7);

    // CONTORNO DO PIAUÍ
    fetch('/assets/geojson/piaui.geojson')
        .then(res => res.json())
        .then(geoData => {

            const piaui = geoData.features.find(
                estado =>
                    estado.properties.name === 'Piauí'
            );

            L.geoJSON(piaui, {
                style: {
                    color: '#000',
                    weight: 1.5,
                    fillColor: '#ef4444',
                    fillOpacity: 0.15
                }
            }).addTo(mapa);

        });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    const markers = [];
    const linhas = [];
    let gestorExpandido = false;

    function limparMapa() {

        markers.forEach(m => mapa.removeLayer(m));
        linhas.forEach(l => mapa.removeLayer(l));

        markers.length = 0;
        linhas.length = 0;
    }

    function abrirSidebarUnidade(unidade, colaboradores) {

        if (!colaboradores.length) return;

        const imagem =
            colaboradores[0].imagem_base;

        sidebarConteudo.innerHTML = `

            <img
                src="${imagem}"
                class="sidebar-imagem"
            >

            <h2 class="sidebar-titulo">
                ${unidade}
            </h2>

            <p class="sidebar-subtitulo">
                ${colaboradores.length} colaborador(es)
            </p>

            ${colaboradores.map(colab => `

                <div class="sidebar-colaborador">

                    <strong>${colab.nome}</strong>

                    <p>
                        <strong>Patente:</strong>
                        ${colab.patente ?? '-'}
                    </p>

                    <p>
                        <strong>Cargo:</strong>
                        ${colab.cargo ?? '-'}
                    </p>

                    <p>
                        <strong>Cidade:</strong>
                        ${colab.cidade}
                    </p>

                    <span class="sidebar-status">
                        Plantão ${colab.status}
                    </span>

                </div>

            `).join('')}

        `;

        sidebar.classList.add('active');
    }

    btnFecharSidebar.addEventListener('click', () => {
        sidebar.classList.remove('active');
    });

    try {

        const response = await fetch('/api/painel/mapa');
        const result = await response.json();

        const usuarios = result.data;

        console.log(usuarios);

        const role = window.USER_ROLE;

        // =====================================================
        // ADMIN
        // =====================================================

        if (role === 'admin') {

        const admin = usuarios.find(u => u.role === 'admin');

        const gestores = usuarios.filter(u => u.role === 'gestor');

        if (!admin) return;

        function renderizarVisaoAdmin() {

            limparMapa();

            gestorExpandido = false;

            // =========================
            // ADMIN
            // =========================

            const markerAdmin = L.marker([
                admin.latitude,
                admin.longitude
            ]).addTo(mapa);

            markerAdmin.bindPopup(`
                <strong>${admin.nome}</strong><br>
                ADMIN
            `);

            markers.push(markerAdmin);

            // =========================
            // GESTORES
            // =========================

            gestores.forEach(gestor => {

                const markerGestor = L.marker([
                    gestor.latitude,
                    gestor.longitude
                ]).addTo(mapa);

                markerGestor.bindPopup(`
                    <strong>${gestor.nome}</strong><br>
                    Gestor
                `);

                markers.push(markerGestor);

                // LINHA ADMIN -> GESTOR

                const linha = L.polyline([
                    [admin.latitude, admin.longitude],
                    [gestor.latitude, gestor.longitude]
                ]).addTo(mapa);

                linhas.push(linha);

                // ====================================
                // CLICK GESTOR
                // ====================================

                markerGestor.on('click', (e) => {

                    L.DomEvent.stopPropagation(e);

                    const colaboradoresDaUnidade =
                        usuarios.filter(u =>
                            u.unidade_id === gestor.unidade_id
                        );

                    abrirSidebarUnidade(
                        gestor.unidade,
                        colaboradoresDaUnidade
                    );

                    limparMapa();

                    gestorExpandido = true;

                    // =========================
                    // ADMIN
                    // =========================

                    const novoAdmin = L.marker([
                        admin.latitude,
                        admin.longitude
                    ]).addTo(mapa);

                    novoAdmin.bindPopup(`
                        <strong>${admin.nome}</strong><br>
                        ADMIN
                    `);

                    markers.push(novoAdmin);

                    // =========================
                    // GESTOR CLICADO
                    // =========================

                    const novoGestor = L.marker([
                        gestor.latitude,
                        gestor.longitude
                    ]).addTo(mapa);

                    novoGestor.bindPopup(`
                        <strong>${gestor.nome}</strong><br>
                        Gestor
                    `);

                    markers.push(novoGestor);

                    // LINHA ADMIN -> GESTOR

                    const linhaGestor = L.polyline([
                        [admin.latitude, admin.longitude],
                        [gestor.latitude, gestor.longitude]
                    ]).addTo(mapa);

                    linhas.push(linhaGestor);

                    // =========================
                    // COLABORADORES
                    // =========================

                    const colaboradores = usuarios.filter(c =>
                        c.role === 'user' &&
                        c.unidade_id === gestor.unidade_id
                    );

                    colaboradores.forEach(colab => {

                        const markerColab = L.marker([
                            colab.latitude,
                            colab.longitude
                        ]).addTo(mapa);

                        markerColab.bindPopup(`
                            <strong>${colab.nome}</strong><br>
                            Colaborador
                        `);

                        markers.push(markerColab);

                        // LINHA GESTOR -> COLAB

                        const linhaColab = L.polyline([
                            [gestor.latitude, gestor.longitude],
                            [colab.latitude, colab.longitude]
                        ]).addTo(mapa);

                        linhas.push(linhaColab);

                    });

                });

            });

        }

        // =========================
        // PRIMEIRA RENDERIZAÇÃO
        // =========================

        renderizarVisaoAdmin();

        // =========================
        // CLICK NO MAPA = RESET
        // =========================

        mapa.on('click', () => {

            if (gestorExpandido) {

                renderizarVisaoAdmin();

            }

        });

    }

        // =====================================================
        // GESTOR
        // =====================================================

        if (role === 'gestor') {

            const gestor = usuarios.find(u => u.role === 'gestor');

            const colaboradores = usuarios.filter(u => u.role === 'user');

            if (!gestor) return;

            // GESTOR
            const markerGestor = L.marker([
                gestor.latitude,
                gestor.longitude
            ]).addTo(mapa);

            markerGestor.bindPopup(`
                <strong>${gestor.nome}</strong><br>
                Gestor
            `);

            markers.push(markerGestor);

            // COLABORADORES
            colaboradores.forEach(colab => {

                const markerColab = L.marker([
                    colab.latitude,
                    colab.longitude
                ]).addTo(mapa);

                markerColab.bindPopup(`
                    <strong>${colab.nome}</strong><br>
                    Colaborador
                `);

                markers.push(markerColab);

                // LINHA
                const linha = L.polyline([
                    [gestor.latitude, gestor.longitude],
                    [colab.latitude, colab.longitude]
                ]).addTo(mapa);

                linhas.push(linha);

            });

        }

    } catch (err) {

        console.error('Erro mapa:', err);

    } finally {

        loading.style.display = 'none';
        mapaDiv.style.display = 'block';

        setTimeout(() => {
            mapa.invalidateSize();
        }, 200);

    }

});