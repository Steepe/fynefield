/* Fynefield Studio — the page editor's live preview frame (ported from the design). */
(() => {
    const { html, css, BOOT } = window.FS;

    const linkLabel = (link, settings) => (link.label || '').trim() || (link.link === 'tel' ? settings.contact.phone : link.link === 'mailto' ? settings.contact.email : '');

    function PreviewFrame({ app, page, sel, device }) {
        const st = app.state;
        const T = st.theme, settings = st.settings, nav = st.nav, coll = st.colls;
        const ST = BOOT.sectionTypes;
        const mob = device === 'Mobile';
        const r = T.radius, btnR = T.button === 'Pill' ? 999 : T.button === 'Rounded' ? 6 : 0;
        const shadow = T.shadow === 'None' ? 'none' : T.shadow === 'Strong' ? '0 14px 34px rgba(16,38,59,.18)' : '0 8px 22px rgba(16,38,59,.08)';
        const scale = (T.headingScale || 100) / 100;
        const s = {
            accentText: `color:${T.accent}`, accentSmall: `font-size:11px;font-weight:600;color:${T.accent}`,
            eyebrowStyle: `font-size:11px;font-weight:600;color:${T.accent};margin-bottom:6px`,
            h1Style: `font-size:${Math.round((mob ? 22 : 28) * scale)}px;line-height:1.15;font-weight:${T.headingWeight};letter-spacing:-.01em`,
            h2Style: `font-size:${Math.round(19 * scale)}px;line-height:1.25;font-weight:${T.headingWeight}`,
            h2DarkStyle: `font-size:${Math.round(18 * scale)}px;line-height:1.25;font-weight:${T.headingWeight};color:#fff`,
            btnAccent: `background:${T.accent};color:${T.primary};font-size:11px;font-weight:600;padding:8px 14px;border-radius:${btnR}px`,
            btnGhost: `border:1px solid rgba(255,255,255,.45);color:#fff;font-size:11px;font-weight:600;padding:8px 14px;border-radius:${btnR}px`,
            btnPrimary: `background:${T.primary};color:#fff;font-size:11px;font-weight:600;padding:8px 14px;border-radius:${btnR}px`,
            cardStyle: `border:1px solid #e3e7ea;border-radius:${r}px;padding:12px;background:#fff`,
            surfaceCard: `background:${T.surface};border-radius:${r}px;padding:12px`,
            surfaceBand: `display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:22px 28px;background:${T.surface}`,
            chipOn: `font-size:10px;font-weight:600;padding:3px 9px;border-radius:999px;background:${T.primary};color:#fff`, chipOff: 'font-size:10px;font-weight:600;padding:3px 9px;border-radius:999px;border:1px solid #d3d9de',
            phImg: `width:100%;aspect-ratio:16/10;border-radius:${r}px;background:repeating-linear-gradient(45deg,#edf0f2 0 8px,#f6f7f9 8px 16px)`,
            phImgSm: 'width:100%;aspect-ratio:16/9;border-radius:6px;margin-bottom:8px;background:repeating-linear-gradient(45deg,#edf0f2 0 8px,#f6f7f9 8px 16px)',
            imgStyle: `width:100%;aspect-ratio:16/10;object-fit:cover;display:block;border-radius:${r}px`,
        };
        const frameStyle = `width:${{ Desktop: '100%', Tablet: '768px', Mobile: '390px' }[device]};max-width:1040px;background:#fff;box-shadow:0 10px 30px rgba(16,38,59,.12);align-self:flex-start;transition:width .25s ease;font-family:'${T.font}',sans-serif;color:${T.ink};border-radius:6px;overflow:hidden`;
        const pvNav = nav.header.filter((h) => h.show !== 'Hidden').map((h) => linkLabel(h, settings));
        const secs = page.sections;
        const visibleLoc = coll.locations.filter((l) => l.visible);
        const topics = settings.forms.topics;

        const block = (x, i) => {
            const t = x.type;
            const label = (ST[t] || {}).label;
            const selected = i === sel;
            const wrap = `position:relative;cursor:pointer;outline:${selected ? '2px solid #e59b23' : 'none'};outline-offset:-2px;opacity:${x.hidden ? .35 : 1};background:${x.bg === 'Soft' ? T.surface : x.bg === 'Navy' ? T.primary : 'transparent'};padding:${x.spacing === 'Spacious' ? '14px 0' : '0'}`;
            const stop = (fn) => (e) => { e.stopPropagation(); fn(); };
            const toolbar = selected && html`<div style=${css('position:absolute;top:6px;right:6px;z-index:5;display:flex;background:#e59b23;color:#10263b;font-size:12px;font-weight:700;border-radius:6px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.15)')}>
                <span style=${css('padding:6px 10px')}>${label}</span>
                <button onClick=${stop(() => app.moveSection(i, -1))} title="Move up" style=${css('background:transparent;border:0;border-left:1px solid rgba(16,38,59,.2);padding:6px 9px;cursor:pointer;font-weight:700')}>↑</button>
                <button onClick=${stop(() => app.moveSection(i, 1))} title="Move down" style=${css('background:transparent;border:0;border-left:1px solid rgba(16,38,59,.2);padding:6px 9px;cursor:pointer;font-weight:700')}>↓</button>
                <button onClick=${stop(() => app.duplicateSection(i))} title="Duplicate" style=${css('background:transparent;border:0;border-left:1px solid rgba(16,38,59,.2);padding:6px 9px;cursor:pointer;font-weight:700')}>⧉</button>
                <button onClick=${stop(() => app.toggleSectionHidden(i))} title="Show / hide" style=${css('background:transparent;border:0;border-left:1px solid rgba(16,38,59,.2);padding:6px 9px;cursor:pointer;font-weight:700')}>${x.hidden ? '◉' : '◌'}</button>
                <button onClick=${stop(() => app.removeSection(i))} title="Delete" style=${css('background:transparent;border:0;border-left:1px solid rgba(16,38,59,.2);padding:6px 9px;cursor:pointer;font-weight:700')}>✕</button>
            </div>`;
            let body = null;
            if (t === 'hero') {
                const heroBox = `position:relative;overflow:hidden;background:${T.primary};color:#fff;padding:${x.height === 'Tall' ? '64px' : x.height === 'Compact' ? '32px' : '48px'} 28px`;
                const heroImg = `position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:${{ Strong: .3, Medium: .5, Light: .75, None: 1 }[x.overlay] ?? .3}`;
                body = html`<div style=${css(heroBox)}>
                    ${x.image && html`<img src=${x.image} alt="" style=${css(heroImg)} />`}
                    <div style=${css('position:relative;max-width:520px')}>
                        ${x.breadcrumb && html`<div style=${css('font-size:11px;color:#b8c5d0;margin-bottom:10px')}>Home / <span style=${css(s.accentText)}>${page.title}</span></div>`}
                        ${x.eyebrow && html`<div style=${css(`font-size:11px;font-weight:600;color:${T.accent};margin-bottom:8px`)}>${x.eyebrow}</div>`}
                        <div style=${css(s.h1Style)}>${x.heading}</div>
                        ${x.body && html`<div style=${css('font-size:13px;line-height:1.6;color:#d0dae2;margin-top:10px')}>${x.body}</div>`}
                        <div style=${css('display:flex;gap:8px;margin-top:16px;flex-wrap:wrap')}>
                            ${x.primaryLabel && html`<span style=${css(s.btnAccent)}>${x.primaryLabel}</span>`}
                            ${x.secondaryLabel && html`<span style=${css(s.btnGhost)}>${x.secondaryLabel}</span>`}
                        </div>
                    </div>
                </div>`;
            } else if (t === 'stats') {
                const statsBox = x.style === 'Plain row' ? `display:grid;grid-template-columns:repeat(${mob ? 2 : 4},1fr);border-top:1px solid #e3e7ea;border-bottom:1px solid #e3e7ea` : `display:grid;grid-template-columns:repeat(${mob ? 2 : 4},1fr);background:#fff;border-radius:${r}px;box-shadow:${shadow}`;
                body = html`<div style=${css('padding:20px 28px')}><div style=${css(statsBox)}>${(x.items || []).map((it, j) => html`<div key=${j} style=${css('padding:14px 16px;border-right:1px solid #edf0f2')}><div style=${css('font-size:20px;font-weight:600')}>${it.v}</div><div style=${css('font-size:11px;color:#5b6977;margin-top:2px')}>${it.l}</div></div>`)}</div></div>`;
            } else if (t === 'tabs') {
                const first = (x.items || [])[0];
                body = html`<div style=${css('padding:28px')}>
                    <div style=${css(s.eyebrowStyle)}>${x.eyebrow}</div><div style=${css(s.h2Style)}>${x.heading}</div>
                    <div style=${css('display:flex;gap:18px;border-bottom:1px solid #e3e7ea;margin:16px 0 14px;overflow:hidden')}>${(x.items || []).map((it, j) => html`<span key=${j} style=${css(`padding:0 0 8px;font-size:12px;font-weight:600;white-space:nowrap;border-bottom:2px solid ${j === 0 ? T.accent : 'transparent'};color:${j === 0 ? T.ink : '#8a97a3'}`)}>${it.title}</span>`)}</div>
                    <div style=${css('display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:center')}>${first && first.image ? html`<img src=${first.image} alt="" style=${css(s.imgStyle)} />` : html`<div style=${css(s.phImg)}></div>`}<div style=${css('font-size:13px;line-height:1.6;color:#4c5b69')}>${first ? first.body : ''}</div></div>
                </div>`;
            } else if (t === 'split') {
                const left = x.imageSide === 'Left' && ! mob;
                body = html`<div style=${css(`padding:28px;display:grid;grid-template-columns:${mob ? '1fr' : '1fr 1fr'};gap:20px;align-items:center`)}>
                    <div style=${css(`order:${left ? 2 : 1}`)}><div style=${css(s.eyebrowStyle)}>${x.eyebrow}</div><div style=${css(s.h2Style)}>${x.heading}</div><div style=${css('font-size:13px;line-height:1.65;color:#4c5b69;margin-top:8px;white-space:pre-line')}>${x.body}</div>${x.buttonLabel && html`<span style=${css(s.btnPrimary + ';margin-top:12px;display:inline-block')}>${x.buttonLabel}</span>`}</div>
                    <div style=${css(`order:${left ? 1 : 2}`)}>${x.image ? html`<img src=${x.image} alt="" style=${css(s.imgStyle)} />` : html`<div style=${css(s.phImg)}></div>`}</div>
                </div>`;
            } else if (['cards', 'serviceList', 'team', 'picker'].includes(t)) {
                let cards = [];
                if (t === 'cards') cards = (x.items || []).map((c) => ({ title: c.title, body: c.body, cta: 'Learn more' }));
                if (t === 'serviceList') cards = coll.services.filter((sv) => sv.status === 'Published').map((sv) => ({ title: sv.title, kicker: sv.kicker, body: sv.summary, img: sv.card || sv.hero || '', facts: x.showFacts ? (sv.facts || []).slice(0, 2).map((f) => f.v).join(' · ') : '', cta: x.buttonLabel }));
                if (t === 'team') cards = coll.team.filter((m) => m.visible).map((m) => ({ title: m.name, kicker: m.role, img: m.photo, body: x.showBio ? m.bio : '', cta: x.profiles ? 'View profile' : '' }));
                if (t === 'picker') cards = (x.items || []).map((a, j) => ({ title: a.label, kicker: j === 0 ? 'Selected' : '', body: 'Recommends: ' + ((BOOT_SERVICES(app).find((o) => o.value === a.service) || {}).label || a.service) }));
                const colCount = mob ? 1 : Math.min(+x.columns || 3, device === 'Tablet' ? 2 : 4);
                body = html`<div style=${css('padding:28px')}>
                    ${x.eyebrow && html`<div style=${css(s.eyebrowStyle)}>${x.eyebrow}</div>`}
                    ${x.heading && html`<div style=${css(s.h2Style + ';margin-bottom:14px')}>${x.heading}</div>`}
                    <div style=${css(`display:grid;grid-template-columns:repeat(${colCount},minmax(0,1fr));gap:10px`)}>
                        ${cards.map((c, j) => html`<div key=${j} style=${css(s.cardStyle)}>
                            ${c.img && html`<img src=${c.img} alt="" style=${css('width:100%;aspect-ratio:1;object-fit:cover;object-position:center 20%;display:block;border-radius:6px;margin-bottom:10px')} />`}
                            ${c.kicker && html`<div style=${css(s.accentSmall)}>${c.kicker}</div>`}
                            <div style=${css('font-size:14px;font-weight:600;margin:2px 0 4px')}>${c.title}</div>
                            <div style=${css('font-size:12px;line-height:1.5;color:#5b6977')}>${c.body}</div>
                            ${c.facts && html`<div style=${css('font-size:12px;font-weight:600;margin-top:8px')}>${c.facts}</div>`}
                            ${c.cta && html`<div style=${css(s.accentSmall + ';margin-top:8px')}>${c.cta} →</div>`}
                        </div>`)}
                    </div>
                </div>`;
            } else if (t === 'map') {
                body = html`<div style=${css(`padding:26px 28px;background:${T.primary};color:#fff;display:grid;grid-template-columns:${mob ? '1fr' : '1fr 1fr'};gap:18px;align-items:center`)}>
                    <div style=${css(`order:${x.mapSide === 'Right' && ! mob ? 2 : 0}`)}><div style=${css('height:150px;border-radius:8px;background:radial-gradient(circle at 60% 55%,#1b3a57,#0a1d2f);display:grid;place-items:center;font-size:12px;color:#8fa0ad')}>Network map · ${visibleLoc.length} locations</div></div>
                    <div><div style=${css(s.eyebrowStyle)}>${x.eyebrow}</div><div style=${css(s.h2DarkStyle)}>${x.heading}</div><div style=${css('font-size:12px;line-height:1.6;color:#b8c5d0;margin:8px 0 10px')}>${x.body}</div>
                        ${x.showChips && html`<div style=${css('display:flex;flex-wrap:wrap;gap:5px')}>${visibleLoc.map((l) => html`<span key=${l.id} style=${css('border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:3px 9px;font-size:11px')}>${l.state}</span>`)}</div>`}
                    </div>
                </div>`;
            } else if (t === 'news') {
                const news = coll.news.filter((n) => n.status === 'Published' && (x.category === 'All categories' || ! x.category || n.category === x.category));
                const nCount = x.count === 'All' ? news.length : +x.count || 2;
                const grid = x.layout === 'Compact list' ? 'display:grid;grid-template-columns:1fr;gap:8px' : `display:grid;grid-template-columns:repeat(${mob ? 1 : 3},minmax(0,1fr));gap:10px`;
                body = html`<div style=${css('padding:28px')}>
                    <div style=${css('display:flex;justify-content:space-between;align-items:flex-end;gap:12px;margin-bottom:14px')}><div>${x.eyebrow && html`<div style=${css(s.eyebrowStyle)}>${x.eyebrow}</div>`}<div style=${css(s.h2Style)}>${x.heading}</div></div>${x.yearFilter && html`<div style=${css('display:flex;gap:4px')}><span style=${css(s.chipOn)}>All</span><span style=${css(s.chipOff)}>2024</span></div>`}</div>
                    <div style=${css(grid)}>${news.slice(0, nCount).map((n) => html`<div key=${n.id} style=${css(s.cardStyle)}>${n.cover ? html`<img src=${n.cover} alt="" style=${css('width:100%;aspect-ratio:16/9;object-fit:cover;display:block;border-radius:6px;margin-bottom:8px')} />` : html`<div style=${css(s.phImgSm)}></div>`}<div style=${css('font-size:11px;color:#5b6977')}>${n.dateLabel}</div><div style=${css('font-size:13px;font-weight:600;margin-top:2px;line-height:1.35')}>${n.title}</div></div>`)}</div>
                </div>`;
            } else if (t === 'cta') {
                const dark = x.style === 'Navy band';
                body = html`<div style=${css('padding:24px 28px')}><div style=${css(`display:grid;grid-template-columns:${mob ? '1fr' : '1fr 1fr'};border-radius:${r}px;overflow:hidden;box-shadow:${shadow}`)}>
                    <div style=${css(`padding:20px;background:${dark ? T.primary : T.accent};color:${dark ? '#fff' : T.primary}`)}><div style=${css('font-size:19px;font-weight:600')}>${x.heading}</div><div style=${css('font-size:12px;line-height:1.55;margin-top:6px')}>${x.body}</div></div>
                    <div style=${css('padding:20px;background:#fff;display:flex;flex-direction:column;justify-content:center;gap:8px')}><span style=${css(s.btnPrimary + ';align-self:flex-start')}>${x.button}</span>${x.showContact && html`<span style=${css('font-size:11px;font-weight:600;color:#10263b')}>${settings.contact.phone} · ${settings.contact.email}</span>`}</div>
                </div></div>`;
            } else if (t === 'form') {
                const fields = (x.fields || '').split(',').map((f) => f.trim()).filter(Boolean);
                body = html`<div style=${css('padding:28px')}><div style=${css(s.cardStyle + ';padding:18px')}>
                    <div style=${css('font-size:17px;font-weight:600;margin-bottom:10px')}>${x.heading || 'Inquiry form'}</div>
                    <div style=${css('display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px')}>${topics.map((tp, j) => html`<span key=${j} style=${css(`font-size:10px;font-weight:600;padding:4px 9px;border-radius:999px;border:1px solid ${j === 0 ? T.primary : '#d3d9de'};background:${j === 0 ? T.primary : '#fff'};color:${j === 0 ? '#fff' : T.ink}`)}>${tp.label}</span>`)}</div>
                    <div style=${css('display:grid;grid-template-columns:1fr 1fr;gap:8px')}>${fields.map((ff) => html`<div key=${ff} style=${css('border:1px solid #d3d9de;border-radius:5px;padding:7px 9px;font-size:11px;color:#8a97a3')}>${ff}</div>`)}</div>
                    ${x.consent && html`<div style=${css('font-size:10px;color:#5b6977;margin-top:8px;line-height:1.5')}>☐ ${settings.forms.consent}</div>`}
                    <span style=${css(s.btnPrimary + ';display:inline-block;margin-top:10px')}>${x.button}</span>
                </div></div>`;
            } else if (t === 'faq') {
                body = html`<div style=${css('padding:28px')}><div style=${css(s.h2Style + ';font-size:17px;margin-bottom:8px')}>${x.heading}</div>${(x.items || []).map((q, j) => { const open = j === 0 && x.openFirst; return html`<div key=${j} style=${css('border-bottom:1px solid #e3e7ea;padding:9px 0')}><div style=${css('display:flex;justify-content:space-between;font-size:13px;font-weight:600')}>${q.q}<span style=${css(s.accentText)}>${open ? '−' : '+'}</span></div>${open && html`<div style=${css('font-size:12px;color:#5b6977;margin-top:5px;line-height:1.5')}>${q.a}</div>`}</div>`; })}</div>`;
            } else if (t === 'newsletter') {
                body = html`<div style=${css(s.surfaceBand)}><div><div style=${css('font-size:16px;font-weight:600')}>${x.heading}</div><div style=${css('font-size:12px;color:#4c5b69')}>${x.body}</div></div><div style=${css('display:flex;gap:6px')}><span style=${css('border:1px solid #d3d9de;background:#fff;border-radius:999px;padding:7px 12px;font-size:11px;color:#8a97a3;width:140px')}>Your work email</span><span style=${css(s.btnPrimary)}>${x.button}</span></div></div>`;
            } else if (t === 'contactCards') {
                const items = [x.showPhone && { l: 'Call', v: settings.contact.phone }, x.showEmail && { l: 'Email', v: settings.contact.email }, x.showOffice && { l: x.officeLabel || 'Office', v: settings.contact.address }].filter(Boolean);
                body = html`<div style=${css('padding:20px 28px;display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px')}>${items.map((c) => html`<div key=${c.l} style=${css(s.surfaceCard)}><div style=${css('font-size:11px;color:#5b6977')}>${c.l}</div><div style=${css('font-size:12px;font-weight:600;margin-top:3px;line-height:1.4')}>${c.v}</div></div>`)}</div>`;
            } else if (t === 'text') {
                body = html`<div style=${css(`padding:28px;max-width:${x.width === 'Wide' ? '100%' : '620px'}`)}><div style=${css(s.h2Style + ';font-size:17px')}>${x.heading}</div><div style=${css('font-size:13px;line-height:1.7;color:#33424f;margin-top:8px;white-space:pre-line')}>${x.body}</div></div>`;
            } else if (t === 'gallery') {
                body = html`<div style=${css('padding:24px 28px')}>${x.heading && html`<div style=${css(s.h2Style + ';font-size:17px;margin-bottom:10px')}>${x.heading}</div>`}<div style=${css('display:grid;grid-template-columns:repeat(3,1fr);gap:8px')}>${[x.image1, x.image2, x.image3].map((src, j) => html`<div key=${j}>${src ? html`<img src=${src} alt="" style=${css('width:100%;aspect-ratio:4/3;object-fit:cover;display:block;border-radius:6px')} />` : html`<div style=${css(s.phImgSm + ';margin:0')}></div>`}</div>`)}</div></div>`;
            }
            return html`<div key=${x.id} onClick=${() => app.setState({ sel: i, tab: 'Section' })} style=${css(wrap)}>${toolbar}${body}</div>`;
        };

        return html`<div style=${css(frameStyle)}>
            <div style=${css(`display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 24px;background:${T.primary};color:#fff`)}>
                <div style=${css('background:#fff;border-radius:3px;padding:4px 7px;display:flex')}><img src=${settings.general.logo} alt="" style=${css('height:18px;width:auto;display:block')} /></div>
                ${! mob && html`<div style=${css('display:flex;gap:16px;font-size:12px;font-weight:500')}>${pvNav.map((n, j) => html`<span key=${j}>${n}</span>`)}</div>`}
                ${nav.cta.show !== 'Hidden' && html`<span style=${css(`background:#fff;color:${T.primary};font-size:11px;font-weight:600;padding:6px 12px;border-radius:${btnR}px`)}>${nav.cta.label}</span>`}
            </div>
            ${secs.map(block)}
            <div style=${css('position:relative;overflow:hidden;background:#0a1d2f;color:#8fa0ad;padding:26px 24px 16px')}>
                ${nav.showWordmark && html`<div style=${css('position:absolute;left:0;right:0;bottom:-10px;text-align:center;font-size:84px;font-weight:700;letter-spacing:-.04em;color:rgba(255,255,255,.04);white-space:nowrap;overflow:hidden;pointer-events:none')}>${nav.wordmark}</div>`}
                <div style=${css('position:relative;display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:16px;font-size:11px')}>
                    ${nav.showAddress && html`<div><div style=${css('color:#fff;font-weight:600;margin-bottom:5px')}>Head office</div><div style=${css('line-height:1.5')}>${settings.contact.address}</div></div>`}
                    ${nav.columns.map((c, j) => html`<div key=${j}><div style=${css('color:#fff;font-weight:600;margin-bottom:5px')}>${c.heading}</div>${(c.links || []).map((l, k) => html`<div key=${k} style=${css('margin-bottom:3px')}>${linkLabel(l, settings)}</div>`)}</div>`)}
                </div>
                <div style=${css('position:relative;display:flex;justify-content:space-between;gap:10px;margin-top:22px;padding-top:10px;border-top:1px solid rgba(255,255,255,.1);font-size:10px')}><span>${String(nav.copyright || '').replace('{year}', new Date().getFullYear())}</span><span style=${css('display:flex;gap:10px')}>${nav.legal.map((l, j) => html`<span key=${j}>${linkLabel(l, settings)}</span>`)}</span></div>
            </div>
        </div>`;
    }

    const BOOT_SERVICES = (app) => app.state.serviceOptions;

    Object.assign(window.FS, { PreviewFrame, linkLabel });
})();
