/* Fynefield Studio — application state and behaviour (port of the design's Component class). */
(() => {
    const { html, css, BOOT, api, clone } = window.FS;
    const PAGE_FIELDS = ['title', 'slug', 'template', 'inNav', 'headerStyle', 'visibility', 'footerCta', 'seoTitle', 'seoDesc', 'shareImage', 'sections', 'password'];
    const SCREENS = { Today: 'today', Pages: 'pages', News: 'news', Services: 'services', Team: 'team', Locations: 'locations', Media: 'media', Inbox: 'inbox', Navigation: 'nav', Theme: 'theme', Settings: 'settings', Users: 'users' };

    class App extends React.Component {
        constructor(props) {
            super(props);
            const hash = decodeURIComponent(window.location.hash.slice(1));
            const firstInquiry = BOOT.inquiries[0];
            this.state = {
                screen: Object.values(SCREENS).includes(hash) ? hash : 'today',
                pages: clone(BOOT.pages), colls: clone(BOOT.colls), media: clone(BOOT.media), nav: clone(BOOT.nav), theme: clone(BOOT.theme), settings: clone(BOOT.settings),
                users: clone(BOOT.users), perms: clone(BOOT.perms), inquiries: clone(BOOT.inquiries), linkOptions: BOOT.linkOptions, serviceOptions: BOOT.serviceOptions,
                navSaved: JSON.stringify(BOOT.nav), themeSaved: JSON.stringify(BOOT.theme), settingsSaved: JSON.stringify(BOOT.settings),
                pageId: (BOOT.pages[0] || {}).id, sel: 0, tab: 'Section', device: 'Desktop', itemId: null, collQuery: '', pageQuery: '', mediaSel: (BOOT.media[0] || {}).id, folder: 'All',
                setTab: 'general', inq: firstInquiry ? firstInquiry.id : null, filter: 'All', reply: '', inviteEmail: '', inviteRole: 'Editor', palette: false, query: '', toast: null, picker: null,
                vw: window.innerWidth, saveState: {}, versions: null, dirtyItems: {}, scheduleAt: '',
            };
            this.fileRef = React.createRef();
            this.replaceRef = React.createRef();
            this.timers = {};
            this.pendingDeletes = [];
        }

        componentDidMount() {
            this.onKey = (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); this.setState({ palette: true, query: '' }); }
                if (e.key === 'Escape') this.setState({ palette: false, publishOpen: false, picker: null });
            };
            this.onResize = () => this.setState({ vw: window.innerWidth });
            // Deletes wait for the Undo window and drafts save after a pause; warn before leaving mid-way.
            this.onUnload = (e) => {
                const saving = Object.values(this.state.saveState).includes('saving');
                if (! this.pendingDeletes.length && ! saving) return;
                e.preventDefault();
                e.returnValue = '';
            };
            window.addEventListener('keydown', this.onKey);
            window.addEventListener('resize', this.onResize);
            window.addEventListener('beforeunload', this.onUnload);
        }

        componentWillUnmount() {
            window.removeEventListener('keydown', this.onKey);
            window.removeEventListener('resize', this.onResize);
            window.removeEventListener('beforeunload', this.onUnload);
        }

        can(permission) { return !! BOOT.me.perms[permission]; }

        // ---------------------------------------------------------- feedback

        flash(msg, undo) {
            clearTimeout(this.toastTimer);
            this.undoAction = undo || null;
            this.setState({ toast: msg });
            this.toastTimer = setTimeout(() => { this.setState({ toast: null }); this.undoAction = null; }, 4000);
        }

        fail(error) { this.flash(error && error.message ? error.message : String(error)); }

        undo() {
            const action = this.undoAction;
            this.undoAction = null;
            this.setState({ toast: null });
            if (action) action();
        }

        /** Removes something now, but waits for the Undo window before telling the server. */
        deferDelete(message, restore, commit) {
            const entry = { done: false };
            entry.commit = () => {
                if (entry.done) return;
                entry.done = true;
                this.pendingDeletes = this.pendingDeletes.filter((d) => d !== entry);
                commit().catch((error) => { restore(); this.fail(error); });
            };
            entry.timer = setTimeout(entry.commit, 4200);
            this.pendingDeletes.push(entry);
            this.flash(message, () => { entry.done = true; clearTimeout(entry.timer); this.pendingDeletes = this.pendingDeletes.filter((d) => d !== entry); restore(); });
        }

        go(screen, extra) {
            if (window.location.hash !== '#' + screen) history.replaceState(null, '', '#' + screen);
            this.setState({ screen, palette: false, publishOpen: false, libraryOpen: false, itemId: null, collQuery: '', versions: null, history: false, ...(extra || {}) });
        }

        // ------------------------------------------------------------- state

        get(path) { let o = this.state; for (const k of path) { if (o == null) return undefined; o = o[k]; } return o; }

        setPath(path, value) {
            this.setState((s) => {
                const root = path[0];
                if (path.length === 1) return { [root]: value };
                const copy = clone(s[root]);
                let o = copy;
                for (let i = 1; i < path.length - 1; i++) o = o[path[i]];
                o[path[path.length - 1]] = value;
                return { [root]: copy };
            }, () => this.changed(path));
        }

        /** Persists edits that save as you go: page drafts, media details and inquiry notes. */
        changed(path) {
            const [root, index] = path;
            if (root === 'pages') this.queueDraft(this.state.pages[index]);
            if (root === 'media' && this.state.media[index]) this.debounce('media' + this.state.media[index].id, 600, () => {
                const m = this.state.media[index];
                if (m) api.post(`/api/media/${m.id}`, { alt: m.alt, caption: m.caption, folder: m.folder }).catch((e) => this.fail(e));
            });
            if (root === 'colls') this.setState((s) => ({ dirtyItems: { ...s.dirtyItems, [path[1] + ':' + s.colls[path[1]][path[2]].id]: true } }));
        }

        debounce(key, wait, fn) { clearTimeout(this.timers[key]); this.timers[key] = setTimeout(fn, wait); }

        queueDraft(page) {
            if (! page) return;
            this.setState((s) => ({ saveState: { ...s.saveState, [page.id]: 'saving' } }));
            this.debounce('page' + page.id, 700, () => {
                const current = this.state.pages.find((p) => p.id === page.id);
                if (! current) return;
                const body = {};
                PAGE_FIELDS.forEach((k) => { if (current[k] !== undefined) body[k] = current[k]; });
                api.post(`/api/pages/${page.id}/draft`, body)
                    .then(() => this.setState((s) => ({ saveState: { ...s.saveState, [page.id]: 'saved' }, pages: s.pages.map((p) => (p.id === page.id ? { ...p, updated: 'just now', by: 'You', hasDraft: true, password: '' } : p)) })))
                    .catch((error) => { this.setState((s) => ({ saveState: { ...s.saveState, [page.id]: 'error' } })); this.fail(error); });
            });
        }

        /** Builds the props for one field component bound to a state path (port of the design's field()). */
        field(def, path) {
            const t = def.type || 'text';
            let v = this.get(path);
            if (v === undefined || v === null) v = t === 'toggle' ? false : t === 'list' ? [] : t === 'range' ? (def.min || 0) : '';
            const set = (x) => this.setPath(path, x);
            const o = { type: t, label: def.label, hint: def.hint || '', placeholder: def.placeholder || '', options: def.options || [], value: v, rows: def.rows, onText: def.onText, offText: def.offText, unit: def.unit || '', min: def.min, max: def.max, step: def.step || 1 };
            o.onChange = (e) => set(t === 'range' ? +e.target.value : e.target.value);
            o.onToggle = () => set(! v);
            if (t === 'image') { o.onPick = () => this.setState({ picker: path }); o.onClear = () => set(''); }
            if (t === 'link') {
                o.options = this.state.linkOptions;
                const urlPath = [...path.slice(0, -1), path[path.length - 1] + 'Url'];
                o.url = this.get(urlPath) || '';
                o.onUrl = (e) => this.setPath(urlPath, e.target.value);
            }
            if (t === 'service') o.options = this.state.serviceOptions;
            if (t === 'list') {
                const sub = def.sub || [];
                o.itemLabel = def.itemLabel || 'item';
                o.items = v.map((it, i) => ({
                    n: i + 1, title: (sub[0] && it[sub[0].k]) || ((sub[1] && sub[1].k === 'link' && it.link === 'tel') ? 'Phone number' : (sub[1] && it.link === 'mailto') ? 'Email address' : `${o.itemLabel} ${i + 1}`),
                    cells: sub.map((sd) => {
                        const cellPath = [...path, i, sd.k];
                        const cell = { label: sd.label, type: sd.type || 'text', value: it[sd.k] == null ? '' : it[sd.k], options: sd.type === 'link' ? this.state.linkOptions : sd.type === 'service' ? this.state.serviceOptions : (sd.options || []), onChange: (e) => this.setPath(cellPath, e.target.value) };
                        if (sd.type === 'link') { cell.url = it[sd.k + 'Url'] || ''; cell.onUrl = (e) => this.setPath([...path, i, sd.k + 'Url'], e.target.value); }
                        if (sd.type === 'image') { cell.onPick = () => this.setState({ picker: cellPath }); cell.onClear = () => this.setPath(cellPath, ''); }
                        return cell;
                    }),
                    remove: () => set(v.filter((_, j) => j !== i)),
                    up: () => { if (! i) return; const a = [...v]; [a[i - 1], a[i]] = [a[i], a[i - 1]]; set(a); },
                    down: () => { if (i >= v.length - 1) return; const a = [...v]; [a[i + 1], a[i]] = [a[i], a[i + 1]]; set(a); },
                }));
                o.onAdd = () => set([...v, Object.fromEntries(sub.map((sd) => [sd.k, sd.type === 'select' ? sd.options[0] : sd.type === 'link' ? 'url' : sd.type === 'service' ? ((this.state.serviceOptions[0] || {}).value || '') : '']))]);
            }
            return o;
        }

        // ------------------------------------------------------------- pages

        pageIdx() { return Math.max(0, this.state.pages.findIndex((p) => p.id === this.state.pageId)); }
        sections() { return this.state.pages[this.pageIdx()].sections; }
        setSections(secs, extra) { this.setPath(['pages', this.pageIdx(), 'sections'], secs); if (extra) this.setState(extra); }

        moveSection(i, d) {
            const secs = [...this.sections()], j = i + d;
            if (j < 0 || j >= secs.length) return;
            [secs[i], secs[j]] = [secs[j], secs[i]];
            this.setSections(secs, { sel: j });
        }
        duplicateSection(i) { const secs = [...this.sections()]; secs.splice(i + 1, 0, { ...clone(secs[i]), id: 's' + Date.now().toString(36) }); this.setSections(secs, { sel: i + 1 }); }
        toggleSectionHidden(i) { this.setPath(['pages', this.pageIdx(), 'sections', i, 'hidden'], ! this.sections()[i].hidden); }
        removeSection(i) {
            const prev = this.sections();
            const label = (BOOT.sectionTypes[prev[i].type] || {}).label;
            this.setSections(prev.filter((_, j) => j !== i), { sel: Math.max(0, i - 1) });
            this.flash(`Deleted “${label}” section`, () => this.setSections(prev, { sel: i }));
        }
        addSection(type) {
            const secs = [...this.sections()], sel = Math.min(this.state.sel, secs.length - 1);
            secs.splice(sel + 1, 0, { id: 's' + Date.now().toString(36), type, ...clone(BOOT.sectionCommon), ...clone(BOOT.sectionDefaults[type]) });
            this.setSections(secs, { sel: sel + 1, libraryOpen: false, tab: 'Section' });
            this.flash(`Added “${BOOT.sectionTypes[type].label}” below the selected section`);
        }

        newPage() {
            if (! this.can('edit_pages')) return this.flash('Your role cannot create pages.');
            api.post('/api/pages').then(({ page, linkOptions }) => {
                this.setState((s) => ({ pages: [...s.pages, page], linkOptions }));
                this.go('editor', { pageId: page.id, sel: 0, tab: 'Page' });
                this.flash('New draft page created');
            }).catch((e) => this.fail(e));
        }

        duplicatePage(p) {
            api.post(`/api/pages/${p.id}/duplicate`).then(({ page, linkOptions }) => {
                this.setState((s) => ({ pages: [...s.pages, page], linkOptions }));
                this.flash(`Duplicated “${p.title}”`);
            }).catch((e) => this.fail(e));
        }

        deletePage(p) {
            if (! this.can('publish_pages')) return this.flash('Your role cannot delete pages.');
            if (p.slug === '/') return this.flash('The homepage cannot be deleted.');
            const prev = this.state.pages;
            this.setState({ pages: prev.filter((x) => x.id !== p.id) });
            this.deferDelete(`Deleted “${p.title}”`, () => this.setState({ pages: prev }), () => api.post(`/api/pages/${p.id}/delete`).then(({ linkOptions }) => this.setState({ linkOptions })));
        }

        publish(action, at) {
            const page = this.state.pages[this.pageIdx()];
            const run = () => api.post(`/api/pages/${page.id}/publish`, { action, at }).then(({ page: updated, nav, linkOptions }) => {
                this.setState((s) => ({ pages: s.pages.map((p) => (p.id === updated.id ? updated : p)), nav, navSaved: JSON.stringify(nav), linkOptions, publishOpen: false, scheduleOpen: false }));
                this.flash({ publish: `“${updated.title}” is live`, schedule: `Scheduled for ${new Date(at).toLocaleString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}`, review: 'Sent for review', draft: 'Page unpublished' }[action]);
            }).catch((e) => this.fail(e));
            // Publish only after any pending autosave has reached the server.
            clearTimeout(this.timers['page' + page.id]);
            const body = {};
            PAGE_FIELDS.forEach((k) => { if (page[k] !== undefined) body[k] = page[k]; });
            api.post(`/api/pages/${page.id}/draft`, body).then(run).catch((e) => this.fail(e));
        }

        copyPreview() {
            const page = this.state.pages[this.pageIdx()];
            api.post(`/api/pages/${page.id}/preview-link`).then(({ url }) => {
                (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(() => this.flash('Private preview link copied — expires in 7 days'), () => { window.prompt('Copy this private preview link', url); });
            }).catch((e) => this.fail(e));
        }

        toggleHistory() {
            if (this.state.history) return this.setState({ history: false });
            const page = this.state.pages[this.pageIdx()];
            this.setState({ history: true, versions: null });
            api.get(`/api/pages/${page.id}/revisions`).then(({ versions }) => this.setState({ versions })).catch((e) => this.fail(e));
        }

        restoreVersion(version) {
            const page = this.state.pages[this.pageIdx()];
            const prev = this.state.pages;
            api.post(`/api/pages/${page.id}/revisions/${version.id}/restore`).then(({ page: updated }) => {
                this.setState((s) => ({ pages: s.pages.map((p) => (p.id === updated.id ? updated : p)), sel: 0 }));
                this.flash(`Restored version from ${version.when} into the draft`, () => { this.setState({ pages: prev }); this.queueDraft(prev.find((p) => p.id === page.id)); });
            }).catch((e) => this.fail(e));
        }

        // ------------------------------------------------------- collections

        newItem(key) {
            const C = BOOT.collections[key];
            const blank = { id: null, _new: 'n' + Date.now() };
            C.fields.forEach((f) => { blank[f.k] = f.type === 'toggle' ? true : f.type === 'list' ? [] : f.type === 'select' ? f.options[key === 'news' && f.k === 'status' ? 1 : 0] : ''; });
            blank[C.titleKey] = 'Untitled ' + C.singular;
            if (key === 'news') { blank.dateLabel = '—'; blank.featured = false; }
            if (key === 'services') blank.status = 'Draft';
            if (key === 'team' || key === 'locations') blank.visible = false;
            this.setState((s) => ({ colls: { ...s.colls, [key]: [blank, ...s.colls[key]] }, itemId: blank._new, dirtyItems: { ...s.dirtyItems, [key + ':' + null]: true } }));
            this.flash(`New ${C.singular} created as a draft — save to keep it`);
        }

        itemKey(x) { return x.id || x._new; }

        saveItem(key) {
            const items = this.state.colls[key];
            const idx = items.findIndex((x) => this.itemKey(x) === this.state.itemId);
            const item = items[idx];
            if (! item) return;
            const C = BOOT.collections[key];
            api.post(`/api/collections/${key}` + (item.id ? `/${item.id}` : ''), item).then(({ item: saved, linkOptions, serviceOptions }) => {
                this.setState((s) => {
                    const list = [...s.colls[key]];
                    const at = list.findIndex((x) => this.itemKey(x) === this.itemKey(item));
                    if (at >= 0) list[at] = saved;
                    const dirty = { ...s.dirtyItems };
                    delete dirty[key + ':' + item.id];
                    return { colls: { ...s.colls, [key]: list }, itemId: saved.id, linkOptions, serviceOptions, dirtyItems: dirty };
                });
                const live = saved.status === 'Published' || saved.visible === true;
                this.flash(`Saved “${saved[C.titleKey] || 'Untitled'}”${live ? ' — live on the site' : ''}`);
            }).catch((e) => this.fail(e));
        }

        dupItem(key) {
            const items = this.state.colls[key];
            const idx = items.findIndex((x) => this.itemKey(x) === this.state.itemId);
            const C = BOOT.collections[key];
            const copy = { ...clone(items[idx]), id: null, _new: 'n' + Date.now() };
            copy[C.titleKey] += ' (copy)';
            if (copy.slug) copy.slug += '-copy';
            if (key === 'news' || key === 'services') copy.status = 'Draft';
            if (key === 'team' || key === 'locations') copy.visible = false;
            const list = [...items]; list.splice(idx + 1, 0, copy);
            this.setState((s) => ({ colls: { ...s.colls, [key]: list }, itemId: copy._new }));
            this.flash(`Duplicated as a draft — save to keep it`);
        }

        delItem(key) {
            const items = this.state.colls[key];
            const item = items.find((x) => this.itemKey(x) === this.state.itemId);
            const C = BOOT.collections[key];
            const prev = this.state.colls;
            this.setState((s) => ({ colls: { ...s.colls, [key]: s.colls[key].filter((x) => x !== item) }, itemId: null }));
            if (! item.id) return this.flash(`Discarded “${item[C.titleKey]}”`);
            this.deferDelete(`Deleted “${item[C.titleKey]}”`, () => this.setState({ colls: prev }), () => api.post(`/api/collections/${key}/${item.id}/delete`).then(({ linkOptions }) => this.setState({ linkOptions })));
        }

        moveItem(key, i, d) {
            const items = [...this.state.colls[key]], j = i + d;
            if (j < 0 || j >= items.length) return;
            [items[i], items[j]] = [items[j], items[i]];
            this.setState((s) => ({ colls: { ...s.colls, [key]: items } }));
            if (key !== 'news') api.post(`/api/collections/${key}/reorder`, { ids: items.filter((x) => x.id).map((x) => x.id) }).catch((e) => this.fail(e));
        }

        // ------------------------------------------------------------- media

        upload(files) {
            const list = [...files];
            if (! list.length) return;
            const form = new FormData();
            list.forEach((file) => form.append('files[]', file));
            api.upload('/api/media', form).then(({ media, errors }) => {
                this.setState((s) => ({ media: [...media, ...s.media], mediaSel: media[0].id }));
                if (this.state.picker && media[0]) { this.setPath(this.state.picker, media[0].src); this.setState({ picker: null }); }
                this.flash(errors && errors.length ? errors[0] : `Uploaded ${media.length === 1 ? media[0].name : media.length + ' images'}`);
            }).catch((e) => this.fail(e));
        }

        replaceMedia(file) {
            const cm = this.state.media.find((m) => m.id === this.state.mediaSel);
            if (! cm || ! file) return;
            const form = new FormData();
            form.append('file', file);
            api.upload(`/api/media/${cm.id}/replace`, form).then(({ media, previous }) => {
                const swap = (value) => (Array.isArray(value) ? value.map(swap) : value && typeof value === 'object' ? Object.fromEntries(Object.entries(value).map(([k, v]) => [k, swap(v)])) : value === previous ? media.src : value);
                this.setState((s) => ({ media: s.media.map((m) => (m.id === media.id ? media : m)), pages: swap(s.pages), colls: swap(s.colls), settings: swap(s.settings) }));
                this.flash(`Replaced with ${media.name} everywhere it was used`);
            }).catch((e) => this.fail(e));
        }

        deleteMedia() {
            const cm = this.state.media.find((m) => m.id === this.state.mediaSel);
            if (! cm) return;
            if (cm.used && cm.used.length) return this.flash(`In use on ${cm.used.length} place(s) — replace it there first`);
            const prev = this.state.media;
            this.setState({ media: prev.filter((m) => m.id !== cm.id), mediaSel: (prev.find((m) => m.id !== cm.id) || {}).id });
            this.deferDelete(`Deleted ${cm.name}`, () => this.setState({ media: prev, mediaSel: cm.id }), () => api.post(`/api/media/${cm.id}/delete`));
        }

        // ----------------------------------------------------------- inquiries

        updateInquiry(id, changes, message, undoable) {
            const prev = this.state.inquiries;
            this.setState((s) => ({ inquiries: s.inquiries.map((q) => (q.id === id ? { ...q, ...changes } : q)) }));
            const save = () => api.post(`/api/inquiries/${id}`, changes).catch((e) => { this.setState({ inquiries: prev }); this.fail(e); });
            if (changes.notes !== undefined && Object.keys(changes).length === 1) return this.debounce('notes' + id, 700, save);
            save();
            if (message) this.flash(message, undoable ? () => { const before = prev.find((q) => q.id === id); this.setState({ inquiries: prev }); api.post(`/api/inquiries/${id}`, { status: before.status }).catch((e) => this.fail(e)); } : null);
        }

        sendReply() {
            const cur = this.state.inquiries.find((q) => q.id === this.state.inq);
            if (! this.state.reply.trim()) return this.flash('Write a reply first');
            api.post(`/api/inquiries/${cur.id}/reply`, { body: this.state.reply }).then(({ status, inquiry }) => {
                this.setState((s) => ({ reply: '', inquiries: s.inquiries.map((q) => (q.id === cur.id && inquiry ? inquiry : q)) }));
                this.flash(status === 'sent' ? `Reply sent to ${cur.email}` : status === 'not_configured' ? 'Reply saved to this inquiry — email is not configured yet, so it was not sent' : 'Reply saved, but the email could not be sent');
            }).catch((e) => this.fail(e));
        }

        // ----------------------------------------------- navigation, theme, settings

        publishNav() {
            api.post('/api/nav', this.state.nav).then(({ nav }) => { this.setState({ nav, navSaved: JSON.stringify(nav) }); this.flash('Navigation published to all pages'); }).catch((e) => this.fail(e));
        }

        publishTheme() {
            api.post('/api/theme', this.state.theme).then(({ theme }) => { this.setState({ theme, themeSaved: JSON.stringify(theme) }); this.flash('Theme published — every page updated'); }).catch((e) => this.fail(e));
        }

        saveSettings(tab) {
            const label = BOOT.settingsTabs.find((t) => t.k === tab).label;
            api.post(`/api/settings/${tab}`, this.state.settings[tab]).then(({ settings }) => {
                this.setState((s) => {
                    const next = { ...s.settings, [tab]: { ...settings, ...(tab === 'advanced' ? { redirects: s.settings.advanced.redirects } : {}), ...(tab === 'integrations' ? { smtp: s.settings.integrations.smtp } : {}) } };
                    return { settings: next, settingsSaved: JSON.stringify(next) };
                });
                this.flash(`${label} saved`);
            }).catch((e) => this.fail(e));
        }

        // ------------------------------------------------------- users & roles

        invite(e) {
            e.preventDefault();
            api.post('/api/users', { email: this.state.inviteEmail, role: this.state.inviteRole }).then(({ users, sent, link }) => {
                const email = this.state.inviteEmail;
                this.setState({ users, inviteEmail: '' });
                if (sent) return this.flash(`Invite sent to ${email}`);
                (navigator.clipboard && link ? navigator.clipboard.writeText(link) : Promise.reject()).then(() => this.flash('Email is not configured — the invite link was copied for you to send'), () => window.prompt('Email is not configured. Send this invite link yourself:', link));
            }).catch((err) => this.fail(err));
        }

        setRole(u, role) {
            api.post(`/api/users/${u.id}`, { role }).then(({ users }) => { this.setState({ users }); this.flash(`${u.name} is now ${role}`); }).catch((e) => this.fail(e));
        }

        removeUser(u) {
            const admins = this.state.users.filter((x) => x.role === 'Administrator');
            if (u.role === 'Administrator' && admins.length < 2) return this.flash('You need at least one administrator');
            if (u.id === BOOT.me.id) return this.flash('You cannot remove your own account');
            const prev = this.state.users;
            this.setState({ users: prev.filter((x) => x.id !== u.id) });
            this.deferDelete(`Removed ${u.name}`, () => this.setState({ users: prev }), () => api.post(`/api/users/${u.id}/delete`).then(({ users }) => this.setState({ users })));
        }

        togglePerm(i, j) {
            if (j === 0) return this.flash('Administrators always have every permission');
            const perms = clone(this.state.perms);
            perms[i].v[j] = perms[i].v[j] ? 0 : 1;
            this.setState({ perms });
            api.post('/api/perms', Object.fromEntries(perms.map((p) => [p.k, p.v]))).catch((e) => { this.setState({ perms: this.state.perms }); this.fail(e); });
        }

        // ------------------------------------------------------------ render

        render() {
            return window.FS.renderStudio(this);
        }
    }

    window.FS.App = App;
    window.FS.SCREENS = SCREENS;
    ReactDOM.createRoot(document.getElementById('studio')).render(React.createElement(App));
})();
