document.addEventListener('DOMContentLoaded', function () {
    const currentPath = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.main-nav a');

    navLinks.forEach(link => {
        const target = link.getAttribute('href');
        if (target === currentPath) {
            link.classList.add('active');
        }
    });
});
