/* Fynefield Studio — every screen, ported from the "Fynefield CMS" Claude Design export. */
(() => {
    const { html, css, BOOT, Field, pill, stPill, statusMeta, PreviewFrame, linkLabel } = window.FS;
    const F = (f, key) => html`<${Field} key=${key || f.label} f=${f} />`;
    const btnLight = 'background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 13px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b';
    const btnDark = 'background:#10263b;color:#fff;border:0;border-radius:6px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer';
    const header = 'display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 28px;border-bottom:1px solid #e3e7ea;background:#fff;flex-wrap:wrap';
    const words = ['Nothing', 'One thing', 'Two things', 'Three things'];

    // ---------------------------------------------------------------- sidebar

    function Sidebar({ app }) {
        const st = app.state, sc = st.screen;
        const newCount = st.inquiries.filter((q) => q.status === 'new').length;
        const navBtn = (label, screen, perm, badge) => {
            if (perm && ! app.can(perm)) return null;
            const on = sc === screen || (screen === 'pages' && sc === 'editor');
            return html`<button key=${screen} onClick=${() => app.go(screen)} style=${css(`display:flex;justify-content:space-between;align-items:center;width:100%;text-align:left;border:0;border-radius:6px;padding:8px 10px;font-size:14px;cursor:pointer;background:${on ? '#1d3e5e' : 'transparent'};color:${on ? '#fff' : '#b8c5d0'};font-weight:${on ? 600 : 400}`)}><span>${label}</span>${badge ? html`<span style=${css('background:#e59b23;color:#10263b;font-size:11px;font-weight:700;padding:1px 7px;border-radius:9px')}>${badge}</span>` : null}</button>`;
        };
        const groups = [
            { label: 'Workspace', items: [navBtn('Today', 'today'), navBtn('Inbox', 'inbox', 'inquiries', newCount)] },
            { label: 'Content', items: [navBtn('Pages', 'pages', 'edit_pages'), navBtn('News', 'news', 'write_news'), navBtn('Services', 'services', 'edit_pages'), navBtn('Team', 'team', 'edit_pages'), navBtn('Locations', 'locations', 'edit_pages'), navBtn('Media', 'media', 'manage_media')] },
            { label: 'Design', items: [navBtn('Navigation', 'nav', 'nav_theme'), navBtn('Theme', 'theme', 'nav_theme')] },
            { label: 'Admin', items: [navBtn('Settings', 'settings', 'settings'), navBtn('Users & roles', 'users', 'manage_users')] },
        ].map((g) => ({ ...g, items: g.items.filter(Boolean) })).filter((g) => g.items.length);
        const initials = BOOT.me.name.split(/[\s.]/).map((w) => w[0]).join('').slice(0, 2).toUpperCase();
        return html`<aside style=${css('width:236px;flex-shrink:0;background:#10263b;color:#b8c5d0;display:flex;flex-direction:column;padding:18px 12px;overflow-y:auto')}>
            <div style=${css('display:flex;align-items:center;gap:10px;padding:2px 8px 20px')}>
                <div style=${css('background:#fff;border-radius:6px;padding:5px 8px;display:flex')}><img src=${st.settings.general.logo} alt="Fynefield" style=${css('height:24px;width:auto;display:block')} /></div>
                <span style=${css('font-size:11px;font-weight:700;letter-spacing:.16em;color:#e59b23')}>STUDIO</span>
            </div>
            <button onClick=${() => app.setState({ palette: true, query: '' })} style=${css('display:flex;align-items:center;justify-content:space-between;gap:8px;background:#183652;border:1px solid #23466a;border-radius:8px;color:#9fb0be;padding:9px 11px;font-size:13px;cursor:pointer;margin-bottom:18px')}>Search or jump to…<span style=${css('font-size:11px;border:1px solid #2e5378;border-radius:4px;padding:1px 6px;color:#c9d4dd')}>⌘K</span></button>
            ${groups.map((g) => html`<div key=${g.label} style=${css('margin-bottom:14px')}>
                <div style=${css('font-size:11px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#6d8396;padding:0 10px 6px')}>${g.label}</div>
                <div style=${css('display:grid;gap:1px')}>${g.items}</div>
            </div>`)}
            <div style=${css('margin-top:auto;display:flex;align-items:center;gap:10px;padding:14px 8px 2px;border-top:1px solid #23466a')}>
                <a href=${BOOT.profile} title="Your account" style=${css('width:32px;height:32px;border-radius:50%;background:#e59b23;color:#10263b;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0')}>${initials}</a>
                <div style=${css('flex:1;min-width:0')}><div style=${css('color:#fff;font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap')}>${BOOT.me.name}</div><div style=${css('font-size:12px')}>${BOOT.me.role}</div></div>
                <form method="post" action=${BOOT.logout}><input type="hidden" name="csrf_test_name" value=${window.FS.api.csrf} /><button type="submit" title="Sign out" style=${css('background:transparent;border:0;color:#8fa0ad;cursor:pointer;font-size:12px;padding:4px')}>Sign out</button></form>
            </div>
        </aside>`;
    }

    // ------------------------------------------------------------------ today

    function Today({ app }) {
        const st = app.state;
        const newOnes = st.inquiries.filter((q) => q.status === 'new');
        const oldest = newOnes[newOnes.length - 1];
        const missingAlt = st.media.filter((m) => ! m.alt);
        const livePages = st.pages.filter((p) => p.live || p.status === 'Published');
        const noDesc = livePages.filter((p) => ! p.seoDesc).length;
        const broken = BOOT.health.brokenLinks;
        const reviewPage = st.pages.find((p) => p.status === 'In review');
        const reviewNews = st.colls.news.find((n) => n.status === 'In review');
        const score = Math.max(60, 100 - missingAlt.length * 3 - noDesc * 4 - broken * 5);
        const hour = new Date().getHours();
        const attention = [
            app.can('inquiries') && { kicker: 'Inbox', color: '#e59b23', title: newOnes.length ? `${newOnes.length} new ${newOnes.length === 1 ? 'inquiry' : 'inquiries'}` : 'Inbox is clear', body: oldest ? `Oldest is ${oldest.ago.replace(' ago', '')} old — a ${oldest.topic.toLowerCase()} request from ${oldest.company !== 'Independent' ? oldest.company : oldest.name}.` : 'New website inquiries will appear here.', cta: newOnes.length ? 'Reply now' : 'Open inbox', go: () => app.go('inbox', oldest ? { inq: oldest.id } : {}), needs: newOnes.length > 0 },
            { kicker: 'Review', color: '#6a3f96', title: reviewNews ? `“${reviewNews.title}” awaits approval` : reviewPage ? `“${reviewPage.title}” awaits approval` : 'Nothing waiting for review', body: reviewNews ? 'An editor submitted a news article for review.' : reviewPage ? 'A page was submitted for review.' : 'Drafts sent for review will appear here.', cta: reviewNews ? 'Review article' : reviewPage ? 'Review page' : 'Open pages', go: () => (reviewNews ? app.go('news', { itemId: reviewNews.id }) : reviewPage ? app.go('editor', { pageId: reviewPage.id, sel: 0, tab: 'Section' }) : app.go('pages')), needs: !! (reviewNews || reviewPage) },
            { kicker: 'Health', color: '#1e6c8b', title: `${missingAlt.length} images missing alt text`, body: 'Affects accessibility and search ranking.', cta: 'Fix in Media', go: () => app.go('media', { mediaSel: (missingAlt[0] || {}).id }), needs: missingAlt.length > 0 },
        ].filter(Boolean);
        const needs = attention.filter((a) => a.needs).length;
        const quick = [
            { label: 'Edit the homepage', hint: 'Hero, figures, sections', go: () => app.go('editor', { pageId: (st.pages.find((p) => p.slug === '/') || st.pages[0]).id, sel: 0, tab: 'Section' }) },
            { label: 'Change header menu', hint: 'Links, button, footer', go: () => app.go('nav') },
            { label: 'Brand colours & fonts', hint: 'Theme for every page', go: () => app.go('theme') },
            { label: 'Contact details', hint: 'Phone, email, address', go: () => app.go('settings', { setTab: 'contact' }) },
            { label: 'Inquiry routing', hint: 'Topics & who receives them', go: () => app.go('settings', { setTab: 'forms' }) },
            { label: 'Map locations', hint: 'States & supply routes', go: () => app.go('locations') },
        ];
        const health = [
            { label: 'Broken links', value: String(broken), style: `color:${broken ? '#c97d0f' : '#2f8a57'};font-weight:600`, go: () => app.go('nav') },
            { label: 'Images missing alt text', value: String(missingAlt.length), style: `color:${missingAlt.length ? '#c97d0f' : '#2f8a57'};font-weight:600`, go: () => app.go('media') },
            { label: 'Pages without meta description', value: String(noDesc), style: `color:${noDesc ? '#c97d0f' : '#2f8a57'};font-weight:600`, go: () => app.go('pages') },
            { label: 'Team bios still to write', value: String(st.colls.team.filter((t) => ! t.fullBio).length), style: 'color:#c97d0f;font-weight:600', go: () => app.go('team') },
        ];
        return html`<div style=${css('flex:1;overflow:auto')}>
            <header style=${css('height:60px;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 32px;border-bottom:1px solid #e3e7ea;background:#fff')}>
                <span style=${css('color:#5b6977')}>${BOOT.today}</span>
                <div style=${css('display:flex;gap:8px')}>
                    ${app.can('edit_pages') && html`<button onClick=${() => app.newPage()} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 13px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>+ New page</button>`}
                    ${app.can('write_news') && html`<button onClick=${() => { app.go('news'); setTimeout(() => app.newItem('news'), 0); }} style=${css('background:#10263b;color:#fff;border:0;border-radius:6px;padding:8px 13px;font-size:13px;font-weight:600;cursor:pointer')}>+ Write news</button>`}
                    <a href=${BOOT.siteUrl + '/'} target="_blank" rel="noopener" style=${css('display:flex;align-items:center;padding:8px 6px;font-size:13px;font-weight:600')}>View site ↗</a>
                </div>
            </header>
            <div style=${css('padding:32px;max-width:1240px')}>
                <h1 style=${css('font-size:26px;font-weight:600;letter-spacing:-.01em;margin:0 0 4px')}>${hour < 12 ? 'Good morning.' : hour < 17 ? 'Good afternoon.' : 'Good evening.'}</h1>
                <p style=${css('color:#5b6977;font-size:15px;margin:0 0 24px')}>${needs ? `${words[needs] || needs + ' things'} ${needs === 1 ? 'needs' : 'need'} you today. Everything else is running smoothly.` : 'Nothing needs you today. Everything is running smoothly.'}</p>
                <div style=${css('display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-bottom:24px')}>
                    ${attention.map((a) => html`<div key=${a.kicker} style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;padding:18px;display:flex;flex-direction:column;gap:8px')}>
                        <div style=${css('display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:#5b6977')}><span style=${css(`width:8px;height:8px;border-radius:50%;background:${a.color}`)}></span>${a.kicker}</div>
                        <div style=${css('font-size:17px;font-weight:600;line-height:1.3')}>${a.title}</div>
                        <div style=${css('color:#5b6977;line-height:1.5;font-size:13px')}>${a.body}</div>
                        <button onClick=${a.go} style=${css('align-self:flex-start;margin-top:auto;background:transparent;border:0;padding:2px 0;font-weight:600;cursor:pointer;font-size:13px;color:#c97d0f')}>${a.cta} →</button>
                    </div>`)}
                </div>
                <div style=${css('font-size:13px;font-weight:600;color:#5b6977;margin-bottom:10px')}>Customise your site</div>
                <div style=${css('display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:24px')}>
                    ${quick.map((q) => html`<button key=${q.label} onClick=${q.go} className="sf-hover-quick" style=${css('display:flex;flex-direction:column;gap:4px;text-align:left;background:#fff;border:1px solid #e3e7ea;border-radius:10px;padding:14px 16px;cursor:pointer;color:#10263b')}><span style=${css('font-weight:600;font-size:14px')}>${q.label}</span><span style=${css('font-size:12px;color:#5b6977')}>${q.hint}</span></button>`)}
                </div>
                <div style=${css('display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));gap:20px')}>
                    ${app.can('inquiries') && html`<section style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;overflow:hidden')}>
                        <div style=${css('display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid #edf0f2')}><h2 style=${css('font-size:14px;margin:0')}>Latest inquiries</h2><button onClick=${() => app.go('inbox')} style=${css('background:transparent;border:0;font-weight:600;cursor:pointer;font-size:13px;color:#c97d0f')}>Open inbox →</button></div>
                        ${st.inquiries.slice(0, 5).map((q) => html`<button key=${q.id} onClick=${() => app.go('inbox', { inq: q.id })} className="sf-hover-row" style=${css('display:grid;grid-template-columns:minmax(0,1fr) auto;gap:3px 14px;width:100%;text-align:left;background:transparent;border:0;border-bottom:1px solid #edf0f2;padding:13px 18px;cursor:pointer;color:#10263b')}>
                            <span style=${css('font-weight:600')}>${q.name} <span style=${css('font-weight:400;color:#5b6977')}>· ${q.company}</span></span>
                            <span style=${css('font-size:12px;color:#5b6977')}>${q.ago}</span>
                            <span style=${css('color:#5b6977;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px')}>${q.subject}</span>
                            <span style=${css(pill(statusMeta[q.status][1], statusMeta[q.status][2]) + ';justify-self:end')}>${statusMeta[q.status][0]}</span>
                        </button>`)}
                        ${! st.inquiries.length && html`<div style=${css('padding:24px 18px;color:#5b6977;font-size:13px')}>No inquiries yet.</div>`}
                    </section>`}
                    <div style=${css('display:grid;gap:20px;align-content:start')}>
                        <section style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;padding:18px')}>
                            <div style=${css('display:flex;justify-content:space-between;align-items:baseline;margin-bottom:12px')}><h2 style=${css('font-size:14px;margin:0')}>Site health</h2><span style=${css('font-size:24px;font-weight:600')}>${score}<span style=${css('font-size:14px;color:#5b6977')}>/100</span></span></div>
                            <div style=${css('height:6px;background:#edf0f2;border-radius:3px;margin-bottom:12px;overflow:hidden')}><div style=${css(`width:${score}%;height:100%;background:${score > 85 ? '#2f8a57' : '#e59b23'}`)}></div></div>
                            ${health.map((h) => html`<button key=${h.label} onClick=${h.go} style=${css('display:flex;justify-content:space-between;gap:12px;width:100%;padding:8px 0;border:0;border-top:1px solid #f0f2f4;background:transparent;cursor:pointer;font-size:13px;color:#10263b;text-align:left')}><span>${h.label}</span><span style=${css(h.style)}>${h.value}</span></button>`)}
                        </section>
                        <section style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;padding:18px')}>
                            <h2 style=${css('font-size:14px;margin:0 0 8px')}>Recent activity</h2>
                            ${BOOT.activity.map((a, i) => html`<div key=${i} style=${css('display:flex;gap:10px;padding:8px 0;border-top:1px solid #f0f2f4;font-size:13px')}><span style=${css('width:6px;height:6px;border-radius:50%;background:#e59b23;margin-top:6px;flex-shrink:0')}></span><span style=${css('flex:1')}><strong style=${css('font-weight:600')}>${a.who}</strong> ${a.what}</span><span style=${css('color:#8a97a3;font-size:12px;white-space:nowrap')}>${a.when}</span></div>`)}
                            ${! BOOT.activity.length && html`<div style=${css('font-size:13px;color:#8a97a3')}>No activity yet.</div>`}
                        </section>
                    </div>
                </div>
            </div>
        </div>`;
    }

    // ------------------------------------------------------------------ pages

    function Pages({ app }) {
        const st = app.state, q = st.pageQuery.toLowerCase();
        const cols = 'display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1.2fr) 110px 90px minmax(0,1fr) 200px;gap:12px';
        const rows = st.pages.filter((p) => ! q || p.title.toLowerCase().includes(q) || p.slug.includes(q));
        return html`<div style=${css('flex:1;overflow:auto')}>
            <header style=${css('display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 32px;border-bottom:1px solid #e3e7ea;background:#fff;flex-wrap:wrap')}>
                <div><h1 style=${css('font-size:22px;font-weight:600;margin:0')}>Pages</h1><div style=${css('font-size:13px;color:#5b6977;margin-top:2px')}>Every page on the website. Click a page to edit its sections visually.</div></div>
                <div style=${css('display:flex;gap:8px;align-items:center')}>
                    <input value=${st.pageQuery} onChange=${(e) => app.setState({ pageQuery: e.target.value })} placeholder="Search pages…" style=${css('width:220px;border:1px solid #d3d9de;border-radius:6px;padding:8px 11px;font-size:13px;outline:none')} />
                    <button onClick=${() => app.newPage()} style=${css(btnDark)}>+ New page</button>
                </div>
            </header>
            <div style=${css('padding:24px 32px')}>
                <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;overflow-x:auto')}><div style=${css('min-width:900px')}>
                    <div style=${css(cols + ';padding:11px 18px;background:#fafbfc;border-bottom:1px solid #e3e7ea;font-size:12px;font-weight:600;color:#5b6977')}><span>Page</span><span>URL</span><span>Status</span><span>Sections</span><span>Last edited</span><span></span></div>
                    ${rows.map((p) => { const edit = () => app.go('editor', { pageId: p.id, sel: 0, tab: 'Section' }); return html`<div key=${p.id} style=${css(cols + ';align-items:center;padding:13px 18px;border-bottom:1px solid #edf0f2')}>
                        <button onClick=${edit} style=${css('background:transparent;border:0;padding:0;text-align:left;cursor:pointer;color:#10263b')}><span style=${css('display:block;font-weight:600;font-size:14px')}>${p.title}</span><span style=${css('font-size:12px;color:#8a97a3')}>${p.template} template · ${p.inNav ? 'In menu' : 'Not in menu'}</span></button>
                        <span style=${css('font-family:ui-monospace,monospace;font-size:12px;color:#5b6977;overflow:hidden;text-overflow:ellipsis')}>${p.slug}</span>
                        <span style=${css(stPill(p.status))}>${p.status}</span>
                        <span style=${css('color:#5b6977')}>${p.sections.length} sections</span>
                        <span style=${css('font-size:13px;color:#5b6977')}>${p.updated} · ${p.by}</span>
                        <div style=${css('display:flex;gap:6px;justify-content:flex-end')}>
                            <button onClick=${edit} style=${css('background:#10263b;color:#fff;border:0;border-radius:6px;padding:7px 12px;font-size:12px;font-weight:600;cursor:pointer')}>Edit</button>
                            <button onClick=${() => app.duplicatePage(p)} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:7px 10px;font-size:12px;font-weight:600;cursor:pointer;color:#10263b')}>Duplicate</button>
                            <button onClick=${() => app.deletePage(p)} title="Delete page" style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:7px 9px;font-size:12px;cursor:pointer;color:#b4413b')}>✕</button>
                        </div>
                    </div>`; })}
                </div></div>
                <div style=${css('margin-top:18px;font-size:13px;color:#5b6977')}>Service detail pages are generated from <button onClick=${() => app.go('services')} style=${css('background:none;border:0;padding:0;color:#c97d0f;font-weight:600;cursor:pointer;font-size:13px')}>Services</button>, articles from <button onClick=${() => app.go('news')} style=${css('background:none;border:0;padding:0;color:#c97d0f;font-weight:600;cursor:pointer;font-size:13px')}>News</button>, and profiles from <button onClick=${() => app.go('team')} style=${css('background:none;border:0;padding:0;color:#c97d0f;font-weight:600;cursor:pointer;font-size:13px')}>Team</button>.</div>
            </div>
        </div>`;
    }

    // ----------------------------------------------------------------- editor

    function Editor({ app }) {
        const st = app.state;
        const pi = app.pageIdx(), page = st.pages[pi];
        if (! page) return html`<div style=${css('padding:32px')}>This page no longer exists. <button onClick=${() => app.go('pages')}>Back to Pages</button></div>`;
        const secs = page.sections, sel = Math.min(st.sel, secs.length - 1), selS = secs[sel];
        const ST = BOOT.sectionTypes;
        const vw = st.vw, narrow = vw < 1280, compact = vw < 1200, dev = st.device;
        const saveState = st.saveState[page.id];
        const editorCols = compact ? 'minmax(0,1fr) 320px' : narrow ? '220px minmax(0,1fr) 320px' : '250px minmax(0,1fr) 360px';
        const outlineStyle = compact ? `display:${st.outlineOpen ? 'block' : 'none'};position:absolute;left:0;top:0;bottom:0;width:260px;z-index:25;box-shadow:12px 0 30px rgba(16,38,59,.18);background:#fff;overflow:auto;padding:14px` : 'border-right:1px solid #e3e7ea;background:#fff;overflow:auto;padding:14px';
        const outline = secs.map((x, i) => ({ type: (ST[x.type] || {}).label || x.type, heading: x.heading || x.eyebrow || (x.items && x.items[0] ? x.items.map((it) => it.v || it.title || it.label || it.q).join(' · ') : '') || ((ST[x.type] || {}).source ? 'From ' + ST[x.type].source : '—'), hidden: !! x.hidden, i }));
        const library = ['Headers', 'Content', 'Collections', 'Interactive', 'Conversion'].map((g) => ({ group: g, items: Object.entries(ST).filter(([, v]) => v.group === g) }));
        const sdef = selS ? ST[selS.type] : null;
        const base = ['pages', pi, 'sections', sel];
        const sourceText = sdef && sdef.source ? { services: 'Content comes from the Services collection.', team: 'People come from the Team collection.', locations: 'Pins come from the Locations collection.', news: 'Articles come from the News collection.' }[sdef.source] : '';
        const statusAction = { Published: 'publish', Draft: 'draft', 'In review': 'review' };
        const pageFields = [
            { k: 'title', label: 'Page title' }, { k: 'slug', label: 'URL', hint: 'Old URL redirects automatically' },
            { k: 'status', label: 'Status', type: 'select', options: ['Published', 'Draft', 'Scheduled', 'In review'] },
            { k: 'template', label: 'Template', type: 'select', options: ['Landing', 'Standard', 'Listing', 'Legal'] },
            { k: 'inNav', label: 'Show in header menu', type: 'toggle' },
            { k: 'headerStyle', label: 'Header style', type: 'select', options: ['Transparent over hero', 'Solid'] },
            { k: 'visibility', label: 'Visibility', type: 'select', options: ['Public', 'Password protected', 'Hidden from search'] },
            ...(page.visibility === 'Password protected' ? [{ k: 'password', label: 'Page password', hint: page.hasPassword ? 'Leave blank to keep the current one' : 'Visitors must enter this' }] : []),
            { k: 'footerCta', label: 'Show footer call to action', type: 'toggle' },
        ].map((f) => {
            const field = app.field(f, ['pages', pi, f.k]);
            if (f.k === 'status') field.onChange = (e) => { const v = e.target.value; if (v === 'Scheduled') app.setState({ publishOpen: true, scheduleOpen: true }); else app.publish(statusAction[v]); };
            return field;
        });
        const seoFields = [{ k: 'seoTitle', label: 'SEO title', max: 60 }, { k: 'seoDesc', label: 'Meta description', type: 'textarea', rows: 4, max: 155 }, { k: 'shareImage', label: 'Social share image', type: 'image', hint: 'Falls back to site default' }].map((f) => app.field(f, ['pages', pi, f.k]));
        const seoChecks = [
            [page.seoTitle.length > 10 && page.seoTitle.length <= 60, 'Title is 10–60 characters'],
            [page.seoDesc.length > 70 && page.seoDesc.length <= 155, 'Description is 70–155 characters'],
            [secs.filter((s) => s.type === 'hero').length === 1, 'Page has exactly one main heading'],
            [secs.every((s) => s.type !== 'hero' || !! s.image || page.template !== 'Landing'), 'Hero has an image'],
            [secs.some((s) => ['cta', 'form'].includes(s.type)) || page.footerCta, 'Page ends with a call to action'],
        ];
        const publishOpts = [
            app.can('publish_pages') && { label: 'Publish now', hint: `Goes live at ${BOOT.siteHost}${page.slug}`, run: () => app.publish('publish') },
            app.can('publish_pages') && { label: 'Schedule…', hint: page.scheduledAt ? `Currently ${new Date(page.scheduledAt.replace(' ', 'T')).toLocaleString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}` : 'Choose a date and time', run: () => app.setState({ scheduleOpen: ! st.scheduleOpen }) },
            app.can('edit_pages') && { label: 'Request review', hint: 'Notify administrators to approve', run: () => app.publish('review') },
            app.can('publish_pages') && page.slug !== '/' && { label: 'Revert to draft', hint: 'Unpublish this page', run: () => app.publish('draft') },
        ].filter(Boolean);
        const inspTab = (t) => html`<button key=${t} onClick=${() => app.setState({ tab: t })} style=${css(`flex:1;border:0;border-bottom:2px solid ${st.tab === t ? '#e59b23' : 'transparent'};background:transparent;padding:13px;font-weight:600;cursor:pointer;font-size:13px;color:${st.tab === t ? '#10263b' : '#5b6977'}`)}>${t}</button>`;
        return html`<div style=${css('flex:1;display:flex;flex-direction:column;min-height:0')}>
            <header style=${css('height:60px;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0 16px;border-bottom:1px solid #e3e7ea;background:#fff')}>
                <div style=${css('display:flex;align-items:center;gap:10px;min-width:0;flex:1;white-space:nowrap;overflow:hidden')}>
                    <button onClick=${() => app.go('pages')} style=${css('flex-shrink:0;background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:7px 10px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>← Pages</button>
                    ${compact && html`<button onClick=${() => app.setState({ outlineOpen: ! st.outlineOpen })} style=${css('flex-shrink:0;background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:7px 10px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>☰ Sections</button>`}
                    <strong style=${css('font-size:15px;overflow:hidden;text-overflow:ellipsis;min-width:0')}>${page.title}</strong>
                    <span style=${css(stPill(page.status) + ';flex-shrink:0')}>${page.status}</span>
                    ${vw >= 1440 && html`<span style=${css(`color:${saveState === 'error' ? '#b4413b' : '#8a97a3'};font-size:12px`)}>${saveState === 'saving' ? 'Saving…' : saveState === 'error' ? 'Not saved' : page.hasDraft && page.live ? 'Autosaved · unpublished changes' : 'Autosaved · ' + page.updated}</span>`}
                </div>
                <div style=${css('display:flex;align-items:center;gap:3px;background:#f0f2f4;border-radius:7px;padding:3px;flex-shrink:0')}>
                    ${['Desktop', 'Tablet', 'Mobile'].map((d) => html`<button key=${d} onClick=${() => app.setState({ device: d })} style=${css(`border:0;border-radius:5px;padding:6px 11px;font-size:13px;font-weight:600;cursor:pointer;background:${dev === d ? '#fff' : 'transparent'};color:${dev === d ? '#10263b' : '#5b6977'};box-shadow:${dev === d ? '0 1px 2px rgba(0,0,0,.08)' : 'none'}`)}>${narrow ? d[0] : d}</button>`)}
                </div>
                <div style=${css('display:flex;align-items:center;gap:6px;position:relative;flex-shrink:0')}>
                    <button onClick=${() => app.toggleHistory()} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 11px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>History</button>
                    <button onClick=${() => app.copyPreview()} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 11px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>Share preview</button>
                    <button onClick=${() => app.setState({ publishOpen: ! st.publishOpen, scheduleOpen: false })} style=${css('background:#10263b;color:#fff;border:0;border-radius:6px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer')}>Publish ▾</button>
                    ${st.publishOpen && html`<div style=${css('position:absolute;right:0;top:44px;width:290px;background:#fff;border:1px solid #e3e7ea;border-radius:10px;box-shadow:0 18px 40px rgba(16,38,59,.16);z-index:30;padding:6px')}>
                        ${publishOpts.map((o) => html`<div key=${o.label}>
                            <button onClick=${o.run} className="sf-hover-menu" style=${css('display:block;width:100%;text-align:left;background:transparent;border:0;border-radius:6px;padding:11px;cursor:pointer;color:#10263b')}><div style=${css('font-weight:600')}>${o.label}</div><div style=${css('font-size:12px;color:#5b6977;margin-top:2px')}>${o.hint}</div></button>
                            ${o.label === 'Schedule…' && st.scheduleOpen && html`<div style=${css('display:flex;gap:6px;padding:0 11px 11px')}><input type="datetime-local" value=${st.scheduleAt} onChange=${(e) => app.setState({ scheduleAt: e.target.value })} style=${css('flex:1;min-width:0;border:1px solid #d3d9de;border-radius:6px;padding:7px 8px;font-size:13px')} /><button onClick=${() => app.publish('schedule', st.scheduleAt)} style=${css(btnDark + ';padding:7px 10px')}>Schedule</button></div>`}
                        </div>`)}
                        ${! publishOpts.length && html`<div style=${css('padding:11px;font-size:13px;color:#5b6977')}>Your role can edit drafts only.</div>`}
                    </div>`}
                </div>
            </header>
            <div style=${css(`flex:1;display:grid;grid-template-columns:${editorCols};min-height:0;position:relative`)}>
                <div style=${css(outlineStyle)}>
                    <div style=${css('font-size:12px;font-weight:600;color:#5b6977;margin:2px 4px 10px')}>Sections</div>
                    <div style=${css('display:grid;gap:5px')}>
                        ${outline.map((o) => html`<button key=${secs[o.i].id} onClick=${() => app.setState({ sel: o.i, tab: 'Section' })} style=${css(`display:flex;align-items:center;gap:9px;width:100%;border:1px solid ${o.i === sel ? '#e59b23' : '#e3e7ea'};border-radius:7px;background:${o.i === sel ? '#fffaf1' : '#fff'};padding:8px 9px;cursor:pointer;color:#10263b;opacity:${o.hidden ? .5 : 1}`)}>
                            <span style=${css('color:#a3aeb8;cursor:grab')}>⠿</span>
                            <span style=${css('flex:1;min-width:0;text-align:left')}><span style=${css('display:block;font-size:11px;font-weight:600;color:#c97d0f')}>${o.type}</span><span style=${css('display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;font-size:13px')}>${o.heading}</span></span>
                            ${o.hidden && html`<span style=${css('font-size:11px;color:#8a97a3')}>Hidden</span>`}
                        </button>`)}
                    </div>
                    <button onClick=${() => app.setState({ libraryOpen: ! st.libraryOpen })} style=${css('margin-top:10px;width:100%;background:transparent;border:1px dashed #b9c2ca;border-radius:8px;padding:11px;font-weight:600;cursor:pointer;color:#10263b;font-size:13px')}>+ Add section</button>
                    ${st.libraryOpen && html`<div style=${css('margin-top:10px;display:grid;gap:12px')}>
                        ${library.map((lg) => html`<div key=${lg.group}>
                            <div style=${css('font-size:11px;font-weight:600;color:#8a97a3;margin:0 2px 6px')}>${lg.group}</div>
                            <div style=${css('display:grid;gap:5px')}>${lg.items.map(([k, v]) => html`<button key=${k} onClick=${() => app.addSection(k)} className="sf-hover-lib" style=${css('background:#f4f6f8;border:1px solid #e3e7ea;border-radius:6px;padding:9px 10px;font-size:12px;font-weight:600;cursor:pointer;text-align:left;color:#10263b')}>${v.label}</button>`)}</div>
                        </div>`)}
                    </div>`}
                </div>
                <div style=${css('overflow:auto;background:#e6eaee;padding:24px;display:flex;justify-content:center')}>
                    <${PreviewFrame} app=${app} page=${page} sel=${sel} device=${dev} />
                </div>
                <div style=${css('border-left:1px solid #e3e7ea;background:#fff;overflow:auto;display:flex;flex-direction:column')}>
                    <div style=${css('display:flex;border-bottom:1px solid #e3e7ea;flex-shrink:0;position:sticky;top:0;background:#fff;z-index:2')}>${['Section', 'Page', 'SEO'].map(inspTab)}</div>
                    ${st.tab === 'Section' && html`<div style=${css('padding:18px;display:grid;gap:16px;grid-template-columns:minmax(0,1fr)')}>
                        <div><div style=${css('font-size:12px;font-weight:600;color:#c97d0f')}>${sdef ? sdef.label : 'No section'}</div><div style=${css('color:#5b6977;font-size:12px;margin-top:3px')}>${selS ? 'Changes appear in the preview as you type.' : 'Add a section to get started.'}</div></div>
                        ${sdef && sdef.fields.map((f) => F(app.field(f, [...base, f.k]), selS.id + f.k))}
                        ${sourceText && html`<button onClick=${() => app.go(sdef.source)} style=${css('background:#fffaf1;border:1px solid #f3d9a8;border-radius:8px;padding:12px;text-align:left;font-size:13px;cursor:pointer;color:#10263b')}>${sourceText} <strong style=${css('color:#c97d0f')}>Manage →</strong></button>`}
                        ${selS && html`<div style=${css('display:grid;grid-template-columns:1fr 1fr;gap:8px;padding-top:12px;border-top:1px solid #edf0f2')}>
                            ${F(app.field({ label: 'Background', type: 'select', options: ['Default', 'Soft', 'Navy'] }, [...base, 'bg']))}
                            ${F(app.field({ label: 'Spacing', type: 'select', options: ['Standard', 'Compact', 'Spacious'] }, [...base, 'spacing']))}
                            ${F(app.field({ label: 'Anchor ID', placeholder: 'e.g. services' }, [...base, 'anchor']))}
                            ${F(app.field({ label: 'Show on', type: 'select', options: ['All devices', 'Desktop only', 'Mobile only'] }, [...base, 'devices']))}
                        </div>`}
                    </div>`}
                    ${st.tab === 'Page' && html`<div style=${css('padding:18px;display:grid;gap:16px;grid-template-columns:minmax(0,1fr)')}>${pageFields.map((f) => F(f))}</div>`}
                    ${st.tab === 'SEO' && html`<div style=${css('padding:18px;display:grid;gap:16px;grid-template-columns:minmax(0,1fr)')}>
                        <div style=${css('border:1px solid #e3e7ea;border-radius:8px;padding:12px;background:#fafbfc')}>
                            <div style=${css('font-size:11px;color:#5b6977;margin-bottom:6px')}>Search result preview</div>
                            <div style=${css('font-size:12px;color:#3c4043')}>${BOOT.siteHost} › ${page.slug.replace(/^\//, '') || 'home'}</div>
                            <div style=${css('font-size:17px;color:#1a0dab;line-height:1.3;margin:2px 0')}>${page.seoTitle || page.title}</div>
                            <div style=${css('font-size:13px;color:#4d5156;line-height:1.45')}>${page.seoDesc || st.settings.seo.defaultDesc}</div>
                        </div>
                        ${seoFields.map((f) => F(f))}
                        <div><div style=${css('font-size:13px;font-weight:600;margin-bottom:6px')}>Checklist</div>${seoChecks.map(([ok, label]) => html`<div key=${label} style=${css('display:flex;gap:10px;padding:6px 0;font-size:13px;border-top:1px solid #f0f2f4')}><span style=${css(`width:16px;font-weight:700;color:${ok ? '#2f8a57' : '#c97d0f'}`)}>${ok ? '✓' : '!'}</span>${label}</div>`)}</div>
                    </div>`}
                    ${st.history && html`<div style=${css('border-top:1px solid #e3e7ea;padding:18px;margin-top:auto;background:#fafbfc')}>
                        <div style=${css('font-size:13px;font-weight:600;margin-bottom:8px')}>Version history</div>
                        <div style=${css('display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 0;border-top:1px solid #edf0f2;font-size:13px')}><div><div style=${css('font-weight:600')}>Current draft</div><div style=${css('color:#5b6977;font-size:12px')}>${page.by} · ${page.updated}</div></div></div>
                        ${st.versions === null ? html`<div style=${css('font-size:12px;color:#8a97a3;padding:8px 0')}>Loading…</div>` : st.versions.map((v) => html`<div key=${v.id} style=${css('display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 0;border-top:1px solid #edf0f2;font-size:13px')}><div><div style=${css('font-weight:600')}>${v.when}</div><div style=${css('color:#5b6977;font-size:12px')}>${v.who}</div></div><button onClick=${() => app.restoreVersion(v)} style=${css('background:transparent;border:0;color:#c97d0f;font-weight:600;cursor:pointer;font-size:12px')}>Restore</button></div>`)}
                        ${st.versions && ! st.versions.length && html`<div style=${css('font-size:12px;color:#8a97a3;padding:8px 0;border-top:1px solid #edf0f2')}>Earlier versions appear here after the next publish.</div>`}
                    </div>`}
                </div>
            </div>
        </div>`;
    }

    // ------------------------------------------------------------ collections

    function Collection({ app }) {
        const st = app.state, sc = st.screen;
        const C = BOOT.collections[sc], items = st.colls[sc], q = st.collQuery.toLowerCase();
        const idx = items.findIndex((x) => app.itemKey(x) === st.itemId), item = items[idx];
        const vw = st.vw;
        const grid = `display:grid;grid-template-columns:minmax(0,2fr) repeat(${C.cols.length - 1},minmax(0,1fr)) 64px;gap:12px;align-items:center`;
        const help = { news: 'Articles shown on the News page, homepage feed and article pages.', services: 'Each service powers its own page, the Services list and the service finder.', team: 'People shown on the Leadership page and in profile panels.', locations: 'States, map pins and supply routes on the network map.' }[sc];
        const rows = items.map((x, i) => ({ x, i })).filter(({ x }) => ! q || JSON.stringify(x).toLowerCase().includes(q));
        const panelStyle = vw >= 1200 ? 'border-left:1px solid #e3e7ea;background:#fff;display:flex;flex-direction:column;min-height:0' : 'position:absolute;top:0;right:0;bottom:0;width:min(420px,100%);z-index:20;background:#fff;display:flex;flex-direction:column;min-height:0;border-left:1px solid #e3e7ea;box-shadow:-16px 0 40px rgba(16,38,59,.16)';
        return html`<div style=${css('flex:1;display:flex;flex-direction:column;min-height:0')}>
            <header style=${css(header + ';flex-shrink:0')}>
                <div><h1 style=${css('font-size:22px;font-weight:600;margin:0')}>${C.label}</h1><div style=${css('font-size:13px;color:#5b6977;margin-top:2px')}>${help}</div></div>
                <div style=${css('display:flex;gap:8px;align-items:center')}>
                    <input value=${st.collQuery} onChange=${(e) => app.setState({ collQuery: e.target.value })} placeholder="Search…" style=${css('width:200px;border:1px solid #d3d9de;border-radius:6px;padding:8px 11px;font-size:13px;outline:none')} />
                    <button onClick=${() => app.newItem(sc)} style=${css(btnDark)}>+ New ${C.singular}</button>
                </div>
            </header>
            <div style=${css(`flex:1;display:grid;grid-template-columns:${item && vw >= 1200 ? 'minmax(0,1fr) 420px' : 'minmax(0,1fr)'};min-height:0;position:relative`)}>
                <div style=${css('overflow:auto;padding:20px 28px')}>
                    <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;overflow-x:auto')}><div style=${css('min-width:640px')}>
                        <div style=${css(grid + ';padding:11px 18px;background:#fafbfc;border-bottom:1px solid #e3e7ea;font-size:12px;font-weight:600;color:#5b6977')}>${C.cols.map((c) => html`<span key=${c[0]}>${c[1]}</span>`)}<span></span></div>
                        ${rows.map(({ x, i }) => { const on = app.itemKey(x) === st.itemId; return html`<div key=${app.itemKey(x)} onClick=${() => app.setState({ itemId: app.itemKey(x) })} style=${css(grid + `;padding:12px 18px;border-bottom:1px solid #edf0f2;cursor:pointer;background:${on ? '#fffaf1' : '#fff'};border-left:3px solid ${on ? '#e59b23' : 'transparent'}`)}>
                            ${C.cols.map(([k], ci) => { const v = x[k]; const isStatus = k === 'status' || k === 'visible'; return html`<span key=${k} style=${css(isStatus ? stPill(v) : ci === 0 ? 'font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap' : 'color:#5b6977;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap')}>${typeof v === 'boolean' ? (v ? 'Visible' : 'Hidden') : (v || '—')}${ci === 0 && ! x.id ? ' · unsaved' : ''}</span>`; })}
                            <span style=${css('display:flex;gap:2px;justify-content:flex-end')}><button onClick=${(e) => { e.stopPropagation(); app.moveItem(sc, i, -1); }} title="Move up" style=${css('background:transparent;border:0;cursor:pointer;color:#5b6977;padding:4px 6px')}>↑</button><button onClick=${(e) => { e.stopPropagation(); app.moveItem(sc, i, 1); }} title="Move down" style=${css('background:transparent;border:0;cursor:pointer;color:#5b6977;padding:4px 6px')}>↓</button></span>
                        </div>`; })}
                        ${! rows.length && html`<div style=${css('padding:32px;text-align:center;color:#5b6977')}>Nothing matches your search.</div>`}
                    </div></div>
                </div>
                ${item && html`<div style=${css(panelStyle)}>
                    <div style=${css('display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid #e3e7ea')}>
                        <div style=${css('min-width:0')}><div style=${css('font-size:12px;color:#c97d0f;font-weight:600;text-transform:capitalize')}>Edit ${C.singular}</div><div style=${css('font-weight:600;font-size:15px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap')}>${item[C.titleKey] || 'Untitled'}</div></div>
                        <button onClick=${() => app.setState({ itemId: null })} style=${css('background:transparent;border:0;font-size:16px;cursor:pointer;color:#5b6977;padding:4px 8px')}>✕</button>
                    </div>
                    <div style=${css('flex:1;overflow:auto;padding:18px;display:grid;gap:16px;align-content:start;grid-template-columns:minmax(0,1fr)')}>
                        ${C.fields.map((f) => F(app.field(f, ['colls', sc, idx, f.k]), app.itemKey(item) + f.k))}
                    </div>
                    <div style=${css('display:flex;justify-content:space-between;gap:8px;padding:12px 18px;border-top:1px solid #e3e7ea;background:#fafbfc')}>
                        <div style=${css('display:flex;gap:6px')}><button onClick=${() => app.dupItem(sc)} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>Duplicate</button><button onClick=${() => app.delItem(sc)} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;color:#b4413b')}>Delete</button></div>
                        <button onClick=${() => app.saveItem(sc)} style=${css('background:#10263b;color:#fff;border:0;border-radius:6px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer')}>Save & publish</button>
                    </div>
                </div>`}
            </div>
        </div>`;
    }

    // ------------------------------------------------------------------ media

    function Media({ app }) {
        const st = app.state;
        const folders = ['All', ...new Set(st.media.map((m) => m.folder))];
        const list = st.media.filter((m) => st.folder === 'All' || m.folder === st.folder);
        const mi = Math.max(0, st.media.findIndex((m) => m.id === st.mediaSel)), cm = st.media[mi] || { used: [] };
        const fields = [{ k: 'alt', label: 'Alt text', hint: 'Describe the image for screen readers' }, { k: 'caption', label: 'Caption' }, { k: 'folder', label: 'Folder', type: 'select', options: ['Brand', 'Operations', 'People', 'News', 'Uploads'] }];
        return html`<div style=${css('flex:1;display:flex;flex-direction:column;min-height:0')}>
            <header style=${css(header + ';flex-shrink:0')}>
                <div><h1 style=${css('font-size:22px;font-weight:600;margin:0')}>Media library</h1><div style=${css('font-size:13px;color:#5b6977;margin-top:2px')}>${st.media.length} files · images used anywhere on the site</div></div>
                <div style=${css('display:flex;gap:8px;align-items:center')}>
                    <div style=${css('display:flex;gap:4px')}>${folders.map((f) => html`<button key=${f} onClick=${() => app.setState({ folder: f })} style=${css(`border:1px solid ${st.folder === f ? '#10263b' : '#d3d9de'};background:${st.folder === f ? '#10263b' : '#fff'};color:${st.folder === f ? '#fff' : '#10263b'};border-radius:999px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer`)}>${f}</button>`)}</div>
                    <button onClick=${() => app.fileRef.current.click()} style=${css(btnDark)}>Upload</button>
                </div>
            </header>
            <div style=${css('flex:1;display:grid;grid-template-columns:minmax(0,1fr) 340px;min-height:0')}>
                <div style=${css('overflow:auto;padding:20px 28px')} onDragOver=${(e) => e.preventDefault()} onDrop=${(e) => { e.preventDefault(); app.upload(e.dataTransfer.files); }}>
                    <button onClick=${() => app.fileRef.current.click()} className="sf-hover-drop" style=${css('width:100%;border:2px dashed #c3ccd4;border-radius:10px;background:#fff;padding:22px;margin-bottom:16px;cursor:pointer;color:#5b6977;font-size:14px')}>Drop images here or <strong style=${css('color:#c97d0f')}>browse</strong> · JPG, PNG, WebP up to 10 MB</button>
                    <div style=${css('display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px')}>
                        ${list.map((m) => html`<button key=${m.id} onClick=${() => app.setState({ mediaSel: m.id })} style=${css(`padding:0;border:1px solid ${m.id === cm.id ? '#e59b23' : '#e3e7ea'};box-shadow:${m.id === cm.id ? '0 0 0 3px rgba(229,155,35,.2)' : 'none'};border-radius:8px;overflow:hidden;background:#fff;cursor:pointer;color:#10263b`)}>
                            <img src=${m.src} alt=${m.alt} style=${css(`width:100%;aspect-ratio:4/3;display:block;background:#f0f2f4;object-fit:${m.folder === 'Brand' ? 'contain;padding:14px' : 'cover'}`)} />
                            <span style=${css('display:block;padding:8px 10px;text-align:left')}><span style=${css('display:block;font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap')}>${m.name}</span><span style=${css(`display:block;font-size:11px;margin-top:2px;color:${m.alt ? '#2f8a57' : '#c97d0f'};font-weight:600`)}>${m.alt ? '✓ Alt text' : '! Missing alt text'}</span></span>
                        </button>`)}
                    </div>
                </div>
                <div style=${css('border-left:1px solid #e3e7ea;background:#fff;overflow:auto;padding:18px;display:grid;gap:14px;align-content:start;grid-template-columns:minmax(0,1fr)')}>
                    ${cm.src ? html`<img src=${cm.src} alt="" style=${css('width:100%;aspect-ratio:4/3;object-fit:contain;background:#f0f2f4;border-radius:8px;display:block')} />` : null}
                    <div><div style=${css('font-weight:600;font-size:14px;word-break:break-all')}>${cm.name || 'No image selected'}</div><div style=${css('font-size:12px;color:#5b6977;margin-top:2px')}>${cm.dims || ''}${cm.size ? ' · ' + cm.size : ''}</div></div>
                    ${cm.id && fields.map((f) => F(app.field(f, ['media', mi, f.k]), cm.id + f.k))}
                    <div><div style=${css('font-size:13px;font-weight:600;margin-bottom:6px')}>Used on</div>${(cm.used || []).map((u) => html`<div key=${u} style=${css('font-size:13px;color:#5b6977;padding:5px 0;border-top:1px solid #f0f2f4')}>${u}</div>`)}${(! cm.used || ! cm.used.length) && html`<div style=${css('font-size:13px;color:#8a97a3')}>Not used yet</div>`}</div>
                    ${cm.id && html`<div style=${css('display:flex;gap:6px;flex-wrap:wrap')}>
                        <button onClick=${() => { const url = BOOT.siteUrl + cm.src; (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(() => app.flash('Image URL copied'), () => window.prompt('Image URL', url)); }} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>Copy URL</button>
                        <button onClick=${() => app.replaceRef.current.click()} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>Replace</button>
                        <button onClick=${() => app.deleteMedia()} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;color:#b4413b')}>Delete</button>
                    </div>`}
                </div>
            </div>
        </div>`;
    }

    // ------------------------------------------------------------------ inbox

    function Inbox({ app }) {
        const st = app.state;
        const filtered = st.inquiries.filter((x) => st.filter === 'All' || statusMeta[x.status][0] === st.filter);
        const cur = st.inquiries.find((q) => q.id === st.inq) || st.inquiries[0];
        if (! cur) return html`<div style=${css('flex:1;display:grid;place-items:center;color:#5b6977')}><div style=${css('text-align:center')}><h1 style=${css('font-size:22px;font-weight:600;margin:0 0 6px;color:#10263b')}>Inbox</h1>No inquiries yet. Website inquiries will arrive here.</div></div>`;
        const forms = st.settings.forms, site = st.settings.general.siteName;
        const route = ((forms.topics.find((t) => t.label === cur.topic) || {}).route) || st.settings.contact.email;
        const first = cur.name.split(' ')[0];
        const templates = [
            { label: 'Quote requested', text: `Dear ${first},\n\nThank you for contacting ${site}. Our commercial team is preparing a quote and will share it within ${forms.responseTime.toLowerCase()}.\n\nKind regards,\n${site}` },
            { label: 'Need more details', text: `Dear ${first},\n\nThank you for your inquiry. Could you confirm the product, volume and delivery location so we can respond accurately?\n\nKind regards,\n${site}` },
            { label: 'Forward to depot', text: `Dear ${first},\n\nI've shared your request with our Calabar depot team, who will contact you directly.\n\nKind regards,\n${site}` },
        ];
        const assignees = ['Unassigned', ...st.users.filter((u) => u.status !== 'Inactive').map((u) => u.name)];
        return html`<div style=${css('flex:1;display:grid;grid-template-columns:minmax(260px,330px) minmax(0,1fr);min-height:0')}>
            <div style=${css('border-right:1px solid #e3e7ea;background:#fff;display:flex;flex-direction:column;min-height:0')}>
                <div style=${css('padding:18px 18px 0')}>
                    <div style=${css('display:flex;justify-content:space-between;align-items:baseline')}><h1 style=${css('font-size:22px;font-weight:600;margin:0')}>Inbox</h1><a href=${BOOT.base + '/inquiries/export'} style=${css('font-weight:600;font-size:13px;color:#c97d0f')}>Export CSV</a></div>
                    <div style=${css('display:flex;gap:2px;margin:14px 0 0;border-bottom:1px solid #e3e7ea')}>
                        ${['All', 'New', 'In progress', 'Resolved'].map((f) => html`<button key=${f} onClick=${() => app.setState({ filter: f })} style=${css(`border:0;background:transparent;padding:9px 8px;font-size:13px;font-weight:600;cursor:pointer;border-bottom:2px solid ${st.filter === f ? '#e59b23' : 'transparent'};margin-bottom:-1px;color:${st.filter === f ? '#10263b' : '#5b6977'}`)}>${f} <span style=${css('color:#8a97a3')}>${f === 'All' ? st.inquiries.length : st.inquiries.filter((x) => statusMeta[x.status][0] === f).length}</span></button>`)}
                    </div>
                </div>
                <div style=${css('overflow:auto;flex:1')}>
                    ${filtered.map((x) => html`<button key=${x.id} onClick=${() => app.setState({ inq: x.id, reply: '' })} style=${css(`display:block;width:100%;text-align:left;border:0;border-bottom:1px solid #edf0f2;border-left:3px solid ${x.id === cur.id ? '#e59b23' : 'transparent'};background:${x.id === cur.id ? '#fffaf1' : '#fff'};padding:14px 18px;cursor:pointer;font-size:14px;color:#10263b`)}>
                        <span style=${css('display:flex;justify-content:space-between;gap:8px')}><span style=${css('font-weight:600')}>${x.name}</span><span style=${css('font-size:12px;color:#5b6977')}>${x.ago}</span></span>
                        <span style=${css('display:block;margin-top:2px;font-size:13px')}>${x.subject}</span>
                        <span style=${css('display:flex;gap:6px;margin-top:7px;align-items:center;flex-wrap:wrap')}><span style=${css('font-size:11px;background:#f0f2f4;border-radius:4px;padding:2px 7px')}>${x.topic}</span><span style=${css(pill(statusMeta[x.status][1], statusMeta[x.status][2]))}>${statusMeta[x.status][0]}</span>${x.overdue && html`<span style=${css('font-size:11px;color:#b4413b;font-weight:600')}>Overdue</span>`}</span>
                    </button>`)}
                </div>
            </div>
            <div style=${css('overflow:auto;padding:28px clamp(20px,3vw,36px)')}>
                <div style=${css('max-width:760px')}>
                    <div style=${css('display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap')}>
                        <div><div style=${css('font-size:12px;font-weight:600;color:#c97d0f')}>${cur.topic} · ${cur.ref}</div><h2 style=${css('font-size:22px;font-weight:600;line-height:1.3;margin:6px 0 0')}>${cur.subject}</h2></div>
                        <div style=${css('display:flex;gap:3px;background:#f0f2f4;border-radius:7px;padding:3px;white-space:nowrap')}>${Object.keys(statusMeta).map((k) => html`<button key=${k} onClick=${() => app.updateInquiry(cur.id, { status: k, overdue: k === 'new' && cur.overdue }, `Marked as ${statusMeta[k][0].toLowerCase()}`, true)} style=${css(`border:0;border-radius:5px;padding:6px 11px;font-size:13px;font-weight:600;cursor:pointer;background:${cur.status === k ? '#fff' : 'transparent'};color:${cur.status === k ? statusMeta[k][1] : '#5b6977'};box-shadow:${cur.status === k ? '0 1px 2px rgba(0,0,0,.08)' : 'none'}`)}>${statusMeta[k][0]}</button>`)}</div>
                    </div>
                    <div style=${css('display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:20px 0')}>
                        <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:8px;padding:12px')}><div style=${css('font-size:11px;color:#5b6977')}>From</div><div style=${css('font-weight:600;margin-top:3px')}>${cur.name}</div><div style=${css('font-size:12px;color:#5b6977')}>${cur.company}</div></div>
                        <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:8px;padding:12px')}><div style=${css('font-size:11px;color:#5b6977')}>Contact</div><div style=${css('font-weight:600;margin-top:3px;overflow:hidden;text-overflow:ellipsis')}><a href=${'mailto:' + cur.email}>${cur.email}</a></div><div style=${css('font-size:12px;color:#5b6977')}>${cur.phone}</div></div>
                        <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:8px;padding:12px')}><div style=${css('font-size:11px;color:#5b6977')}>Received</div><div style=${css('font-weight:600;margin-top:3px')}>${cur.ago}</div><div style=${css('font-size:12px;color:#5b6977')}>Routed to ${route}</div></div>
                        <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:8px;padding:12px')}><div style=${css('font-size:11px;color:#5b6977;margin-bottom:4px')}>Assigned to</div><select value=${cur.assignee} onChange=${(e) => app.updateInquiry(cur.id, { assignee: e.target.value })} style=${css('width:100%;border:1px solid #d3d9de;border-radius:6px;padding:6px 8px;font-size:13px;background:#fff;color:#10263b')}>${assignees.map((a) => html`<option key=${a} value=${a}>${a}</option>`)}</select></div>
                    </div>
                    <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;padding:20px;font-size:15px;line-height:1.7;white-space:pre-line')}>${cur.message}</div>
                    <div style=${css('margin-top:14px;background:#fffaf1;border:1px solid #f3d9a8;border-radius:10px;padding:12px 14px')}><div style=${css('font-size:12px;font-weight:600;color:#9a5c06;margin-bottom:6px')}>Internal note · only visible to your team</div><textarea rows="2" value=${cur.notes} onChange=${(e) => app.updateInquiry(cur.id, { notes: e.target.value })} placeholder="Add a note for colleagues…" style=${css('width:100%;border:0;background:transparent;font-size:14px;line-height:1.5;outline:none;resize:vertical;color:#10263b')}></textarea></div>
                    ${cur.replies.map((r, i) => html`<div key=${i} style=${css('margin-top:14px;background:#fff;border:1px solid #e3e7ea;border-radius:10px;padding:14px 16px')}><div style=${css('font-size:12px;color:#5b6977;margin-bottom:6px')}>${r.who} replied · ${r.when}${r.status !== 'sent' ? ' · not emailed' : ''}</div><div style=${css('font-size:14px;line-height:1.6;white-space:pre-line')}>${r.body}</div></div>`)}
                    <div style=${css('margin-top:14px;background:#fff;border:1px solid #e3e7ea;border-radius:10px;overflow:hidden')}>
                        <div style=${css('display:flex;gap:6px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid #edf0f2;align-items:center')}><span style=${css('font-size:12px;color:#5b6977;margin-right:4px')}>Templates</span>${templates.map((t) => html`<button key=${t.label} onClick=${() => app.setState({ reply: t.text })} style=${css('background:#f4f6f8;border:1px solid #e3e7ea;border-radius:999px;padding:5px 11px;font-size:12px;font-weight:600;cursor:pointer;color:#10263b')}>${t.label}</button>`)}</div>
                        <textarea rows="6" value=${st.reply} onChange=${(e) => app.setState({ reply: e.target.value })} placeholder="Write a reply…" style=${css('display:block;width:100%;border:0;padding:14px;font-size:15px;line-height:1.6;resize:vertical;outline:none;color:#10263b')}></textarea>
                        <div style=${css('display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 14px;border-top:1px solid #edf0f2')}><span style=${css('font-size:12px;color:#5b6977')}>Sends from ${st.settings.contact.email} · logged to this inquiry${BOOT.emailConfigured ? '' : ' · email not configured yet'}</span><button onClick=${() => app.sendReply()} style=${css('background:#10263b;color:#fff;border:0;border-radius:6px;padding:9px 16px;font-weight:600;cursor:pointer;font-size:13px')}>Send reply</button></div>
                    </div>
                </div>
            </div>
        </div>`;
    }

    // ------------------------------------------------------------- navigation

    function Navigation({ app }) {
        const st = app.state, T = st.theme, nav = st.nav;
        const btnR = T.button === 'Pill' ? 999 : T.button === 'Rounded' ? 6 : 0;
        const pvNav = nav.header.filter((h) => h.show !== 'Hidden').map((h) => linkLabel(h, st.settings));
        const linkSub = [{ k: 'label', label: 'Label' }, { k: 'link', label: 'Goes to', type: 'link' }];
        return html`<div style=${css('flex:1;overflow:auto')}>
            <header style=${css(header + ';position:sticky;top:0;z-index:5')}>
                <div><h1 style=${css('font-size:22px;font-weight:600;margin:0')}>Navigation</h1><div style=${css('font-size:13px;color:#5b6977;margin-top:2px')}>Header menu, header button and every footer link.</div></div>
                <div style=${css('display:flex;gap:8px')}><button onClick=${() => { app.setState({ nav: JSON.parse(st.navSaved) }); app.flash('Changes discarded'); }} style=${css(btnLight + ';padding:9px 14px')}>Discard</button><button onClick=${() => app.publishNav()} style=${css(btnDark)}>Publish changes</button></div>
            </header>
            <div style=${css('padding:20px 28px')}>
                <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;overflow:hidden;margin-bottom:20px')}>
                    <div style=${css(`display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 20px;background:#fff;color:${T.ink};font-family:'${T.font}',sans-serif`)}><div style=${css('background:#fff;border-radius:3px;padding:4px 7px;display:flex;border:1px solid #e3e7ea')}><img src=${st.settings.general.logo} alt="" style=${css('height:18px;width:auto;display:block')} /></div><div style=${css('display:flex;gap:18px;font-size:13px;font-weight:500')}>${pvNav.map((n, i) => html`<span key=${i}>${n}</span>`)}</div>${nav.cta.show !== 'Hidden' && html`<span style=${css(`background:${T.primary};color:#fff;font-size:12px;font-weight:600;padding:8px 14px;border-radius:${btnR}px`)}>${nav.cta.label}</span>`}</div>
                    <div style=${css('font-size:11px;color:#8a97a3;padding:6px 14px;border-top:1px solid #edf0f2;background:#fafbfc')}>Live preview of the site header</div>
                </div>
                <div style=${css('display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));gap:20px;align-items:start')}>
                    <section style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;padding:18px;display:grid;gap:16px;grid-template-columns:minmax(0,1fr)')}>
                        <h2 style=${css('font-size:15px;margin:0')}>Header</h2>
                        ${F(app.field({ label: 'Menu links', type: 'list', itemLabel: 'link', sub: [...linkSub, { k: 'show', label: 'Visibility', type: 'select', options: ['Visible', 'Hidden'] }] }, ['nav', 'header']))}
                        <div style=${css('display:grid;grid-template-columns:1fr 1fr;gap:12px')}>${[{ k: 'label', label: 'Header button label' }, { k: 'link', label: 'Button goes to', type: 'link' }, { k: 'show', label: 'Header button', type: 'select', options: ['Visible', 'Hidden'] }].map((f) => F(app.field(f, ['nav', 'cta', f.k])))}</div>
                        <div style=${css('display:grid;grid-template-columns:1fr 1fr;gap:12px')}>${[{ k: 'sticky', label: 'Sticky header', type: 'toggle' }, { k: 'transparent', label: 'Transparent over hero', type: 'toggle' }, { k: 'mobileBreakpoint', label: 'Mobile menu below', type: 'select', options: ['768px', '960px', '1100px'] }].map((f) => F(app.field(f, ['nav', f.k])))}</div>
                    </section>
                    <section style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;padding:18px;display:grid;gap:16px;grid-template-columns:minmax(0,1fr)')}>
                        <div style=${css('display:flex;justify-content:space-between;align-items:center')}><h2 style=${css('font-size:15px;margin:0')}>Footer columns</h2><button onClick=${() => app.setPath(['nav', 'columns'], [...nav.columns, { heading: 'New column', links: [{ label: 'Link', link: 'page:home' }] }])} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:6px 10px;font-size:12px;font-weight:600;cursor:pointer;color:#10263b')}>+ Add column</button></div>
                        ${nav.columns.map((c, i) => html`<div key=${i} style=${css('border:1px solid #e3e7ea;border-radius:8px;padding:12px;display:grid;gap:10px')}>
                            <div style=${css('display:flex;gap:8px;align-items:flex-end')}><div style=${css('flex:1')}>${F(app.field({ label: `Column ${i + 1} heading` }, ['nav', 'columns', i, 'heading']))}</div><button onClick=${() => { const prev = st.nav; app.setPath(['nav', 'columns'], nav.columns.filter((_, j) => j !== i)); app.flash('Column removed', () => app.setState({ nav: prev })); }} title="Remove column" style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:9px 10px;cursor:pointer;color:#b4413b')}>✕</button></div>
                            ${F(app.field({ label: 'Links', type: 'list', itemLabel: 'link', sub: linkSub }, ['nav', 'columns', i, 'links']))}
                        </div>`)}
                        <div style=${css('display:grid;grid-template-columns:1fr 1fr;gap:12px;padding-top:12px;border-top:1px solid #edf0f2')}>${[{ k: 'showAddress', label: 'Show office address', type: 'toggle' }, { k: 'showCta', label: 'Call-to-action band', type: 'toggle' }, { k: 'showWordmark', label: 'Background wordmark', type: 'toggle' }, { k: 'wordmark', label: 'Wordmark text' }, { k: 'copyright', label: 'Copyright line', hint: '{year} is replaced' }].map((f) => F(app.field(f, ['nav', f.k])))}</div>
                        ${F(app.field({ label: 'Legal links (bottom bar)', type: 'list', itemLabel: 'legal link', sub: linkSub }, ['nav', 'legal']))}
                    </section>
                </div>
            </div>
        </div>`;
    }

    // ------------------------------------------------------------------ theme

    function Theme({ app }) {
        const st = app.state, T = st.theme;
        const r = T.radius, btnR = T.button === 'Pill' ? 999 : T.button === 'Rounded' ? 6 : 0, sc2 = (T.headingScale || 100) / 100;
        const shadow = T.shadow === 'None' ? 'none' : T.shadow === 'Strong' ? '0 16px 36px rgba(16,38,59,.18)' : '0 8px 22px rgba(16,38,59,.08)';
        const linkC = T.link === 'Primary' ? T.primary : T.accent;
        const thH3 = `font-size:${Math.round(18 * sc2)}px;font-weight:${T.headingWeight};margin:4px 0 6px`;
        const cards = [{ k: '01', t: 'Bulk storage', b: '50M litres in the Calabar FTZ.' }, { k: '02', t: 'Logistics', b: 'A dedicated fleet of 13+ trucks.' }, { k: '03', t: 'Retail', b: 'Outlets across five states and the FCT.' }];
        return html`<div style=${css('flex:1;display:grid;grid-template-columns:360px minmax(0,1fr);min-height:0')}>
            <div style=${css('border-right:1px solid #e3e7ea;background:#fff;overflow:auto;display:flex;flex-direction:column')}>
                <div style=${css('padding:18px 20px;border-bottom:1px solid #e3e7ea')}><h1 style=${css('font-size:22px;font-weight:600;margin:0')}>Theme</h1><div style=${css('font-size:13px;color:#5b6977;margin-top:2px')}>Brand colours, fonts and shapes — applied site-wide.</div></div>
                <div style=${css('padding:18px 20px;display:grid;gap:22px;flex:1;grid-template-columns:minmax(0,1fr)')}>
                    ${BOOT.themeFields.map((g) => html`<div key=${g.group} style=${css('display:grid;gap:12px')}><div style=${css('font-size:12px;font-weight:600;color:#c97d0f')}>${g.group}</div>${g.fields.map((f) => F(app.field(f, ['theme', f.k])))}</div>`)}
                </div>
                <div style=${css('display:flex;justify-content:space-between;gap:8px;padding:12px 20px;border-top:1px solid #e3e7ea;background:#fafbfc;position:sticky;bottom:0')}><button onClick=${() => { const prev = st.theme; app.setState({ theme: window.FS.clone(BOOT.defaultTheme) }); app.flash('Theme reset to default — publish to apply', () => app.setState({ theme: prev })); }} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>Reset to default</button><button onClick=${() => app.publishTheme()} style=${css('background:#10263b;color:#fff;border:0;border-radius:6px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer')}>Publish theme</button></div>
            </div>
            <div style=${css('overflow:auto;background:#e6eaee;padding:28px')}>
                <div style=${css(`max-width:900px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 10px 30px rgba(16,38,59,.12);font-family:'${T.font}',sans-serif;color:${T.ink}`)}>
                    <div style=${css(`background:${T.primary};color:#fff;padding:48px 32px`)}>
                        <div style=${css('font-size:12px;font-weight:600;margin-bottom:10px;opacity:.9')}>${st.settings.general.siteName}</div>
                        <div style=${css(`font-size:${Math.round(34 * sc2)}px;line-height:1.15;font-weight:${T.headingWeight};letter-spacing:-.015em;max-width:560px`)}>${st.settings.general.tagline}</div>
                        <div style=${css('font-size:14px;opacity:.8;margin:10px 0 18px;max-width:420px')}>Integrated trading, storage, logistics and retail — built for dependable supply since 2013.</div>
                        <div style=${css('display:flex;gap:8px')}><span style=${css(`background:${T.accent};color:${T.primary};font-size:13px;font-weight:600;padding:11px 18px;border-radius:${btnR}px`)}>Explore our services</span><span style=${css(`border:1px solid rgba(255,255,255,.45);color:#fff;font-size:13px;font-weight:600;padding:11px 18px;border-radius:${btnR}px`)}>About us</span></div>
                    </div>
                    <div style=${css('padding:28px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px')}>${cards.map((c) => html`<div key=${c.k} style=${css(`border:1px solid #e3e7ea;border-radius:${r}px;padding:18px;box-shadow:${shadow};background:#fff`)}><div style=${css(`font-size:12px;font-weight:600;color:${T.accent}`)}>${c.k}</div><div style=${css(thH3)}>${c.t}</div><div style=${css('font-size:13px;line-height:1.55;opacity:.75')}>${c.b}</div><div style=${css(`font-size:13px;font-weight:600;color:${linkC};margin-top:12px`)}>Learn more →</div></div>`)}</div>
                    <div style=${css(`display:flex;justify-content:space-between;align-items:center;gap:12px;padding:24px 32px;background:${T.surface}`)}><div style=${css(thH3)}>Stay informed</div><span style=${css(`background:${T.primary};color:#fff;font-size:13px;font-weight:600;padding:11px 18px;border-radius:${btnR}px`)}>Subscribe</span></div>
                </div>
                <div style=${css('text-align:center;font-size:12px;color:#5b6977;margin-top:12px')}>Live preview · changes apply to every page when published</div>
            </div>
        </div>`;
    }

    // --------------------------------------------------------------- settings

    function Settings({ app }) {
        const st = app.state;
        const cur = BOOT.settingsTabs.find((t) => t.k === st.setTab) || BOOT.settingsTabs[0];
        return html`<div style=${css('flex:1;display:grid;grid-template-columns:240px minmax(0,1fr);min-height:0')}>
            <div style=${css('border-right:1px solid #e3e7ea;background:#fff;padding:18px 12px;overflow:auto')}>
                <h1 style=${css('font-size:22px;font-weight:600;margin:0 8px 14px')}>Settings</h1>
                <div style=${css('display:grid;gap:2px')}>${BOOT.settingsTabs.map((t) => html`<button key=${t.k} onClick=${() => app.setState({ setTab: t.k })} style=${css(`text-align:left;border:0;border-radius:6px;padding:9px 10px;font-size:14px;cursor:pointer;background:${t.k === cur.k ? '#f4f6f8' : 'transparent'};color:#10263b;font-weight:${t.k === cur.k ? 600 : 400};border-left:3px solid ${t.k === cur.k ? '#e59b23' : 'transparent'}`)}>${t.label}</button>`)}</div>
            </div>
            <div style=${css('overflow:auto;padding:24px 32px')}>
                <div style=${css('max-width:720px;background:#fff;border:1px solid #e3e7ea;border-radius:10px')}>
                    <div style=${css('padding:18px 22px;border-bottom:1px solid #edf0f2')}><h2 style=${css('font-size:17px;margin:0')}>${cur.label}</h2><div style=${css('font-size:13px;color:#5b6977;margin-top:3px')}>${cur.desc}</div></div>
                    <div style=${css('padding:22px;display:grid;gap:18px;grid-template-columns:minmax(0,1fr)')}>${cur.fields.map((f) => F(app.field(f, ['settings', cur.k, f.k]), cur.k + f.k))}</div>
                    <div style=${css('display:flex;justify-content:flex-end;gap:8px;padding:12px 22px;border-top:1px solid #edf0f2;background:#fafbfc;border-radius:0 0 10px 10px')}><button onClick=${() => app.saveSettings(cur.k)} style=${css('background:#10263b;color:#fff;border:0;border-radius:6px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer')}>Save settings</button></div>
                </div>
            </div>
        </div>`;
    }

    // ------------------------------------------------------------------ users

    function Users({ app }) {
        const st = app.state, roles = BOOT.roles;
        const cols = 'display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1.4fr) 170px 110px 100px 40px;gap:12px';
        return html`<div style=${css('flex:1;overflow:auto')}>
            <header style=${css(header)}>
                <div><h1 style=${css('font-size:22px;font-weight:600;margin:0')}>Users & roles</h1><div style=${css('font-size:13px;color:#5b6977;margin-top:2px')}>Who can sign in, and what each role is allowed to do.</div></div>
                <form onSubmit=${(e) => app.invite(e)} style=${css('display:flex;gap:6px;flex-wrap:wrap')}>
                    <input value=${st.inviteEmail} onChange=${(e) => app.setState({ inviteEmail: e.target.value })} type="email" required placeholder="colleague@fynefield.com" style=${css('width:230px;border:1px solid #d3d9de;border-radius:6px;padding:8px 11px;font-size:13px;outline:none')} />
                    <select value=${st.inviteRole} onChange=${(e) => app.setState({ inviteRole: e.target.value })} style=${css('border:1px solid #d3d9de;border-radius:6px;padding:8px;font-size:13px;background:#fff;color:#10263b')}>${roles.map((r) => html`<option key=${r} value=${r}>${r}</option>`)}</select>
                    <button type="submit" style=${css(btnDark)}>Send invite</button>
                </form>
            </header>
            <div style=${css('padding:20px 28px;display:grid;gap:20px')}>
                <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;overflow:hidden')}>
                    <div style=${css(cols + ';padding:11px 18px;background:#fafbfc;border-bottom:1px solid #e3e7ea;font-size:12px;font-weight:600;color:#5b6977')}><span>Name</span><span>Email</span><span>Role</span><span>Last active</span><span>Status</span><span></span></div>
                    ${st.users.map((u) => html`<div key=${u.id} style=${css(cols + ';align-items:center;padding:11px 18px;border-bottom:1px solid #edf0f2')}>
                        <span style=${css('display:flex;align-items:center;gap:10px;font-weight:600')}><span style=${css('width:28px;height:28px;border-radius:50%;background:#fdf1dc;color:#9a5c06;display:grid;place-items:center;font-size:11px;font-weight:700;flex-shrink:0')}>${u.name.split(/[\s.]/).map((w) => w[0]).join('').slice(0, 2).toUpperCase()}</span>${u.name}</span>
                        <span style=${css('color:#5b6977;overflow:hidden;text-overflow:ellipsis')}>${u.email}</span>
                        <select value=${u.role} onChange=${(e) => app.setRole(u, e.target.value)} style=${css('border:1px solid #d3d9de;border-radius:6px;padding:6px 8px;font-size:13px;background:#fff;color:#10263b')}>${roles.map((r) => html`<option key=${r} value=${r}>${r}</option>`)}</select>
                        <span style=${css('font-size:13px;color:#5b6977')}>${u.last}</span>
                        <span style=${css(stPill(u.status))}>${u.status}</span>
                        <button onClick=${() => app.removeUser(u)} title="Remove user" style=${css('background:transparent;border:0;cursor:pointer;color:#b4413b')}>✕</button>
                    </div>`)}
                </div>
                <div style=${css('background:#fff;border:1px solid #e3e7ea;border-radius:10px;overflow:hidden')}>
                    <div style=${css('padding:14px 18px;border-bottom:1px solid #edf0f2')}><h2 style=${css('font-size:15px;margin:0')}>Role permissions</h2><div style=${css('font-size:12px;color:#5b6977;margin-top:2px')}>Click a cell to grant or remove a permission.</div></div>
                    <div style=${css('display:grid;grid-template-columns:minmax(0,1.6fr) repeat(4,minmax(0,1fr));gap:0;font-size:13px')}>
                        <span style=${css('padding:10px 18px;background:#fafbfc;font-weight:600;color:#5b6977;font-size:12px')}>Permission</span>
                        ${roles.map((r) => html`<span key=${r} style=${css('padding:10px;background:#fafbfc;font-weight:600;color:#5b6977;font-size:12px;text-align:center')}>${r}</span>`)}
                        ${st.perms.map((p, i) => html`<${React.Fragment} key=${p.k}>
                            <span style=${css('padding:10px 18px;border-top:1px solid #edf0f2')}>${p.label}</span>
                            ${p.v.map((on, j) => html`<button key=${j} onClick=${() => app.togglePerm(i, j)} style=${css(`border:0;border-top:1px solid #edf0f2;background:${on ? '#f2faf5' : '#fff'};color:${on ? '#2f8a57' : '#b9c2ca'};font-weight:700;cursor:pointer;padding:10px;font-size:14px`)}>${on ? '✓' : '—'}</button>`)}
                        <//>`)}
                    </div>
                </div>
            </div>
        </div>`;
    }

    // --------------------------------------------------------------- overlays

    function Palette({ app }) {
        const st = app.state;
        const names = { today: 'Today', inbox: 'Inbox', pages: 'Pages', news: 'News', services: 'Services', team: 'Team', locations: 'Locations', media: 'Media', nav: 'Navigation', theme: 'Theme', settings: 'Settings', users: 'Users & roles' };
        const perm = { inbox: 'inquiries', pages: 'edit_pages', news: 'write_news', services: 'edit_pages', team: 'edit_pages', locations: 'edit_pages', media: 'manage_media', nav: 'nav_theme', theme: 'nav_theme', settings: 'settings', users: 'manage_users' };
        const acts = [
            ...Object.keys(names).filter((s) => ! perm[s] || app.can(perm[s])).map((s) => ({ label: 'Go to ' + names[s], kind: 'Navigate', run: () => app.go(s) })),
            ...(app.can('edit_pages') ? st.pages.map((p) => ({ label: 'Edit page: ' + p.title, kind: 'Page', run: () => app.go('editor', { pageId: p.id, sel: 0, tab: 'Section' }) })) : []),
            ...['news', 'services', 'team', 'locations'].filter((k) => app.can(perm[k])).flatMap((k) => st.colls[k].map((x) => ({ label: x[BOOT.collections[k].titleKey], kind: BOOT.collections[k].label, run: () => app.go(k, { itemId: app.itemKey(x) }) }))),
            ...(app.can('settings') ? BOOT.settingsTabs.map((t) => ({ label: 'Settings: ' + t.label, kind: 'Settings', run: () => app.go('settings', { setTab: t.k }) })) : []),
            ...(app.can('write_news') ? [{ label: 'Write a news article', kind: 'Create', run: () => { app.go('news'); setTimeout(() => app.newItem('news'), 0); } }] : []),
            ...(app.can('manage_media') ? [{ label: 'Upload images', kind: 'Create', run: () => { app.go('media'); setTimeout(() => app.fileRef.current.click(), 50); } }] : []),
        ];
        const q = (st.query || '').toLowerCase();
        const results = acts.filter((a) => ! q || String(a.label).toLowerCase().includes(q)).slice(0, 9);
        return html`<div onClick=${() => app.setState({ palette: false })} style=${css('position:absolute;inset:0;background:rgba(10,29,47,.45);z-index:50;display:flex;justify-content:center;align-items:flex-start;padding-top:110px')}>
            <div onClick=${(e) => e.stopPropagation()} style=${css('width:min(620px,90%);background:#fff;border-radius:12px;box-shadow:0 30px 80px rgba(10,29,47,.35);overflow:hidden')}>
                <input autoFocus value=${st.query} onChange=${(e) => app.setState({ query: e.target.value })} onKeyDown=${(e) => { if (e.key === 'Enter' && results[0]) results[0].run(); }} placeholder="Search pages, news, settings — or type a command" style=${css('width:100%;border:0;border-bottom:1px solid #e3e7ea;padding:18px 20px;font-size:16px;outline:none;color:#10263b')} />
                <div style=${css('max-height:380px;overflow:auto;padding:6px')}>
                    ${results.map((r, i) => html`<button key=${i} onClick=${r.run} className="sf-hover-menu" style=${css('display:flex;justify-content:space-between;align-items:center;width:100%;text-align:left;background:transparent;border:0;border-radius:6px;padding:11px 14px;cursor:pointer;font-size:14px;color:#10263b')}><span>${r.label}</span><span style=${css('font-size:11px;font-weight:600;color:#8a97a3')}>${r.kind}</span></button>`)}
                </div>
                <div style=${css('padding:9px 20px;border-top:1px solid #e3e7ea;font-size:12px;color:#8a97a3')}>↵ to open · Esc to close</div>
            </div>
        </div>`;
    }

    function Picker({ app }) {
        const st = app.state;
        return html`<div onClick=${() => app.setState({ picker: null })} style=${css('position:absolute;inset:0;background:rgba(10,29,47,.45);z-index:55;display:grid;place-items:center')}>
            <div onClick=${(e) => e.stopPropagation()} style=${css('width:min(760px,92%);max-height:80vh;background:#fff;border-radius:12px;box-shadow:0 30px 80px rgba(10,29,47,.35);display:flex;flex-direction:column;overflow:hidden')}>
                <div style=${css('display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid #e3e7ea')}><strong style=${css('font-size:15px')}>Choose an image</strong><div style=${css('display:flex;gap:8px')}><button onClick=${() => app.fileRef.current.click()} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:7px 12px;font-size:13px;font-weight:600;cursor:pointer;color:#10263b')}>Upload new</button><button onClick=${() => app.setState({ picker: null })} style=${css('background:transparent;border:0;font-size:16px;cursor:pointer;color:#5b6977')}>✕</button></div></div>
                <div style=${css('overflow:auto;padding:16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px')}>
                    ${st.media.map((m) => html`<button key=${m.id} onClick=${() => { app.setPath(st.picker, m.src); app.setState({ picker: null }); app.flash(`Image set: ${m.name}`); }} className="sf-hover-pick" style=${css('padding:0;border:1px solid #e3e7ea;border-radius:8px;overflow:hidden;background:#fff;cursor:pointer;text-align:left')}><img src=${m.src} alt="" style=${css('width:100%;aspect-ratio:4/3;object-fit:cover;display:block')} /><span style=${css('display:block;padding:7px 9px;font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#10263b')}>${m.name}</span></button>`)}
                </div>
            </div>
        </div>`;
    }

    // ---------------------------------------------------------------- layout

    window.FS.renderStudio = (app) => {
        const st = app.state, sc = st.screen;
        const screen = sc === 'today' ? Today : sc === 'pages' ? Pages : sc === 'editor' ? Editor : ['news', 'services', 'team', 'locations'].includes(sc) ? Collection : sc === 'media' ? Media : sc === 'inbox' ? Inbox : sc === 'nav' ? Navigation : sc === 'theme' ? Theme : sc === 'settings' ? Settings : sc === 'users' ? Users : Today;
        return html`<div data-screen-label="CMS" style=${css('display:flex;height:100vh;min-height:720px;background:#f4f6f8;font-size:14px;position:relative;overflow:hidden')}>
            <${Sidebar} app=${app} />
            <main style=${css('flex:1;min-width:0;display:flex;flex-direction:column;overflow:hidden')}><${screen} app=${app} /></main>
            <input ref=${app.fileRef} type="file" accept="image/jpeg,image/png,image/webp" multiple onChange=${(e) => { app.upload(e.target.files); e.target.value = ''; }} style=${css('display:none')} />
            <input ref=${app.replaceRef} type="file" accept="image/jpeg,image/png,image/webp" onChange=${(e) => { app.replaceMedia(e.target.files[0]); e.target.value = ''; }} style=${css('display:none')} />
            ${st.picker && html`<${Picker} app=${app} />`}
            ${st.palette && html`<${Palette} app=${app} />`}
            ${st.toast && html`<div style=${css('position:absolute;bottom:22px;left:50%;transform:translateX(-50%);background:#10263b;color:#fff;padding:11px 18px;border-radius:8px;font-size:14px;z-index:60;box-shadow:0 10px 30px rgba(10,29,47,.3);display:flex;gap:16px;align-items:center')}><span>${st.toast}</span>${app.undoAction && html`<button onClick=${() => app.undo()} style=${css('background:transparent;border:0;color:#f0ad3a;font-weight:700;cursor:pointer')}>Undo</button>`}</div>`}
        </div>`;
    };
})();
