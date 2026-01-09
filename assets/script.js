document.addEventListener('DOMContentLoaded', function() {
    const menu = document.getElementById('menu');
    const btnMenu = document.getElementById('btn-menu');
    const btnFechar = document.getElementById('btn-fechar');
    const overlay = document.getElementById('overlay');
    const menuItems = document.querySelectorAll('.menu-list li');

    const iconHamburger = "https://cdn-icons-png.flaticon.com/512/1828/1828859.png";
    const iconArrow = "https://cdn-icons-png.flaticon.com/512/271/271220.png";

    function abrirMenu() {
        menu.classList.add('expanded');
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
        btnMenu.src = iconArrow;

        // animação sequencial dos li
        menuItems.forEach((item, index) => {
            item.style.transitionDelay = `${index * 0.05}s`;
        });
    }

    function fecharMenu() {
        menu.classList.remove('expanded');
        overlay.style.display = 'none';
        document.body.style.overflow = 'auto';
        btnMenu.src = iconHamburger;

        // remove delay quando fecha
        menuItems.forEach((item) => {
            item.style.transitionDelay = '0s';
        });
    }

    btnMenu.addEventListener('click', abrirMenu);
    btnFechar.addEventListener('click', fecharMenu);
    overlay.addEventListener('click', fecharMenu);
});

 const menu = document.getElementById('menu');
        const overlay = document.getElementById('overlay');
        const btnMenu = document.getElementById('btn-menu');
        const btnFechar = document.getElementById('btn-fechar');

        btnMenu.addEventListener('click', () => {
            menu.classList.add('expanded');
            overlay.style.display = 'block';
        });

        btnFechar.addEventListener('click', () => {
            menu.classList.remove('expanded');
            overlay.style.display = 'none';
        });

        overlay.addEventListener('click', () => {
            menu.classList.remove('expanded');
            overlay.style.display = 'none';
        });

    window.addEventListener("scroll", function () {
    const topo = document.querySelector(".topo");
    if (window.scrollY > 10) {
        topo.classList.add("scrolled");
    } else {
        topo.classList.remove("scrolled");
    }
});
