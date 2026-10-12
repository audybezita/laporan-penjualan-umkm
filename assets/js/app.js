document.addEventListener('DOMContentLoaded', function () {
    const currentPath = window.location.pathname.split('/').pop();
    if (!currentPath) return;

    const navLinks = document.querySelectorAll('.main-nav a');
    navLinks.forEach(link => {
        const target = link.getAttribute('href');
        if (target === currentPath) {
            link.style.fontWeight = '700';
            link.style.opacity = '1';
        }
    });
});
