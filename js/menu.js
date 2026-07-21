document.addEventListener('DOMContentLoaded', () => {
    const nav = document.querySelector('nav');
    const toggle = document.querySelector('.menu-toggle');

    if (!nav || !toggle) return;

    toggle.addEventListener('click', () => {
        const isOpen = nav.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
