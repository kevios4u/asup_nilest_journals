const menuToggle = document.querySelector('.menu-toggle');
const primaryNav = document.querySelector('#primary-nav');
const menuIcon = menuToggle?.querySelector('ion-icon');

const setMenu = (open) => {
    menuToggle?.setAttribute('aria-expanded', String(open));
    primaryNav?.classList.toggle('is-open', open);
    menuIcon?.setAttribute('name', open ? 'close-outline' : 'menu-outline');
};

menuToggle?.addEventListener('click', () => {
    setMenu(menuToggle.getAttribute('aria-expanded') !== 'true');
});

primaryNav?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setMenu(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menuToggle?.getAttribute('aria-expanded') === 'true') {
        setMenu(false);
        menuToggle.focus();
    }
});

const siteHeader = document.querySelector('.site-header');

if (siteHeader) {
    const syncHeader = () => siteHeader.classList.toggle('is-scrolled', window.scrollY > 12);
    syncHeader();
    window.addEventListener('scroll', syncHeader, { passive: true });
}

const searchInput = document.querySelector('#paper-search');
const paperItems = [...document.querySelectorAll('.paper-item')];
const emptySearch = document.querySelector('#empty-search');
const resultCount = document.querySelector('#result-count');

searchInput?.addEventListener('input', () => {
    const query = searchInput.value.trim().toLowerCase();
    let visibleCount = 0;

    paperItems.forEach((paper) => {
        const matches = paper.dataset.search.includes(query);
        paper.hidden = !matches;
        visibleCount += Number(matches);
    });

    if (emptySearch) emptySearch.hidden = visibleCount > 0;
    if (resultCount) resultCount.textContent = String(visibleCount);
});

document.addEventListener('keydown', (event) => {
    if (event.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
        event.preventDefault();
        searchInput?.focus();
    }
});

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const input = document.getElementById(toggle.dataset.passwordToggle);
        if (!input) return;

        const revealed = input.type === 'password';
        input.type = revealed ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(revealed));
        toggle.setAttribute('aria-label', revealed ? 'Hide password' : 'Show password');

        const icon = toggle.querySelector('ion-icon');
        if (icon) icon.setAttribute('name', revealed ? 'eye-off-outline' : 'eye-outline');

        input.focus();
    });
});
