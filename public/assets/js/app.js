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