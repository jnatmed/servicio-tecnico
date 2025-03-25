document.addEventListener('DOMContentLoaded', () => {
    const menuItems = document.querySelectorAll('.side-nav ul > li');

    menuItems.forEach(item => {
        const submenu = item.querySelector('.sub-menu');
        const link = item.querySelector('.menu-toggle');

        if (submenu && link) {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                // Cerrar otros submenús abiertos
                document.querySelectorAll('.sub-menu.open').forEach(openSub => {
                    if (openSub !== submenu) {
                        openSub.classList.remove('open');
                        const otherArrow = openSub.parentElement.querySelector('.arrow');
                        if (otherArrow) otherArrow.classList.remove('rotated');
                    }
                });

                // Alternar visibilidad y flecha del actual
                submenu.classList.toggle('open');
                const arrow = link.querySelector('.arrow');
                if (arrow) arrow.classList.toggle('rotated');
            });
        }
    });
});
