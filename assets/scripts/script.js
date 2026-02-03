document.addEventListener('DOMContentLoaded', function() {
    const seta = document.getElementById('seta-mestre');
    const grid = document.getElementById('wrapper-grid-trilhas');

    seta.addEventListener('click', function() {
        // O próprio Bootstrap cuida da suavidade com o método .collapse('toggle')
        $(grid).collapse('toggle');
    });
});