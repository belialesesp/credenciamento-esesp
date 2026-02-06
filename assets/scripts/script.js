
const setaUsuario = document.querySelector('.seta-usuario');
const dropdownUsuario = document.querySelector('.dropdown-usuario');
document.addEventListener('DOMContentLoaded', function() {
    const seta = document.getElementById('seta-mestre');
    const grid = document.getElementById('wrapper-grid-trilhas');

setaUsuario.addEventListener('click', (e) => {
    e.stopPropagation(); // não deixa o clique subir
    dropdownUsuario.classList.toggle('ativo');
});

// fecha ao clicar fora
document.addEventListener('click', () => {
    dropdownUsuario.classList.remove('ativo');
});
    seta.addEventListener('click', function() {
        // O próprio Bootstrap cuida da suavidade com o método .collapse('toggle')
        $(grid).collapse('toggle');
    });
});
