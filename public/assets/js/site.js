(() => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const navigation = document.querySelector('#site-navigation');

    if (navToggle && navigation) {
        navToggle.addEventListener('click', () => {
            const expanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(! expanded));
            navigation.classList.toggle('is-open', ! expanded);
        });
    }

    const finder = document.querySelector('[data-service-finder]');
    if (finder) {
        const result = finder.querySelector('[data-service-result]');
        const title = finder.querySelector('[data-service-result-title]');
        const copy = finder.querySelector('[data-service-result-copy]');
        const link = finder.querySelector('[data-service-result-link]');
        const choices = finder.querySelectorAll('[data-service-choice]');

        choices.forEach((choice) => {
            choice.setAttribute('aria-pressed', 'false');
            choice.addEventListener('click', () => {
                const key = choice.dataset.serviceChoice;
                const card = document.querySelector(`[data-service-card="${key}"]`);
                if (! card || ! result || ! title || ! copy || ! link) return;

                choices.forEach((button) => {
                    const selected = button === choice;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', String(selected));
                });
                document.querySelectorAll('[data-service-card]').forEach((item) => item.classList.toggle('is-recommended', item === card));
                title.textContent = card.querySelector('h2')?.textContent?.trim() || 'Recommended service';
                copy.textContent = card.querySelector('p:not(.eyebrow)')?.textContent?.trim() || '';
                link.href = card.querySelector('a')?.href || '#services';
                result.hidden = false;
            });
        });
    }

    const carousel = document.querySelector('[data-carousel]');
    if (carousel) {
        const slides = Array.from(carousel.querySelectorAll('[data-carousel-slide]'));
        const status = carousel.querySelector('[data-carousel-status]');
        let index = 0;
        const show = (nextIndex) => {
            index = (nextIndex + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                const active = slideIndex === index;
                slide.hidden = ! active;
                slide.classList.toggle('is-active', active);
            });
            if (status) status.textContent = `${index + 1} of ${slides.length}`;
        };
        carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => show(index - 1));
        carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => show(index + 1));
    }

    const galleryButtons = document.querySelectorAll('[data-lightbox-src]');
    if (! galleryButtons.length || ! window.HTMLDialogElement) return;

    const dialog = document.createElement('dialog');
    dialog.className = 'image-lightbox';
    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'lightbox-close';
    closeButton.setAttribute('aria-label', 'Close image');
    closeButton.textContent = '×';
    const image = document.createElement('img');
    dialog.append(closeButton, image);
    document.body.append(dialog);
    closeButton.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    galleryButtons.forEach((button) => button.addEventListener('click', () => {
        image.src = button.dataset.lightboxSrc || '';
        image.alt = button.dataset.lightboxAlt || '';
        dialog.showModal();
    }));
})();
