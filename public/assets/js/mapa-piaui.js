document.addEventListener('DOMContentLoaded', async () => {
    const mapaDiv = document.getElementById('mapa-piaui');
    const loading = document.getElementById('loading-mapa');
    const sidebar = document.getElementById('sidebar-unidade');
    const sidebarConteudo = document.getElementById('sidebar-conteudo');
    const btnFecharSidebar = document.getElementById('fechar-sidebar');

    if (!mapaDiv) return;

    const mapa = L.map('mapa-piaui').setView([-7.7183, -42.7289], 7);

    // CONTORNO DO PIAUÍ
    fetch('/assets/geojson/piaui.geojson')
        .then(res => res.json())
        .then(geoData => {
            const piaui = geoData.features.find(
                estado => estado.properties.name === 'Piauí'
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

    // ─────────────────────────────────────────────
    // JITTER: desloca levemente coordenadas iguais
    // para evitar marcadores sobrepostos.
    // Aplica ~0.018° (~2 km) de variação aleatória.
    // ─────────────────────────────────────────────
    function aplicarJitter(lista, raioGraus = 0.018) {
        const vistos = {};

        return lista.map(item => {
            const chave = `${parseFloat(item.latitude).toFixed(4)},${parseFloat(item.longitude).toFixed(4)}`;

            if (!vistos[chave]) {
                vistos[chave] = 0;
            }

            const indice = vistos[chave];
            vistos[chave]++;

            if (indice === 0) {
                return { ...item };
            }

            // Distribui em círculo ao redor do ponto original
            const angulo = (indice / 6) * 2 * Math.PI + Math.random() * 0.3;
            const raio = raioGraus * (0.6 + Math.random() * 0.4);

            return {
                ...item,
                latitude: parseFloat(item.latitude) + raio * Math.sin(angulo),
                longitude: parseFloat(item.longitude) + raio * Math.cos(angulo)
            };
        });
    }

    // ─────────────────────────────────────────────
    // SEMI-CÍRCULO: posiciona N colaboradores em
    // arco ao redor das coordenadas do gestor/unidade.
    // raioGraus ~0.06° ≈ 6–7 km
    // ─────────────────────────────────────────────
    function posicionarEmSemicirculo(colaboradores, centerLat, centerLng, raioGraus = 0.06) {
        const total = colaboradores.length;

        return colaboradores.map((colab, i) => {
            // Arco de 200° centrado para o sul (180°),
            // para que os colaboradores fiquem abaixo/ao redor do gestor
            const anguloInicio = -Math.PI * 0.45; // ~-80°
            const anguloFim    =  Math.PI * 1.45; // ~260°
            const angulo = total === 1
                ? Math.PI / 2
                : anguloInicio + (i / (total - 1)) * (anguloFim - anguloInicio);

            return {
                ...colab,
                latitude:  centerLat + raioGraus * Math.sin(angulo),
                longitude: centerLng + raioGraus * Math.cos(angulo)
            };
        });
    }

    function limparMapa() {
        markers.forEach(m => mapa.removeLayer(m));
        linhas.forEach(l => mapa.removeLayer(l));
        markers.length = 0;
        linhas.length = 0;
    }

    function abrirSidebarUnidade(unidade, colaboradores) {
        if (!colaboradores.length) return;
        const imagem = colaboradores[0].imagem_base;
        sidebarConteudo.innerHTML = `
            <img src="${imagem}" class="sidebar-imagem">
            <h2 class="sidebar-titulo">${unidade}</h2>
            <p class="sidebar-subtitulo">${colaboradores.length} colaborador(es)</p>
            ${colaboradores.map(colab => `
                <div class="sidebar-colaborador">
                    <strong>${colab.nome}</strong>
                    <p><strong>Patente:</strong> ${colab.patente ?? '-'}</p>
                    <p><strong>Cargo:</strong> ${colab.cargo ?? '-'}</p>
                    <p><strong>Cidade:</strong> ${colab.cidade}</p>
                    <span class="sidebar-status">Plantão ${colab.status}</span>
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
        const role = window.USER_ROLE;

        // =====================================================
        // ADMIN
        // =====================================================
        if (role === 'admin') {
            const admin    = usuarios.find(u => u.role === 'admin');
            const gestores = usuarios.filter(u => u.role === 'gestor');

            if (!admin) return;

            // Aplica jitter nos gestores para separar os que
            // ficaram com a mesma coordenada de unidade
            const gestoresComJitter = aplicarJitter(gestores);

            function renderizarVisaoAdmin() {
                limparMapa();
                gestorExpandido = false;

                // ADMIN
                const markerAdmin = L.marker([
                    parseFloat(admin.latitude),
                    parseFloat(admin.longitude)
                ], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                        shadowUrl: 'https://unpkg.com/leaflet/dist/images/marker-shadow.png',
                        iconSize:    [25, 41],
                        iconAnchor:  [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize:  [41, 41]
                    })
                }).addTo(mapa);
                markerAdmin.bindPopup(`<strong>${admin.nome}</strong><br>ADMIN`);
                markers.push(markerAdmin);

                // GESTORES
                gestoresComJitter.forEach(gestor => {
                    const latGestor = parseFloat(gestor.latitude);
                    const lngGestor = parseFloat(gestor.longitude);

                    const markerGestor = L.marker([latGestor, lngGestor]).addTo(mapa);
                    markerGestor.bindPopup(`<strong>${gestor.nome}</strong><br>Gestor`);
                    markers.push(markerGestor);

                    // LINHA ADMIN → GESTOR
                    const linha = L.polyline([
                        [parseFloat(admin.latitude), parseFloat(admin.longitude)],
                        [latGestor, lngGestor]
                    ]).addTo(mapa);
                    linhas.push(linha);

                    // CLICK NO GESTOR
                    markerGestor.on('click', (e) => {
                        L.DomEvent.stopPropagation(e);

                        const colaboradoresDaUnidade = usuarios.filter(u =>
                            u.unidade_id === gestor.unidade_id
                        );

                        abrirSidebarUnidade(gestor.unidade, colaboradoresDaUnidade);
                        limparMapa();
                        gestorExpandido = true;

                        // ADMIN
                        const novoAdmin = L.marker([
                            parseFloat(admin.latitude),
                            parseFloat(admin.longitude)
                        ], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                shadowUrl: 'https://unpkg.com/leaflet/dist/images/marker-shadow.png',
                                iconSize:    [25, 41],
                                iconAnchor:  [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize:  [41, 41]
                            })
                        }).addTo(mapa);
                        novoAdmin.bindPopup(`<strong>${admin.nome}</strong><br>ADMIN`);
                        markers.push(novoAdmin);

                        // GESTOR CLICADO — usa coordenada da unidade como centro
                        const novoGestor = L.marker([latGestor, lngGestor]).addTo(mapa);
                        novoGestor.bindPopup(`<strong>${gestor.nome}</strong><br>Gestor`);
                        markers.push(novoGestor);

                        // LINHA ADMIN → GESTOR
                        const linhaGestor = L.polyline([
                            [parseFloat(admin.latitude), parseFloat(admin.longitude)],
                            [latGestor, lngGestor]
                        ]).addTo(mapa);
                        linhas.push(linhaGestor);

                        // COLABORADORES em semi-círculo ao redor do gestor
                        const colaboradores = usuarios.filter(c =>
                            c.role === 'user' &&
                            c.unidade_id === gestor.unidade_id
                        );

                        const colaboradoresPositionados = posicionarEmSemicirculo(
                            colaboradores,
                            latGestor,
                            lngGestor
                        );

                        colaboradoresPositionados.forEach(colab => {
                            const markerColab = L.marker([
                                parseFloat(colab.latitude),
                                parseFloat(colab.longitude)
                            ]).addTo(mapa);
                            markerColab.bindPopup(`<strong>${colab.nome}</strong><br>Colaborador`);
                            markers.push(markerColab);

                            // LINHA GESTOR → COLABORADOR
                            const linhaColab = L.polyline([
                                [latGestor, lngGestor],
                                [parseFloat(colab.latitude), parseFloat(colab.longitude)]
                            ]).addTo(mapa);
                            linhas.push(linhaColab);
                        });
                    });
                });
            }

            renderizarVisaoAdmin();

            // CLICK NO MAPA = RESET
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

            const latGestor = parseFloat(gestor.latitude);
            const lngGestor = parseFloat(gestor.longitude);

            // GESTOR
            const markerGestor = L.marker([latGestor, lngGestor]).addTo(mapa);
            markerGestor.bindPopup(`<strong>${gestor.nome}</strong><br>Gestor`);
            markers.push(markerGestor);

            // COLABORADORES em semi-círculo ao redor do gestor
            const colaboradoresPositionados = posicionarEmSemicirculo(
                colaboradores,
                latGestor,
                lngGestor
            );

            colaboradoresPositionados.forEach(colab => {
                const markerColab = L.marker([
                    parseFloat(colab.latitude),
                    parseFloat(colab.longitude)
                ]).addTo(mapa);
                markerColab.bindPopup(`<strong>${colab.nome}</strong><br>Colaborador`);
                markers.push(markerColab);

                // LINHA GESTOR → COLABORADOR
                const linha = L.polyline([
                    [latGestor, lngGestor],
                    [parseFloat(colab.latitude), parseFloat(colab.longitude)]
                ]).addTo(mapa);
                linhas.push(linha);
            });
        }

    } catch (err) {
        console.error('Erro mapa:', err);
    } finally {
        loading.style.display = 'none';
        mapaDiv.style.display = 'block';
        setTimeout(() => mapa.invalidateSize(), 200);
    }
});