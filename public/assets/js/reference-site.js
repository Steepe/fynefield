(() => {
    const toggle = document.querySelector('[data-nav-toggle]');
    const menu = document.querySelector('#site-menu');
    if (toggle && menu) toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        toggle.textContent = open ? 'Close' : 'Menu';
        menu.hidden = !open;
    });

    document.querySelectorAll('[data-profile-open]').forEach((button) => button.addEventListener('click', () => {
        document.getElementById(button.dataset.profileOpen || '')?.showModal();
    }));
    document.querySelectorAll('.rf-profile').forEach((dialog) => {
        dialog.querySelector('[data-profile-close]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    });

    document.querySelectorAll('[data-newsletter]').forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(form);
        try {
            const response = await fetch(form.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error();
            form.outerHTML = '<p><strong>Thank you — you are subscribed to operational updates.</strong></p>';
        } catch (_) { form.querySelector('[data-newsletter-error]')?.removeAttribute('hidden'); }
    }));
})();
