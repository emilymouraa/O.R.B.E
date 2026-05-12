<?php

?>
    <script src="/assets/js/app.js"></script>
    <script>
    (function () {
        const btn      = document.getElementById('notifBtn');
        const dropdown = document.getElementById('notifDropdown');
        const badge    = document.getElementById('notifBadge');
        const list     = document.getElementById('notifList');
        const markAll  = document.getElementById('notifMarkAll');

        if (!btn) return; // página sem sessão (login, etc.)

        let notifs = [];

        async function loadNotifs() {
            try {
                const res  = await fetch('/api/notificacoes');
                const json = await res.json();
                notifs = json.data ?? [];
                renderNotifs();
            } catch {
                list.innerHTML = '<li class="notif-empty">Erro ao carregar.</li>';
            }
        }

        function renderNotifs() {
            const unread = notifs.filter(n => !n.lida);
            // Badge
            if (unread.length > 0) {
                badge.textContent = unread.length > 9 ? '9+' : unread.length;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
            // Lista
            if (notifs.length === 0) {
                list.innerHTML = '<li class="notif-empty">Nenhuma notificação.</li>';
                return;
            }
            list.innerHTML = notifs.map(n => `
                <li class="notif-item ${n.lida ? '' : 'unread'}">
                    <span class="notif-item-icon ${n.nivel}">
                        <i class="fas ${iconFor(n.nivel)}"></i>
                    </span>
                    <span class="notif-item-body">
                        <span class="notif-item-title">${n.titulo}</span>
                        <span class="notif-item-desc">${n.descricao}</span>
                    </span>
                </li>
            `).join('');
        }

        function iconFor(nivel) {
            if (nivel === 'danger') return 'fa-circle-exclamation';
            if (nivel === 'warn')   return 'fa-triangle-exclamation';
            return 'fa-circle-info';
        }

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('open');
            dropdown.setAttribute('aria-hidden', !dropdown.classList.contains('open'));
        });

        document.addEventListener('click', () => {
            dropdown.classList.remove('open');
            dropdown.setAttribute('aria-hidden', 'true');
        });

        markAll?.addEventListener('click', () => {
            notifs = notifs.map(n => ({ ...n, lida: true }));
            renderNotifs();
        });

        // Carrega ao abrir e recarrega a cada 60s
        loadNotifs();
        setInterval(loadNotifs, 60_000);
    })();
    </script>
</body>
</html>