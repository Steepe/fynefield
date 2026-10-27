(() => {
    const motion = ! window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const clamp = (value) => Math.max(0, Math.min(1, value));
    const easeOutCubic = (p) => 1 - Math.pow(1 - p, 3);

    // One rAF-throttled scroll/resize loop shared by every scroll-driven effect.
    const scrollHandlers = [];
    let scrollQueued = false;
    const runScrollHandlers = () => { scrollQueued = false; scrollHandlers.forEach((handler) => handler()); };
    const onScroll = (handler) => { scrollHandlers.push(handler); handler(); };
    const queueScroll = () => { if (! scrollQueued) { scrollQueued = true; requestAnimationFrame(runScrollHandlers); } };
    window.addEventListener('scroll', queueScroll, { passive: true });
    window.addEventListener('resize', queueScroll);

    const tween = (duration, ease, step) => {
        if (! motion || ! duration) { step(1); return; }
        const start = performance.now();
        const frame = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            step(ease(progress));
            if (progress < 1) requestAnimationFrame(frame);
        };
        requestAnimationFrame(frame);
    };

    // Header: transparent over the hero, solid once the page scrolls or the menu opens.
    const header = document.querySelector('[data-site-header]');
    const navToggle = document.querySelector('[data-nav-toggle]');
    const menu = document.getElementById('site-menu');
    if (header) {
        const updateHeader = () => header.classList.toggle('is-solid', window.scrollY > 40 || (menu && ! menu.hidden));
        onScroll(updateHeader);
        navToggle?.addEventListener('click', () => {
            const open = menu.hidden;
            menu.hidden = ! open;
            navToggle.setAttribute('aria-expanded', String(open));
            navToggle.textContent = open ? 'Close' : 'Menu';
            updateHeader();
        });
        window.matchMedia('(min-width: 960px)').addEventListener('change', (event) => {
            if (! event.matches || ! menu || menu.hidden) return;
            menu.hidden = true;
            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.textContent = 'Menu';
            updateHeader();
        });
    }

    // Home hero: the image settles and the copy rises in once the page has loaded.
    const hero = document.querySelector('[data-hero]');
    if (hero) setTimeout(() => hero.classList.add('is-loaded'), motion ? 60 : 0);

    // Stats: figures count up the first time the strip comes into view.
    const stats = document.querySelector('[data-stats]');
    if (stats) {
        const counters = stats.querySelectorAll('[data-count-to]');
        const render = (t) => counters.forEach((counter) => {
            counter.textContent = Math.round(Number(counter.dataset.countTo) * t) + (counter.dataset.countSuffix || '');
        });
        let counted = false;
        if (motion) render(0);
        onScroll(() => {
            if (counted || stats.getBoundingClientRect().top >= window.innerHeight * 0.95) return;
            counted = true;
            tween(1600, easeOutCubic, render);
        });
    }

    // Home: "What we do" tabs advance on their own every seven seconds; choosing one restarts its timer.
    const serviceTabs = document.querySelector('[data-service-tabs]');
    if (serviceTabs) {
        const tabs = Array.from(serviceTabs.querySelectorAll('[data-service-tab]'));
        const fills = serviceTabs.querySelectorAll('[data-service-tab-fill]');
        const images = serviceTabs.querySelectorAll('[data-service-tab-image]');
        const panels = serviceTabs.querySelectorAll('[data-service-tab-panel]');
        const duration = 7000;
        let active = 0;
        let started = performance.now();
        let paused = ! motion;
        const select = (index) => {
            active = index;
            started = performance.now();
            tabs.forEach((tab, i) => {
                tab.classList.toggle('is-active', i === index);
                tab.setAttribute('aria-selected', String(i === index));
                fills[i].style.width = '0%';
            });
            images.forEach((image, i) => image.classList.toggle('is-active', i === index));
            panels.forEach((panel, i) => {
                panel.hidden = i !== index;
                if (i !== index || ! motion) return;
                panel.classList.add('is-entering');
                setTimeout(() => panel.classList.remove('is-entering'), 30);
            });
        };
        tabs.forEach((tab, index) => tab.addEventListener('click', () => select(index)));
        serviceTabs.addEventListener('focusin', () => { paused = true; });
        serviceTabs.addEventListener('focusout', () => { paused = ! motion; started = performance.now(); });
        const loop = (now) => {
            if (! paused) {
                const progress = (now - started) / duration;
                if (progress >= 1) select((active + 1) % tabs.length);
                else fills[active].style.width = `${(progress * 100).toFixed(1)}%`;
            }
            requestAnimationFrame(loop);
        };
        requestAnimationFrame(loop);
    }

    // Network: hovering or focusing a state highlights its route on the map.
    const networkMap = document.querySelector('.ff-network-map iframe');
    document.querySelectorAll('[data-network-state]').forEach((item) => {
        const highlight = (state) => {
            document.querySelectorAll('[data-network-state]').forEach((other) => other.classList.toggle('is-active', other.dataset.networkState === state));
            networkMap?.contentWindow?.postMessage({ highlight: state }, window.location.origin);
        };
        ['mouseenter', 'focus'].forEach((type) => item.addEventListener(type, () => highlight(item.dataset.networkState)));
        ['mouseleave', 'blur'].forEach((type) => item.addEventListener(type, () => highlight(null)));
    });

    // Services: the finder recommends a service for the chosen need.
    const finder = document.querySelector('[data-service-finder]');
    if (finder) {
        const title = finder.querySelector('[data-service-result-title]');
        const copy = finder.querySelector('[data-service-result-copy]');
        const link = finder.querySelector('[data-service-result-link]');
        const short = finder.querySelector('[data-service-result-short]');
        const choices = finder.querySelectorAll('[data-service-choice]');
        choices.forEach((choice) => choice.addEventListener('click', () => {
            choices.forEach((button) => {
                const selected = button === choice;
                button.classList.toggle('is-selected', selected);
                button.setAttribute('aria-pressed', String(selected));
                button.querySelector('[data-choice-mark]').textContent = selected ? '●' : '→';
            });
            title.textContent = choice.dataset.title;
            copy.textContent = choice.dataset.summary;
            short.textContent = choice.dataset.short;
            link.href = choice.dataset.href;
        }));
    }

    // Leadership: profile drawer with previous/next and keyboard support.
    const profiles = document.querySelector('[data-profiles]');
    if (profiles) {
        const drawers = Array.from(profiles.querySelectorAll('[data-profile]'));
        const backdrop = profiles.querySelector('.ff-drawer-backdrop');
        let current = -1;
        let opener = null;
        const show = (index) => {
            current = (index + drawers.length) % drawers.length;
            drawers.forEach((drawer, i) => { drawer.hidden = i !== current; });
            backdrop.hidden = false;
            document.body.style.overflow = 'hidden';
            drawers[current].scrollTop = 0;
            drawers[current].focus();
        };
        const close = () => {
            if (current < 0) return;
            current = -1;
            drawers.forEach((drawer) => { drawer.hidden = true; });
            backdrop.hidden = true;
            document.body.style.overflow = '';
            opener?.focus();
        };
        profiles.querySelectorAll('[data-profile-open]').forEach((button) => button.addEventListener('click', () => {
            opener = button;
            show(Number(button.dataset.profileOpen));
        }));
        profiles.querySelectorAll('[data-profile-step]').forEach((button) => button.addEventListener('click', () => show(current + Number(button.dataset.profileStep))));
        profiles.querySelectorAll('[data-profile-close]').forEach((button) => button.addEventListener('click', close));
        document.addEventListener('keydown', (event) => {
            if (current < 0) return;
            if (event.key === 'Escape') close();
            if (event.key === 'ArrowRight') show(current + 1);
            if (event.key === 'ArrowLeft') show(current - 1);
        });
    }

    // News: year filter and newsletter confirmation.
    const newsFilter = document.querySelector('[data-news-filter]');
    if (newsFilter) {
        const buttons = newsFilter.querySelectorAll('[data-year]');
        const rows = newsFilter.querySelectorAll('[data-news-year]');
        const empty = newsFilter.querySelector('[data-news-empty]');
        const emptyYear = newsFilter.querySelector('[data-news-empty-year]');
        buttons.forEach((button) => button.addEventListener('click', () => {
            const year = button.dataset.year;
            buttons.forEach((item) => {
                item.classList.toggle('is-selected', item === button);
                item.setAttribute('aria-pressed', String(item === button));
            });
            let visible = 0;
            rows.forEach((row) => {
                row.hidden = year !== 'All' && row.dataset.newsYear !== year;
                if (! row.hidden) visible++;
            });
            empty.hidden = visible > 0;
            emptyYear.textContent = year === 'All' ? '' : ` in ${year}`;
        }));
    }
    document.querySelector('[data-newsletter]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const done = document.createElement('p');
        done.className = 'ff-newsletter-done';
        done.setAttribute('role', 'status');
        done.textContent = "✓ You're subscribed. Thank you.";
        event.currentTarget.replaceWith(done);
    });

    // Article: reading progress and copy link.
    const readingBody = document.querySelector('[data-reading-body]');
    const readingProgress = document.querySelector('[data-reading-progress]');
    if (readingBody && readingProgress) {
        onScroll(() => {
            const bounds = readingBody.getBoundingClientRect();
            const vh = window.innerHeight;
            readingProgress.style.width = `${(clamp((vh - bounds.top) / (bounds.height + vh * 0.2)) * 100).toFixed(1)}%`;
        });
    }
    document.querySelector('[data-copy-link]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        try { await navigator.clipboard.writeText(window.location.href); } catch (_) { /* Clipboard access is optional. */ }
        button.textContent = 'Copied ✓';
    });

    // Inquiry forms: topic chips adapt the subject hint and message label (contact page)
    // or the hidden subject and message placeholder (home page quick form).
    document.querySelectorAll('[data-contact-form]').forEach((form) => {
        const topics = form.querySelectorAll('[data-contact-topic]');
        const subject = form.querySelector('[data-contact-subject]');
        const subjectValue = form.querySelector('[data-contact-subject-value]');
        const messageLabel = form.querySelector('[data-contact-message-label]');
        const message = form.querySelector('[data-contact-message]');
        topics.forEach((button) => button.addEventListener('click', () => {
            topics.forEach((item) => {
                item.classList.toggle('is-selected', item === button);
                item.setAttribute('aria-pressed', String(item === button));
            });
            if (subject) subject.placeholder = button.dataset.hint;
            if (subjectValue) subjectValue.value = `${button.textContent.trim()} inquiry`;
            if (messageLabel) messageLabel.textContent = button.dataset.message;
            if (message) message.placeholder = button.dataset.message;
        }));
        // A rejected submission reloads the page at the top; bring the form and its errors back into view.
        if (form.querySelector('[role="alert"]') && form.closest('#inquiry')) form.closest('#inquiry').scrollIntoView();
    });
    const faqButtons = document.querySelectorAll('[data-faq-toggle]');
    faqButtons.forEach((button) => button.addEventListener('click', () => {
        const opening = button.getAttribute('aria-expanded') !== 'true';
        faqButtons.forEach((item) => {
            const open = item === button && opening;
            item.setAttribute('aria-expanded', String(open));
            item.querySelector('span').textContent = open ? '−' : '+';
            document.getElementById(item.getAttribute('aria-controls')).hidden = ! open;
        });
    }));

    // Page-builder testimonial carousel.
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

    // Gallery lightbox.
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
