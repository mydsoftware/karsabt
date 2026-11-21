document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.main-nav');
    if (!button || !navigation) return;

    const mobileQuery = window.matchMedia('(max-width: 760px)');

    const closeMenu = function (returnFocus) {
        navigation.classList.remove('is-open');
        button.setAttribute('aria-expanded', 'false');
        if (returnFocus) button.focus();
    };

    button.addEventListener('click', function () {
        const isOpen = button.getAttribute('aria-expanded') === 'true';
        navigation.classList.toggle('is-open', !isOpen);
        button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        if (!isOpen && mobileQuery.matches) {
            const firstLink = navigation.querySelector('a[href]');
            if (firstLink) firstLink.focus();
        }
    });

    navigation.addEventListener('click', function (event) {
        if (event.target.closest('a')) closeMenu(false);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
            closeMenu(true);
        }
    });

    document.addEventListener('click', function (event) {
        if (!navigation.contains(event.target) && !button.contains(event.target)) {
            closeMenu(false);
        }
    });

    const handleViewportChange = function () {
        if (!mobileQuery.matches) closeMenu(false);
    };
    if (typeof mobileQuery.addEventListener === 'function') {
        mobileQuery.addEventListener('change', handleViewportChange);
    } else if (typeof mobileQuery.addListener === 'function') {
        mobileQuery.addListener(handleViewportChange);
    }
    handleViewportChange();
});
