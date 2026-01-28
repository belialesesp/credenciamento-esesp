// HEADER ENCOLHER AO ROLAR
window.addEventListener("scroll", () => {
    const header = document.querySelector(".topo");

    if (window.scrollY > 80) {
        header.classList.add("encolhido");
    } else {
        header.classList.remove("encolhido");
    }
});

const seta = document.querySelector('.seta-usuario');
const dropdown = document.querySelector('.dropdown-usuario');

// Abre/Fecha ao clicar na seta
seta.addEventListener('click', (e) => {
    e.stopPropagation(); // Impede o clique de propagar para o documento
    dropdown.classList.toggle('ativo');
});

// Fecha o menu se o usuário clicar em qualquer outro lugar da tela
document.addEventListener('click', () => {
    dropdown.classList.remove('ativo');
});