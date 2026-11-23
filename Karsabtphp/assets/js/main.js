(() => {
  const toggle = document.getElementById('menuToggle');
  const nav = document.getElementById('nav');

  if (toggle && nav) {
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-controls', 'nav');

    const closeMenu = () => {
      nav.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'باز کردن منو');
    };

    const openMenu = () => {
      nav.classList.add('open');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', 'بستن منو');
    };

    toggle.addEventListener('click', () => {
      if (nav.classList.contains('open')) closeMenu();
      else openMenu();
    });

    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && nav.classList.contains('open')) {
        closeMenu();
        toggle.focus();
      }
    });

    document.addEventListener('click', (event) => {
      if (!nav.classList.contains('open')) return;
      if (nav.contains(event.target) || toggle.contains(event.target)) return;
      closeMenu();
    });
  }

  document.addEventListener('click', (event) => {
    const anchor = event.target.closest('a[href]');
    if (!anchor) return;
    const href = anchor.getAttribute('href') || '';
    let key = null;
    if (href.startsWith('tel:')) key = 'phone';
    else if (/whatsapp\.com|wa\.me/i.test(href)) key = 'whatsapp';
    else if (/t\.me|telegram/i.test(href)) key = 'telegram';
    else if (/rubika/i.test(href)) key = 'rubika';
    else if (/eitaa/i.test(href)) key = 'eitaa';
    if (!key) return;
    try {
      fetch('/track-click.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event=' + encodeURIComponent(key),
        keepalive: true,
      }).catch(() => {});
    } catch (_) {}
  });
})();
