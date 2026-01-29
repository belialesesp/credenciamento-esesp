const setaUsuario = document.querySelector('.seta-usuario');
const dropdownUsuario = document.querySelector('.dropdown-usuario');

setaUsuario.addEventListener('click', (e) => {
    e.stopPropagation(); // não deixa o clique subir
    dropdownUsuario.classList.toggle('ativo');
});

// fecha ao clicar fora
document.addEventListener('click', () => {
    dropdownUsuario.classList.remove('ativo');
});
