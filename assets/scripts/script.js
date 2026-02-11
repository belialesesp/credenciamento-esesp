document.addEventListener('DOMContentLoaded', function () {

    const btnUsuario = document.getElementById('btn-usuario');
    const dropdownUsuario = document.querySelector('.dropdown-usuario');
    const setaUsuario = document.querySelector('.seta-usuario');

    if (!btnUsuario || !dropdownUsuario) return;

    btnUsuario.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdownUsuario.classList.toggle('ativo');

        if (setaUsuario) {
            setaUsuario.classList.toggle('ativo');
        }
    });

    document.addEventListener('click', function () {
        dropdownUsuario.classList.remove('ativo');

        if (setaUsuario) {
            setaUsuario.classList.remove('ativo');
        }
    });

});

document.addEventListener('DOMContentLoaded', function () {
    const gatilho = document.getElementById('btn-abrir-tudo');
    const collapseElement = document.getElementById('wrapper-grid-trilhas');
    const seta = document.getElementById('seta-mestre');
    const areaTrilhas = document.querySelector('.arcodeon-container');

    if (!gatilho || !collapseElement || !areaTrilhas) return;

    /* ===============================
       SINCRONIZA SETA COM TRILHAS
    =============================== */
    $(collapseElement).on('show.bs.collapse', function (e) {
        if (e.target !== collapseElement) return;
        seta.classList.add('ativo');
    });

    $(collapseElement).on('hide.bs.collapse', function (e) {
        // garante que é o collapse PAI
        if (e.target !== collapseElement) return;

        seta.classList.remove('ativo');

        // fecha tudo que estiver aberto dentro
        $(collapseElement)
            .find('.collapse.show')
            .collapse('hide');
    });

    /* ===============================
       CLIQUE NO GATILHO
    =============================== */
    gatilho.addEventListener('click', function (e) {
        e.stopPropagation();
        $(collapseElement).collapse('toggle');
    });

    /* ===============================
       FECHAR AO CLICAR FORA
    =============================== */
    document.addEventListener('click', function (e) {
        const isVisible = collapseElement.classList.contains('show');
        if (!isVisible) return;

        const clickedOnGatilho = gatilho.contains(e.target);
        const clickedInsideContent = areaTrilhas.contains(e.target);

        if (!clickedOnGatilho && !clickedInsideContent) {
            $(collapseElement).collapse('hide');
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {

    const gatilhoCargos = document.getElementById('btn-abrir-cargos');
    const collapseCargos = document.getElementById('wrapper-grid-cargos');
    const setaCargos = document.getElementById('seta-cargos');
    const areaCargos = document.querySelector('#wrapper-grid-cargos .container');

    if (!gatilhoCargos || !collapseCargos || !areaCargos) return;

    /* ===============================
       SINCRONIZA SETA COM CARGOS
    =============================== */
    $(collapseCargos).on('show.bs.collapse', function (e) {
        if (e.target !== collapseCargos) return;
        setaCargos.classList.add('ativo');
    });

    $(collapseCargos).on('hide.bs.collapse', function (e) {
        if (e.target !== collapseCargos) return;

        setaCargos.classList.remove('ativo');

        // fecha todos os cargos internos abertos
        $(collapseCargos)
            .find('.collapse.show')
            .collapse('hide');
    });

    /* ===============================
       CLIQUE NO GATILHO
    =============================== */
    gatilhoCargos.addEventListener('click', function (e) {
        e.stopPropagation();
        $(collapseCargos).collapse('toggle');
    });

    /* ===============================
       FECHAR AO CLICAR FORA
    =============================== */
    document.addEventListener('click', function (e) {
        const isVisible = collapseCargos.classList.contains('show');
        if (!isVisible) return;

        const clickedOnGatilho = gatilhoCargos.contains(e.target);
        const clickedInsideContent = areaCargos.contains(e.target);

        if (!clickedOnGatilho && !clickedInsideContent) {
            $(collapseCargos).collapse('hide');
        }
    });

});
