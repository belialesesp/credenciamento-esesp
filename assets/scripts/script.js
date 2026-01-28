// HEADER ENCOLHER AO ROLAR
window.addEventListener("scroll", () => {
    const header = document.querySelector(".topo");

    if (window.scrollY > 80) {
        header.classList.add("encolhido");
    } else {
        header.classList.remove("encolhido");
    }
});

// DROPDOWN USUÁRIO
const usuarioArea = document.querySelector(".usuario-area");
const dropdown = document.querySelector(".dropdown-usuario");

usuarioArea.addEventListener("click", () => {
    dropdown.style.display =
        dropdown.style.display === "flex" ? "none" : "flex";
});
