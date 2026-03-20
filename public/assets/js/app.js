/*
 * Este arquivo é responsável por controlar o tema da interface (light/dark).
 * A função toggleTheme alterna o tema atual e salva a preferência no localStorage
 * para que ela seja mantida entre recarregamentos da página. Ao carregar o DOM,
 * o script verifica se existe um tema salvo e o aplica automaticamente.
 */

function toggleTheme() {

    const html = document.documentElement;

    const current = html.getAttribute("data-theme");

    const next = current === "light" ? "dark" : "light";

    html.setAttribute("data-theme", next);

    localStorage.setItem("theme", next);
}

document.addEventListener("DOMContentLoaded", () => {

    const saved = localStorage.getItem("theme");

    if (saved) {
        document.documentElement.setAttribute("data-theme", saved);
    }

});