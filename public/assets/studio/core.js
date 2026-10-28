/* Fynefield Studio — shared core: API client, style helpers and the field component.
   Markup and inline styles are ported from the "Fynefield CMS" Claude Design export. */
(() => {
    const html = htm.bind(React.createElement);
    const BOOT = JSON.parse(document.getElementById('studio-boot').textContent);

    // The design writes styles as CSS strings; React wants objects. Converted once and cached.
    const cssCache = new Map();
    const css = (text) => {
        if (! text) return undefined;
        if (cssCache.has(text)) return cssCache.get(text);
        const out = {};
        String(text).split(';').forEach((rule) => {
            const i = rule.indexOf(':');
            if (i < 1) return;
            const prop = rule.slice(0, i).trim();
            const value = rule.slice(i + 1).trim();
            if (! prop || value === '') return;
            const key = prop.startsWith('--') ? prop : prop.replace(/^-(webkit|moz|ms)-/, (m, p) => p.charAt(0).toUpperCase() + p.slice(1) + '-').replace(/-([a-z])/g, (m, c) => c.toUpperCase());
            out[key] = value;
        });
        cssCache.set(text, out);
        return out;
    };

    const clone = (value) => JSON.parse(JSON.stringify(value));

    // Requests run one at a time: the server issues a fresh CSRF token with every response.
    const api = {
        csrf: BOOT.csrf,
        queue: Promise.resolve(),
        call(method, url, body, isForm) {
            const run = async () => {
                const headers = { 'X-Requested-With': 'XMLHttpRequest', [BOOT.csrfHeader]: api.csrf };
                let payload;
                if (isForm) payload = body;
                else if (body !== undefined) { headers['Content-Type'] = 'application/json'; payload = JSON.stringify(body); }
                const response = await fetch(BOOT.base + url, { method, headers, body: payload, credentials: 'same-origin' });
                let data = {};
                try { data = await response.json(); } catch (_) { /* Non-JSON responses fall through to the status check. */ }
                if (data.csrf) api.csrf = data.csrf;
                if (response.status === 401) { window.location.href = BOOT.base + '/login'; throw new Error('Your session has ended.'); }
                if (! response.ok) throw new Error(data.error || `Something went wrong (${response.status}).`);
                return data;
            };
            const result = api.queue.then(run, run);
            api.queue = result.catch(() => {});
            return result;
        },
        get: (url) => api.call('GET', url),
        post: (url, body) => api.call('POST', url, body ?? {}),
        upload: (url, form) => api.call('POST', url, form, true),
    };

    const pill = (c, b) => `font-size:11px;font-weight:600;padding:3px 9px;border-radius:999px;color:${c};background:${b};justify-self:start;white-space:nowrap`;
    const stPill = (s) => s === 'Published' || s === 'Active' || s === true || s === 'Visible' ? pill('#2f8a57', '#e3f4e7') : s === 'Scheduled' || s === 'Invited' ? pill('#1e6c8b', '#e3f0f5') : s === 'In review' ? pill('#6a3f96', '#efe6f5') : pill('#5b6977', '#edf0f2');
    const statusMeta = { new: ['New', '#9a5c06', '#fdf1dc'], in_progress: ['In progress', '#1e6c8b', '#e3f0f5'], resolved: ['Resolved', '#2f8a57', '#e3f4e7'] };

    const optionValue = (o) => (o && typeof o === 'object' ? o.value : o);
    const optionLabel = (o) => (o && typeof o === 'object' ? o.label : o);

    const inputStyle = 'width:100%;border:1px solid #d3d9de;border-radius:6px;padding:9px 11px;font-size:14px;outline:none;background:#fff;color:#10263b';
    const cellStyle = 'width:100%;border:1px solid #d3d9de;border-radius:6px;padding:7px 9px;font-size:13px;outline:none;background:#fff;color:#10263b';

    function ImageControl({ value, onPick, onClear, compact }) {
        const has = !! value;
        return html`<div style=${css(`display:flex;gap:${compact ? 8 : 12}px;align-items:center;border:1px solid #d3d9de;border-radius:8px;padding:${compact ? 6 : 8}px;background:#fff`)}>
            ${has ? html`<img src=${value} alt="" style=${css(`width:${compact ? 52 : 72}px;height:${compact ? 38 : 52}px;object-fit:cover;border-radius:5px;display:block;flex-shrink:0;background:#edf0f2`)} />`
                : html`<div style=${css(`width:${compact ? 52 : 72}px;height:${compact ? 38 : 52}px;border-radius:5px;flex-shrink:0;background:repeating-linear-gradient(45deg,#edf0f2 0 6px,#f6f7f9 6px 12px);display:grid;place-items:center;font-size:11px;color:#8a97a3`)}>No image</div>`}
            <div style=${css('flex:1;min-width:0;font-size:12px;color:#5b6977;overflow:hidden;text-overflow:ellipsis;white-space:nowrap')}>${has ? String(value).split('/').pop() : 'Choose an image from the media library'}</div>
            <button type="button" onClick=${onPick} style=${css('background:#fff;border:1px solid #d3d9de;border-radius:6px;padding:7px 10px;font-size:12px;font-weight:600;cursor:pointer;color:#10263b;white-space:nowrap')}>${has ? 'Replace' : 'Choose'}</button>
            ${has && html`<button type="button" onClick=${onClear} title="Remove image" style=${css('background:transparent;border:0;color:#8a97a3;cursor:pointer;font-size:14px;padding:4px')}>✕</button>`}
        </div>`;
    }

    function LinkControl({ value, url, options, onChange, onUrl, small }) {
        const style = small ? cellStyle : inputStyle;
        return html`<div style=${css('display:grid;gap:6px')}>
            <select value=${value || 'url'} onChange=${onChange} style=${css(style + ';cursor:pointer')}>
                ${options.map((o) => html`<option key=${optionValue(o)} value=${optionValue(o)}>${optionLabel(o)}</option>`)}
            </select>
            ${(value || 'url') === 'url' && html`<input value=${url || ''} onChange=${onUrl} placeholder="https:// or /path" className="sf-focus-plain" style=${css(style)} />`}
        </div>`;
    }

    // Port of the design's CmsField component, plus link, service, date and list-image controls.
    function Field({ f }) {
        const t = f.type || 'text';
        const v = f.value;
        const val = v === undefined || v === null ? '' : v;
        const len = typeof v === 'string' ? v.length : 0;
        const hint = f.hint || (f.max && (t === 'text' || t === 'textarea') ? `${len}/${f.max}` : '');
        const on = !! v;
        let control = null;
        if (t === 'text') control = html`<input value=${val} onChange=${f.onChange} placeholder=${f.placeholder || ''} className="sf-focus" style=${css(inputStyle)} />`;
        else if (t === 'datetime') control = html`<input type="datetime-local" value=${val ? String(val).slice(0, 16).replace(' ', 'T') : ''} onChange=${f.onChange} className="sf-focus" style=${css(inputStyle)} />`;
        else if (t === 'textarea') control = html`<textarea rows=${f.rows || 4} value=${val} onChange=${f.onChange} placeholder=${f.placeholder || ''} className="sf-focus" style=${css('width:100%;border:1px solid #d3d9de;border-radius:6px;padding:9px 11px;font-size:14px;line-height:1.55;outline:none;resize:vertical;background:#fff;color:#10263b')}></textarea>`;
        else if (t === 'select' || t === 'service') control = html`<select value=${val} onChange=${f.onChange} style=${css(inputStyle + ';cursor:pointer')}>${(f.options || []).map((o) => html`<option key=${optionValue(o)} value=${optionValue(o)}>${optionLabel(o)}</option>`)}</select>`;
        else if (t === 'link') control = html`<${LinkControl} value=${val} url=${f.url} options=${f.options} onChange=${f.onChange} onUrl=${f.onUrl} />`;
        else if (t === 'toggle') control = html`<button type="button" onClick=${f.onToggle} style=${css('display:flex;align-items:center;gap:10px;background:transparent;border:0;padding:2px 0;cursor:pointer;font-size:14px;color:#4c5b69;text-align:left')}>
                <span style=${css(`position:relative;width:38px;height:22px;border-radius:999px;flex-shrink:0;transition:background .2s;background:${on ? '#2f8a57' : '#c9d0d6'}`)}><span style=${css(`position:absolute;top:3px;left:${on ? 19 : 3}px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .2s`)}></span></span>${on ? (f.onText || 'On') : (f.offText || 'Off')}
            </button>`;
        else if (t === 'color') control = html`<div style=${css('display:flex;gap:8px;align-items:center')}>
                <input type="color" value=${val} onChange=${f.onChange} style=${css('width:42px;height:38px;border:1px solid #d3d9de;border-radius:6px;padding:2px;background:#fff;cursor:pointer')} />
                <input value=${val} onChange=${f.onChange} style=${css('flex:1;min-width:0;border:1px solid #d3d9de;border-radius:6px;padding:9px 11px;font-size:14px;font-family:ui-monospace,monospace;outline:none;color:#10263b')} />
            </div>`;
        else if (t === 'range') control = html`<div style=${css('display:flex;gap:12px;align-items:center')}>
                <input type="range" min=${f.min} max=${f.max} step=${f.step || 1} value=${val} onChange=${f.onChange} style=${css('flex:1;accent-color:#e59b23')} />
                <span style=${css('min-width:48px;text-align:right;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums')}>${val}${f.unit || ''}</span>
            </div>`;
        else if (t === 'image') control = html`<${ImageControl} value=${val} onPick=${f.onPick} onClear=${f.onClear} />`;
        else if (t === 'list') control = html`<div style=${css('display:grid;gap:8px')}>
                ${(f.items || []).map((it) => html`<div key=${it.n} style=${css('border:1px solid #e3e7ea;border-radius:8px;background:#fafbfc')}>
                    <div style=${css('display:flex;align-items:center;gap:6px;padding:8px 10px;border-bottom:1px solid #edf0f2')}>
                        <span style=${css('color:#a3aeb8;cursor:grab')}>⠿</span>
                        <span style=${css('flex:1;min-width:0;font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap')}>${it.title}</span>
                        <button type="button" onClick=${it.up} title="Move up" style=${css('background:transparent;border:0;cursor:pointer;color:#5b6977;padding:2px 6px')}>↑</button>
                        <button type="button" onClick=${it.down} title="Move down" style=${css('background:transparent;border:0;cursor:pointer;color:#5b6977;padding:2px 6px')}>↓</button>
                        <button type="button" onClick=${it.remove} title="Remove" style=${css('background:transparent;border:0;cursor:pointer;color:#b4413b;padding:2px 6px')}>✕</button>
                    </div>
                    <div style=${css('display:grid;gap:8px;padding:10px')}>
                        ${it.cells.map((c) => html`<label key=${c.label} style=${css('display:grid;gap:4px;font-size:12px;color:#5b6977')}>${c.label}
                            ${c.type === 'textarea' ? html`<textarea rows="2" value=${c.value} onChange=${c.onChange} className="sf-focus-plain" style=${css('width:100%;border:1px solid #d3d9de;border-radius:6px;padding:7px 9px;font-size:13px;line-height:1.5;outline:none;resize:vertical;background:#fff;color:#10263b')}></textarea>`
                                : c.type === 'select' || c.type === 'service' ? html`<select value=${c.value} onChange=${c.onChange} style=${css(cellStyle)}>${c.options.map((o) => html`<option key=${optionValue(o)} value=${optionValue(o)}>${optionLabel(o)}</option>`)}</select>`
                                : c.type === 'link' ? html`<${LinkControl} small value=${c.value} url=${c.url} options=${c.options} onChange=${c.onChange} onUrl=${c.onUrl} />`
                                : c.type === 'image' ? html`<${ImageControl} compact value=${c.value} onPick=${c.onPick} onClear=${c.onClear} />`
                                : html`<input value=${c.value} onChange=${c.onChange} className="sf-focus-plain" style=${css(cellStyle)} />`}
                        </label>`)}
                    </div>
                </div>`)}
                <button type="button" onClick=${f.onAdd} className="sf-hover-lib" style=${css('border:1px dashed #b9c2ca;border-radius:8px;background:transparent;padding:10px;font-size:13px;font-weight:600;color:#10263b;cursor:pointer')}>+ Add ${f.itemLabel}</button>
            </div>`;
        return html`<div style=${css('display:grid;gap:6px;min-width:0')}>
            ${f.label && html`<div style=${css('display:flex;justify-content:space-between;align-items:baseline;gap:8px;font-size:13px;font-weight:600;color:#10263b')}><span>${f.label}</span><span style=${css('font-weight:400;color:#8a97a3;font-size:12px')}>${hint}</span></div>`}
            ${control}
        </div>`;
    }

    window.FS = { html, css, BOOT, api, clone, pill, stPill, statusMeta, Field, optionValue, optionLabel };
})();
