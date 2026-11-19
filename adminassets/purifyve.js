(function () {
    'use strict';

    const cfg = window.PURIFYVE_CONFIG || {};
    const API_URL = cfg.apiUrl || 'editor_api';
    const PAGE_KEY = cfg.pageKey || '';
    const CSRF_TOKEN = cfg.csrfToken || '';

    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function nl2p(text) {
        return String(text ?? '').trim().split(/\n\s*\n/)
            .filter(Boolean)
            .map(p => `<p style="margin:0 0 14px;color:#475569;font-size:15.5px;line-height:1.7;">${esc(p).replace(/\n/g, '<br>')}</p>`)
            .join('');
    }

    function uid() {
        return 'pve_' + Math.random().toString(36).slice(2, 9);
    }

    const BLOCK_CATALOG = [
        { type: 'hero', group: 'Layout', label: 'Hero Banner', icon: 'la-flag' },
        { type: 'split', group: 'Layout', label: 'Image + Text', icon: 'la-columns' },
        { type: 'cta', group: 'Layout', label: 'Call to Action', icon: 'la-bullhorn' },
        { type: 'divider', group: 'Layout', label: 'Spacer / Line', icon: 'la-minus' },
        { type: 'text', group: 'Content', label: 'Text Section', icon: 'la-align-left' },
        { type: 'features', group: 'Content', label: 'Feature Grid', icon: 'la-th-large' },
        { type: 'stats', group: 'Content', label: 'Stats Bar', icon: 'la-chart-bar' },
        { type: 'faq', group: 'Content', label: 'FAQ Accordion', icon: 'la-question-circle' },
        { type: 'notice', group: 'Content', label: 'Notice Box', icon: 'la-info-circle' },
        { type: 'html', group: 'Content', label: 'Custom HTML', icon: 'la-code' }
    ];

    function createDefaultBlock(type) {
        const id = uid();
        switch (type) {
            case 'hero':
                return {
                    id, type, data: {
                        badge: 'Featured',
                        title: 'Build fast, clean pages without complexity',
                        subtitle: 'Create landing pages, documentation, and product showcases using native PurifyVE blocks.',
                        primaryText: 'Get Started',
                        primaryUrl: '/contact',
                        secondaryText: 'Learn More',
                        secondaryUrl: '/about',
                        align: 'center',
                        surface: 'light'
                    }
                };
            case 'text':
                return {
                    id, type, data: {
                        eyebrow: 'Overview',
                        title: 'Write clear, structured content',
                        body: 'PurifyCMS renders clean HTML with zero database overhead. Use paragraphs to share your story or announcements.\n\nDouble line breaks automatically create clean paragraphs.',
                        align: 'left'
                    }
                };
            case 'features':
                return {
                    id, type, data: {
                        title: 'Key Highlights',
                        subtitle: 'Everything designed for speed and clarity.',
                        columns: '3',
                        items: [
                            { icon: 'la-bolt', title: 'Fast Performance', desc: 'Static caching keeps every page responsive.' },
                            { icon: 'la-shield-alt', title: 'Secure Core', desc: 'Built-in CSRF protection and safe file storage.' },
                            { icon: 'la-palette', title: 'Clean Design', desc: 'Consistent typography and responsive layout.' }
                        ]
                    }
                };
            case 'split':
                return {
                    id, type, data: {
                        badge: 'Highlight',
                        title: 'Pair visuals with clear copy',
                        body: 'Combine an image or screenshot with concise text and an action button.',
                        imageUrl: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=900&q=80',
                        imageAlt: 'Preview image',
                        imagePos: 'right',
                        buttonText: 'Explore Details',
                        buttonUrl: '/about'
                    }
                };
            case 'cta':
                return {
                    id, type, data: {
                        title: 'Ready to launch your next page?',
                        subtitle: 'Publish fast, clean content in seconds with PurifyCMS.',
                        buttonText: 'Contact Us',
                        buttonUrl: '/contact',
                        theme: 'indigo'
                    }
                };
            case 'stats':
                return {
                    id, type, data: {
                        items: [
                            { value: '99.9%', label: 'Uptime Target' },
                            { value: '<50ms', label: 'Cached Response' },
                            { value: '0', label: 'Database Overhead' },
                            { value: '24/7', label: 'Support Ready' }
                        ]
                    }
                };
            case 'faq':
                return {
                    id, type, data: {
                        title: 'Frequently Asked Questions',
                        items: [
                            { q: 'How does PurifyCMS store content?', a: 'All pages and settings are stored as lightweight files on disk with zero database setup.' },
                            { q: 'Can I customize the layout?', a: 'Yes. Every block outputs clean HTML that adapts to your active theme.' }
                        ]
                    }
                };
            case 'notice':
                return {
                    id, type, data: {
                        variant: 'info',
                        title: 'Notice',
                        body: 'Highlight important information, release updates, or announcements cleanly.'
                    }
                };
            case 'divider':
                return {
                    id, type, data: { style: 'line', size: 'md' }
                };
            case 'html':
            default:
                return {
                    id, type: 'html', data: {
                        code: '<div class="container" style="padding:24px 0;"><p>Custom HTML content block.</p></div>'
                    }
                };
        }
    }

    function renderTemplatePreview(rawCode) {
        let preview = String(rawCode || '');
        preview = preview.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');

        const siteName = cfg.siteName || 'PurifyCMS';
        const baseUrl = cfg.baseUrl || '';

        const resolvePhpVal = (expr, inAttr) => {
            if (/\$cfg_name\b/.test(expr)) return esc(siteName);
            if (/\$base_site_url\b|\$cfg_mainurl\b/.test(expr)) return esc(baseUrl);
            if (inAttr) return '#';
            const varMatch = expr.match(/\$([a-zA-Z0-9_]+)/);
            const label = varMatch ? '$' + varMatch[1] : 'Dynamic';
            return `<span class="purifyve_php_pill">${esc(label)}</span>`;
        };

        preview = preview.replace(/([a-zA-Z0-9_-]+\s*=\s*")([^"]*?)(")/g, (full, before, val, after) => {
            if (!val.includes('<?')) return full;
            const cleaned = val
                .replace(/<\?=\s*([\s\S]*?)\s*\?>/g, (_, expr) => resolvePhpVal(expr, true))
                .replace(/<\?php[\s\S]*?\?>/gi, '');
            return before + cleaned + after;
        });

        preview = preview.replace(/([a-zA-Z0-9_-]+\s*=\s*')([^']*?)(')/g, (full, before, val, after) => {
            if (!val.includes('<?')) return full;
            const cleaned = val
                .replace(/<\?=\s*([\s\S]*?)\s*\?>/g, (_, expr) => resolvePhpVal(expr, true))
                .replace(/<\?php[\s\S]*?\?>/gi, '');
            return before + cleaned + after;
        });

        preview = preview.replace(/<\?=\s*([\s\S]*?)\s*\?>/g, (_, expr) => resolvePhpVal(expr, false));
        preview = preview.replace(/<\?php[\s\S]*?\?>/gi, '');

        return `<div class="purifyve_tpl_preview">${preview}</div>`;
    }

    function extractEditableTexts(rawCode) {
        const items = [];
        const regex = /<(h[1-4]|p|div|span|a|button)\b([^>]*)>([^<>]+)<\/\1>/gi;
        let match;
        while ((match = regex.exec(rawCode)) !== null) {
            const full = match[0];
            const tag = match[1].toLowerCase();
            const text = match[3].trim();
            if (!text || text.length < 2 || text.includes('<?') || text.includes('?>')) continue;
            items.push({
                tag,
                full,
                open: `<${match[1]}${match[2]}>`,
                close: `</${match[1]}>`,
                text,
                index: match.index
            });
            if (items.length >= 20) break;
        }
        return items;
    }

    function replaceTextInCode(rawCode, oldText, newText) {
        if (!oldText || oldText === newText) return rawCode;
        const escapedOld = oldText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const re = new RegExp('(>\\s*)' + escapedOld + '(\\s*<)');
        if (re.test(rawCode)) {
            return rawCode.replace(re, (_, before, after) => before + newText + after);
        }
        return rawCode;
    }

    function enableInlineEditing(previewEl, blk) {
        previewEl.addEventListener('click', e => {
            if (e.target.closest('a, button')) e.preventDefault();
        });

        const candidates = previewEl.querySelectorAll('h1, h2, h3, h4, p, div, span, a, button, summary');
        candidates.forEach(el => {
            if (el.classList.contains('purifyve_php_pill') || el.classList.contains('purifyve_tpl_preview')) return;
            if (el.querySelector('.purifyve_php_pill')) return;

            const textNodes = Array.from(el.childNodes).filter(n => n.nodeType === 3 && n.textContent.trim().length > 0);
            if (textNodes.length !== 1) return;
            if (el.querySelector('div, p, section, h1, h2, h3, h4, ul, ol, table, form')) return;

            const targetNode = textNodes[0];
            let originalText = targetNode.textContent.trim();
            if (!originalText) return;

            el.classList.add('purifyve_editable');
            el.setAttribute('contenteditable', 'true');
            el.setAttribute('spellcheck', 'false');

            el.addEventListener('mousedown', e => {
                const wrap = el.closest('.purifyve_block_wrap');
                if (wrap) wrap.draggable = false;
                if (state.selectedBlockId !== blk.id) {
                    state.selectedBlockId = blk.id;
                    state.inspectorTab = 'block';
                    document.querySelectorAll('.purifyve_block_wrap').forEach(w => {
                        w.classList.toggle('selected', w.dataset.blockId === blk.id);
                    });
                    renderInspector();
                }
                e.stopPropagation();
            });

            el.addEventListener('click', e => {
                e.preventDefault();
                e.stopPropagation();
            });

            el.addEventListener('input', () => {
                const newText = el.textContent.trim();
                if (!newText || newText === originalText) return;

                if (blk.type === 'template' || blk.type === 'html') {
                    const updated = replaceTextInCode(blk.data.code, originalText, newText);
                    if (updated !== blk.data.code) {
                        blk.data.code = updated;
                        originalText = newText;
                        setDirty(true);
                    }
                } else {
                    for (const k of Object.keys(blk.data)) {
                        if (typeof blk.data[k] === 'string' && blk.data[k].trim() === originalText) {
                            blk.data[k] = newText;
                            originalText = newText;
                            setDirty(true);
                            return;
                        }
                    }
                    if (Array.isArray(blk.data.items)) {
                        for (const item of blk.data.items) {
                            for (const ik of Object.keys(item)) {
                                if (typeof item[ik] === 'string' && item[ik].trim() === originalText) {
                                    item[ik] = newText;
                                    originalText = newText;
                                    setDirty(true);
                                    return;
                                }
                            }
                        }
                    }
                }
            });

            el.addEventListener('blur', () => {
                const wrap = el.closest('.purifyve_block_wrap');
                if (wrap) wrap.draggable = true;
                pushHistory();
                renderInspector();
            });
        });
    }

    function renderBlockHTML(block, forPreview) {
        const d = block.data || {};
        switch (block.type) {
            case 'template':
                return forPreview ? renderTemplatePreview(d.code) : String(d.code ?? '');

            case 'hero': {
                const isDark = d.surface === 'dark';
                const isSoft = d.surface === 'soft';
                const bg = isDark ? '#0f172a' : (isSoft ? '#f8fafc' : '#ffffff');
                const border = isDark ? '1px solid #1e293b' : '1px solid #e2e8f0';
                const titleColor = isDark ? '#f8fafc' : '#0f172a';
                const subColor = isDark ? '#94a3b8' : '#475569';
                const align = d.align === 'left' ? 'left' : 'center';
                const margin = align === 'center' ? '0 auto' : '0';
                const justify = align === 'center' ? 'center' : 'flex-start';

                const badgeHtml = d.badge
                    ? `<div style="display:inline-flex;align-items:center;padding:5px 13px;border-radius:999px;background:${isDark ? 'rgba(99,102,241,0.2)' : 'rgba(79,70,229,0.09)'};color:${isDark ? '#a5b4fc' : '#4f46e5'};font-size:12px;font-weight:700;margin-bottom:16px;">${esc(d.badge)}</div>`
                    : '';
                const priBtn = d.primaryText
                    ? `<a href="${esc(d.primaryUrl || '#')}" style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:10px;background:#4f46e5;color:#ffffff;font-weight:600;font-size:14px;text-decoration:none;">${esc(d.primaryText)}</a>`
                    : '';
                const secBtn = d.secondaryText
                    ? `<a href="${esc(d.secondaryUrl || '#')}" style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:10px;background:${isDark ? 'rgba(255,255,255,0.08)' : '#ffffff'};color:${isDark ? '#f8fafc' : '#0f172a'};border:1px solid ${isDark ? 'rgba(255,255,255,0.16)' : '#cbd5e1'};font-weight:600;font-size:14px;text-decoration:none;">${esc(d.secondaryText)}</a>`
                    : '';

                return `<section style="background:${bg};border:${border};border-radius:16px;padding:56px 32px;margin:0 auto 24px;max-width:1120px;text-align:${align};">
  <div style="max-width:720px;margin:${margin};">
    ${badgeHtml}
    <h1 style="margin:0 0 16px;font-size:clamp(28px,4vw,44px);font-weight:800;line-height:1.15;letter-spacing:-0.025em;color:${titleColor};">${esc(d.title)}</h1>
    <p style="margin:0 0 28px;font-size:16.5px;line-height:1.65;color:${subColor};">${esc(d.subtitle)}</p>
    ${(priBtn || secBtn) ? `<div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:${justify};">${priBtn}${secBtn}</div>` : ''}
  </div>
</section>`;
            }

            case 'text': {
                const align = d.align === 'center' ? 'center' : 'left';
                const margin = align === 'center' ? '0 auto' : '0';
                const eyebrow = d.eyebrow
                    ? `<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#4f46e5;margin-bottom:8px;">${esc(d.eyebrow)}</div>`
                    : '';
                return `<section style="max-width:1120px;margin:0 auto 24px;padding:24px 12px;text-align:${align};">
  <div style="max-width:760px;margin:${margin};">
    ${eyebrow}
    ${d.title ? `<h2 style="margin:0 0 14px;font-size:26px;font-weight:700;color:#0f172a;letter-spacing:-0.02em;">${esc(d.title)}</h2>` : ''}
    ${nl2p(d.body)}
  </div>
</section>`;
            }

            case 'features': {
                const cols = d.columns === '2' ? 'minmax(280px,1fr)' : 'minmax(220px,1fr)';
                const items = Array.isArray(d.items) ? d.items : [];
                const cards = items.map(it => `<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;">
      <div style="width:42px;height:42px;border-radius:10px;background:rgba(79,70,229,0.1);color:#4f46e5;display:inline-flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:14px;"><i class="las ${esc(it.icon || 'la-star')}"></i></div>
      <h3 style="margin:0 0 8px;font-size:17px;font-weight:700;color:#0f172a;">${esc(it.title)}</h3>
      <p style="margin:0;font-size:14px;color:#64748b;line-height:1.6;">${esc(it.desc)}</p>
    </div>`).join('\n');

                return `<section style="max-width:1120px;margin:0 auto 28px;padding:12px;">
  ${(d.title || d.subtitle) ? `<div style="margin-bottom:24px;text-align:center;">
    ${d.title ? `<h2 style="margin:0 0 8px;font-size:26px;font-weight:700;color:#0f172a;">${esc(d.title)}</h2>` : ''}
    ${d.subtitle ? `<p style="margin:0;font-size:15px;color:#64748b;">${esc(d.subtitle)}</p>` : ''}
  </div>` : ''}
  <div style="display:grid;grid-template-columns:repeat(auto-fit,${cols});gap:18px;">
    ${cards}
  </div>
</section>`;
            }

            case 'split': {
                const imgFirst = d.imagePos === 'left';
                const textCol = `<div style="flex:1 1 320px;">
      ${d.badge ? `<span style="display:inline-block;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#4f46e5;margin-bottom:8px;">${esc(d.badge)}</span>` : ''}
      <h2 style="margin:0 0 12px;font-size:26px;font-weight:700;color:#0f172a;line-height:1.25;">${esc(d.title)}</h2>
      ${nl2p(d.body)}
      ${d.buttonText ? `<a href="${esc(d.buttonUrl || '#')}" style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:10px 18px;border-radius:9px;background:#4f46e5;color:#fff;font-size:14px;font-weight:600;text-decoration:none;">${esc(d.buttonText)}</a>` : ''}
    </div>`;
                const imgCol = `<div style="flex:1 1 320px;">
      <img src="${esc(d.imageUrl || '')}" alt="${esc(d.imageAlt || '')}" style="width:100%;height:auto;border-radius:14px;border:1px solid #e2e8f0;display:block;object-fit:cover;max-height:360px;">
    </div>`;
                return `<section style="max-width:1120px;margin:0 auto 28px;padding:12px;">
  <div style="display:flex;flex-wrap:wrap;gap:36px;align-items:center;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;">
    ${imgFirst ? imgCol + textCol : textCol + imgCol}
  </div>
</section>`;
            }

            case 'cta': {
                const bg = d.theme === 'dark'
                    ? '#0f172a'
                    : (d.theme === 'light' ? '#f8fafc' : 'linear-gradient(135deg,#4f46e5 0%,#6366f1 100%)');
                const isLight = d.theme === 'light';
                const textColor = isLight ? '#0f172a' : '#ffffff';
                const subColor = isLight ? '#64748b' : 'rgba(255,255,255,0.85)';
                const btnBg = isLight ? '#4f46e5' : '#ffffff';
                const btnColor = isLight ? '#ffffff' : '#0f172a';

                return `<section style="max-width:1120px;margin:0 auto 28px;padding:12px;">
  <div style="background:${bg};border:${isLight ? '1px solid #e2e8f0' : 'none'};border-radius:16px;padding:40px 32px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:20px;">
    <div style="max-width:600px;">
      <h2 style="margin:0 0 8px;font-size:24px;font-weight:700;color:${textColor};">${esc(d.title)}</h2>
      <p style="margin:0;font-size:15px;color:${subColor};line-height:1.6;">${esc(d.subtitle)}</p>
    </div>
    ${d.buttonText ? `<a href="${esc(d.buttonUrl || '#')}" style="display:inline-flex;align-items:center;padding:12px 24px;border-radius:10px;background:${btnBg};color:${btnColor};font-weight:700;font-size:14px;text-decoration:none;white-space:nowrap;">${esc(d.buttonText)}</a>` : ''}
  </div>
</section>`;
            }

            case 'stats': {
                const items = Array.isArray(d.items) ? d.items : [];
                const cells = items.map(it => `<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;text-align:center;">
      <div style="font-size:28px;font-weight:800;color:#4f46e5;letter-spacing:-0.02em;margin-bottom:4px;">${esc(it.value)}</div>
      <div style="font-size:13px;font-weight:600;color:#64748b;">${esc(it.label)}</div>
    </div>`).join('\n');
                return `<section style="max-width:1120px;margin:0 auto 28px;padding:12px;">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;">
    ${cells}
  </div>
</section>`;
            }

            case 'faq': {
                const items = Array.isArray(d.items) ? d.items : [];
                const rows = items.map(it => `<details style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:10px;">
      <summary style="cursor:pointer;font-weight:700;font-size:15px;color:#0f172a;list-style:none;">${esc(it.q)}</summary>
      <p style="margin:10px 0 0;font-size:14px;color:#475569;line-height:1.65;">${esc(it.a)}</p>
    </details>`).join('\n');
                return `<section style="max-width:820px;margin:0 auto 28px;padding:12px;">
  ${d.title ? `<h2 style="margin:0 0 18px;font-size:24px;font-weight:700;color:#0f172a;text-align:center;">${esc(d.title)}</h2>` : ''}
  ${rows}
</section>`;
            }

            case 'notice': {
                const styles = {
                    info: { bg: 'rgba(79,70,229,0.06)', border: 'rgba(79,70,229,0.22)', color: '#4f46e5', icon: 'la-info-circle' },
                    success: { bg: 'rgba(16,185,129,0.08)', border: 'rgba(16,185,129,0.25)', color: '#059669', icon: 'la-check-circle' },
                    warning: { bg: 'rgba(245,158,11,0.08)', border: 'rgba(245,158,11,0.28)', color: '#d97706', icon: 'la-exclamation-triangle' }
                };
                const st = styles[d.variant] || styles.info;
                return `<section style="max-width:1120px;margin:0 auto 20px;padding:0 12px;">
  <div style="background:${st.bg};border:1px solid ${st.border};border-radius:12px;padding:18px 20px;display:flex;gap:14px;align-items:flex-start;">
    <i class="las ${st.icon}" style="font-size:22px;color:${st.color};flex-shrink:0;margin-top:2px;"></i>
    <div>
      ${d.title ? `<div style="font-weight:700;font-size:14.5px;color:#0f172a;margin-bottom:4px;">${esc(d.title)}</div>` : ''}
      <div style="font-size:13.5px;color:#475569;line-height:1.6;">${esc(d.body)}</div>
    </div>
  </div>
</section>`;
            }

            case 'divider': {
                const pad = d.size === 'lg' ? '36px' : (d.size === 'sm' ? '12px' : '24px');
                if (d.style === 'space') return `<div style="height:${pad};"></div>`;
                return `<div style="max-width:1120px;margin:0 auto;padding:${pad} 12px;"><hr style="border:none;border-top:1px solid #e2e8f0;margin:0;"></div>`;
            }

            case 'html':
            default:
                return String(d.code ?? '');
        }
    }

    function compileZoneHTML(blocks) {
        return blocks.map(b => renderBlockHTML(b, false)).join('\n\n');
    }

    const state = {
        title: '',
        desc: '',
        zones: [],
        activeZoneIdx: 0,
        selectedBlockId: null,
        inspectorTab: 'block',
        viewport: 'desktop',
        dirty: false,
        history: [],
        historyPtr: -1,
        cmInstance: null,
        dragSourceId: null
    };

    function activeZone() {
        return state.zones[state.activeZoneIdx] || null;
    }

    function pushHistory() {
        const z = activeZone();
        if (!z || z.mode !== 'blocks') return;
        const snap = JSON.stringify(z.blocks);
        if (state.history[state.historyPtr] === snap) return;
        state.history = state.history.slice(0, state.historyPtr + 1);
        state.history.push(snap);
        if (state.history.length > 40) state.history.shift();
        state.historyPtr = state.history.length - 1;
        updateUndoRedo();
    }

    function updateUndoRedo() {
        const u = document.getElementById('purifyve_undo');
        const r = document.getElementById('purifyve_redo');
        if (u) u.disabled = state.historyPtr <= 0;
        if (r) r.disabled = state.historyPtr >= state.history.length - 1;
    }

    function setDirty(flag) {
        state.dirty = flag;
        const badge = document.getElementById('purifyve_unsaved');
        if (badge) badge.style.display = flag ? 'inline-flex' : 'none';
    }

    let toastTimer = null;
    function showToast(msg, type) {
        const el = document.getElementById('purifyve_toast');
        if (!el) return;
        clearTimeout(toastTimer);
        el.className = 'purifyve_toast ' + (type === 'error' ? 'error' : 'success');
        el.innerHTML = `<i class="las ${type === 'error' ? 'la-exclamation-circle' : 'la-check-circle'}" style="font-size:18px;"></i><span></span>`;
        el.querySelector('span').textContent = msg;
        el.style.display = 'flex';
        toastTimer = setTimeout(() => { el.style.display = 'none'; }, type === 'error' ? 5000 : 2600);
    }

    function renderPalette(filter) {
        const host = document.getElementById('purifyve_palette_body');
        if (!host) return;
        const q = (filter || '').trim().toLowerCase();
        const groups = ['Layout', 'Content'];
        let html = '';

        groups.forEach(group => {
            const items = BLOCK_CATALOG.filter(b => b.group === group && (!q || b.label.toLowerCase().includes(q) || b.type.includes(q)));
            if (!items.length) return;
            html += `<div class="purifyve_group_label">${group}</div><div class="purifyve_block_grid">`;
            items.forEach(b => {
                html += `<div class="purifyve_block_tile" draggable="true" data-add-type="${b.type}" title="Add ${b.label}">
                    <i class="las ${b.icon}"></i>
                    <span>${b.label}</span>
                </div>`;
            });
            html += '</div>';
        });

        host.innerHTML = html || '<p style="font-size:12px;color:var(--purifyve_muted);text-align:center;margin-top:20px;">No matching blocks.</p>';

        host.querySelectorAll('.purifyve_block_tile').forEach(tile => {
            tile.addEventListener('click', () => addBlock(tile.dataset.addType));
            tile.addEventListener('dragstart', e => {
                e.dataTransfer.setData('text/purifyve-new', tile.dataset.addType);
            });
        });
    }

    function renderZoneTabs() {
        const bar = document.getElementById('purifyve_zonebar');
        if (!bar) return;
        if (state.zones.length <= 1) {
            bar.style.display = 'none';
            return;
        }
        bar.style.display = 'flex';
        bar.innerHTML = '';
        state.zones.forEach((z, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'purifyve_zone_tab' + (idx === state.activeZoneIdx ? ' active' : '');
            const nice = z.name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            btn.innerHTML = `<i class="las ${z.has_php ? 'la-code' : 'la-layer-group'}"></i><span></span>`;
            btn.querySelector('span').textContent = nice;
            btn.addEventListener('click', () => switchZone(idx));
            bar.appendChild(btn);
        });
    }

    function syncCodeMirror() {
        const z = activeZone();
        if (z && state.cmInstance) {
            z.content = state.cmInstance.getValue();
            state.cmInstance.toTextArea();
            state.cmInstance = null;
        }
    }

    function switchZone(idx) {
        syncCodeMirror();
        state.activeZoneIdx = idx;
        state.selectedBlockId = null;
        state.history = [];
        state.historyPtr = -1;
        const z = activeZone();
        if (z && z.mode === 'blocks') pushHistory();
        renderZoneTabs();
        renderModeButtons();
        renderCanvas();
        renderInspector();
    }

    function renderModeButtons() {
        const z = activeZone();
        const modeGroup = document.getElementById('purifyve_mode_switch');
        const vpGroup = document.getElementById('purifyve_vp_switch');
        const palette = document.getElementById('purifyve_palette');
        const visualBtn = document.getElementById('purifyve_mode_visual');
        const codeBtn = document.getElementById('purifyve_mode_code');

        if (!z) {
            if (modeGroup) modeGroup.style.display = 'none';
            if (vpGroup) vpGroup.style.display = 'none';
            return;
        }
        if (modeGroup) modeGroup.style.display = 'inline-flex';
        if (vpGroup) vpGroup.style.display = z.mode === 'blocks' ? 'inline-flex' : 'none';
        if (palette) {
            palette.style.opacity = z.mode === 'blocks' ? '1' : '0.45';
            palette.style.pointerEvents = z.mode === 'blocks' ? 'auto' : 'none';
        }
        if (visualBtn) visualBtn.classList.toggle('active', z.mode === 'blocks');
        if (codeBtn) codeBtn.classList.toggle('active', z.mode === 'code');
    }

    function addBlock(type, insertIndex) {
        const z = activeZone();
        if (!z || z.mode !== 'blocks') return;
        const blk = createDefaultBlock(type);
        if (typeof insertIndex === 'number') {
            z.blocks.splice(insertIndex, 0, blk);
        } else if (state.selectedBlockId) {
            const idx = z.blocks.findIndex(b => b.id === state.selectedBlockId);
            z.blocks.splice(idx >= 0 ? idx + 1 : z.blocks.length, 0, blk);
        } else {
            z.blocks.push(blk);
        }
        state.selectedBlockId = blk.id;
        state.inspectorTab = 'block';
        pushHistory();
        setDirty(true);
        renderCanvas();
        renderInspector();
    }

    function applyStarter(preset) {
        const z = activeZone();
        if (!z) return;
        if (preset === 'landing') {
            z.blocks = [createDefaultBlock('hero'), createDefaultBlock('features'), createDefaultBlock('stats'), createDefaultBlock('cta')];
        } else if (preset === 'article') {
            z.blocks = [createDefaultBlock('text'), createDefaultBlock('split'), createDefaultBlock('notice')];
        } else if (preset === 'faq') {
            z.blocks = [createDefaultBlock('hero'), createDefaultBlock('faq'), createDefaultBlock('cta')];
        }
        state.selectedBlockId = z.blocks[0]?.id || null;
        state.inspectorTab = 'block';
        pushHistory();
        setDirty(true);
        renderCanvas();
        renderInspector();
    }

    function moveBlock(id, dir) {
        const z = activeZone();
        if (!z) return;
        const idx = z.blocks.findIndex(b => b.id === id);
        const target = idx + dir;
        if (idx < 0 || target < 0 || target >= z.blocks.length) return;
        const [item] = z.blocks.splice(idx, 1);
        z.blocks.splice(target, 0, item);
        pushHistory();
        setDirty(true);
        renderCanvas();
    }

    function duplicateBlock(id) {
        const z = activeZone();
        if (!z) return;
        const idx = z.blocks.findIndex(b => b.id === id);
        if (idx < 0) return;
        const copy = JSON.parse(JSON.stringify(z.blocks[idx]));
        copy.id = uid();
        z.blocks.splice(idx + 1, 0, copy);
        state.selectedBlockId = copy.id;
        pushHistory();
        setDirty(true);
        renderCanvas();
        renderInspector();
    }

    function deleteBlock(id) {
        const z = activeZone();
        if (!z) return;
        z.blocks = z.blocks.filter(b => b.id !== id);
        if (state.selectedBlockId === id) state.selectedBlockId = null;
        pushHistory();
        setDirty(true);
        renderCanvas();
        renderInspector();
    }

    function renderCanvas() {
        const area = document.getElementById('purifyve_canvas_area');
        if (!area) return;
        syncCodeMirror();
        area.innerHTML = '';

        const z = activeZone();
        if (!z) {
            area.innerHTML = `<div class="purifyve_empty_canvas" style="background:var(--purifyve_bg);max-width:560px;margin:24px auto;">
                <i class="las la-layer-group" style="font-size:34px;color:var(--purifyve_accent);margin-bottom:10px;"></i>
                <h3 style="margin:0 0 8px;font-size:16px;color:var(--purifyve_text);">No Editable Zones</h3>
                <p style="margin:0;font-size:13px;">Edit SEO metadata in the right panel, or add <code>&lt;!-- purify:zone:main_start --&gt;</code> markers in this template.</p>
            </div>`;
            return;
        }

        if (z.mode === 'code') {
            const wrap = document.createElement('div');
            wrap.className = 'purifyve_code_panel';
            const ta = document.createElement('textarea');
            ta.value = z.content;
            wrap.appendChild(ta);
            area.appendChild(wrap);

            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const cm = CodeMirror.fromTextArea(ta, {
                mode: 'application/x-httpd-php',
                theme: isDark ? 'dracula' : 'default',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 4,
                tabSize: 4
            });
            cm.on('change', () => {
                z.content = cm.getValue();
                setDirty(true);
            });
            state.cmInstance = cm;
            setTimeout(() => cm.refresh(), 30);
            return;
        }

        const artboard = document.createElement('div');
        artboard.className = 'purifyve_artboard' + (state.viewport === 'tablet' ? ' purifyve_vp_tablet' : (state.viewport === 'mobile' ? ' purifyve_vp_mobile' : ''));

        artboard.addEventListener('dragover', e => e.preventDefault());
        artboard.addEventListener('drop', e => {
            const newType = e.dataTransfer.getData('text/purifyve-new');
            if (newType) {
                e.preventDefault();
                addBlock(newType);
            }
        });

        if (!z.blocks || z.blocks.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'purifyve_empty_canvas';
            empty.innerHTML = `
                <i class="las la-cubes" style="font-size:38px;color:#4f46e5;margin-bottom:10px;"></i>
                <h3 style="margin:0 0 6px;font-size:17px;color:#0f172a;">Start Building with PurifyVE</h3>
                <p style="margin:0;font-size:13.5px;max-width:420px;margin-inline:auto;">Click or drag any block from the left panel, or pick a starter layout below.</p>
                <div class="purifyve_starter_row">
                    <button type="button" class="purifyve_starter_btn" data-starter="landing"><i class="las la-rocket"></i> Landing Page</button>
                    <button type="button" class="purifyve_starter_btn" data-starter="article"><i class="las la-file-alt"></i> Article / About</button>
                    <button type="button" class="purifyve_starter_btn" data-starter="faq"><i class="las la-question-circle"></i> Help &amp; FAQ</button>
                </div>`;
            empty.querySelectorAll('[data-starter]').forEach(btn => {
                btn.addEventListener('click', () => applyStarter(btn.dataset.starter));
            });
            artboard.appendChild(empty);
            area.appendChild(artboard);
            return;
        }

        z.blocks.forEach((blk, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'purifyve_block_wrap' + (blk.id === state.selectedBlockId ? ' selected' : '');
            wrap.draggable = true;
            wrap.dataset.blockId = blk.id;

            const meta = blk.type === 'template'
                ? { label: (blk.data.label || 'Template') + (blk.data.hasPhp ? ' · PHP' : '') }
                : (BLOCK_CATALOG.find(b => b.type === blk.type) || { label: blk.type });

            const bar = document.createElement('div');
            bar.className = 'purifyve_block_bar';
            bar.innerHTML = `
                <span class="purifyve_block_tag">${esc(meta.label)}</span>
                <button type="button" class="purifyve_block_action" data-act="up" title="Move Up"><i class="las la-arrow-up"></i></button>
                <button type="button" class="purifyve_block_action" data-act="down" title="Move Down"><i class="las la-arrow-down"></i></button>
                <button type="button" class="purifyve_block_action" data-act="dup" title="Duplicate"><i class="las la-copy"></i></button>
                <button type="button" class="purifyve_block_action danger" data-act="del" title="Delete"><i class="las la-trash"></i></button>
            `;
            bar.addEventListener('click', e => {
                e.stopPropagation();
                const btn = e.target.closest('[data-act]');
                if (!btn) return;
                const act = btn.dataset.act;
                if (act === 'up') moveBlock(blk.id, -1);
                if (act === 'down') moveBlock(blk.id, 1);
                if (act === 'dup') duplicateBlock(blk.id);
                if (act === 'del') deleteBlock(blk.id);
            });

            const preview = document.createElement('div');
            preview.innerHTML = renderBlockHTML(blk, true);
            enableInlineEditing(preview, blk);

            wrap.appendChild(bar);
            wrap.appendChild(preview);

            wrap.addEventListener('click', e => {
                e.stopPropagation();
                state.selectedBlockId = blk.id;
                state.inspectorTab = 'block';
                renderCanvas();
                renderInspector();
            });

            wrap.addEventListener('dragstart', e => {
                state.dragSourceId = blk.id;
                e.dataTransfer.setData('text/purifyve-move', blk.id);
            });
            wrap.addEventListener('dragover', e => {
                e.preventDefault();
                wrap.classList.add('drag-over');
            });
            wrap.addEventListener('dragleave', () => wrap.classList.remove('drag-over'));
            wrap.addEventListener('drop', e => {
                e.preventDefault();
                e.stopPropagation();
                wrap.classList.remove('drag-over');
                const newType = e.dataTransfer.getData('text/purifyve-new');
                if (newType) {
                    addBlock(newType, idx);
                    return;
                }
                const moveId = e.dataTransfer.getData('text/purifyve-move') || state.dragSourceId;
                if (moveId && moveId !== blk.id) {
                    const fromIdx = z.blocks.findIndex(b => b.id === moveId);
                    if (fromIdx >= 0) {
                        const [moved] = z.blocks.splice(fromIdx, 1);
                        const toIdx = z.blocks.findIndex(b => b.id === blk.id);
                        z.blocks.splice(toIdx, 0, moved);
                        pushHistory();
                        setDirty(true);
                        renderCanvas();
                    }
                }
            });

            artboard.appendChild(wrap);
        });

        area.appendChild(artboard);
    }

    function updateBlockPreview(blk) {
        const wrap = document.querySelector(`.purifyve_block_wrap[data-block-id="${blk.id}"]`);
        if (wrap && wrap.children[1]) {
            wrap.children[1].innerHTML = renderBlockHTML(blk, true);
            enableInlineEditing(wrap.children[1], blk);
        }
    }

    function fieldInput(label, value, onChange, placeholder) {
        const div = document.createElement('div');
        div.className = 'purifyve_field';
        div.innerHTML = `<label class="purifyve_label"><span>${esc(label)}</span></label><input type="text" class="purifyve_input">`;
        const inp = div.querySelector('input');
        inp.value = value ?? '';
        if (placeholder) inp.placeholder = placeholder;
        inp.addEventListener('input', () => onChange(inp.value));
        return div;
    }

    function fieldTextarea(label, value, rows, onChange, mono) {
        const div = document.createElement('div');
        div.className = 'purifyve_field';
        div.innerHTML = `<label class="purifyve_label"><span>${esc(label)}</span></label><textarea class="purifyve_textarea" rows="${rows || 3}"></textarea>`;
        const ta = div.querySelector('textarea');
        ta.value = value ?? '';
        if (mono) ta.style.fontFamily = 'ui-monospace, SFMono-Regular, Menlo, monospace';
        ta.addEventListener('input', () => onChange(ta.value));
        return div;
    }

    function fieldPills(label, options, current, onChange) {
        const div = document.createElement('div');
        div.className = 'purifyve_field';
        div.innerHTML = `<label class="purifyve_label"><span>${esc(label)}</span></label><div class="purifyve_pills"></div>`;
        const row = div.querySelector('.purifyve_pills');
        options.forEach(opt => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'purifyve_pill' + (current === opt.value ? ' active' : '');
            btn.textContent = opt.label;
            btn.addEventListener('click', () => {
                onChange(opt.value);
                renderInspector();
            });
            row.appendChild(btn);
        });
        return div;
    }

    function renderInspector() {
        const body = document.getElementById('purifyve_insp_body');
        const tabBlock = document.getElementById('purifyve_tab_block');
        const tabSeo = document.getElementById('purifyve_tab_seo');
        if (!body) return;

        if (tabBlock) tabBlock.classList.toggle('active', state.inspectorTab === 'block');
        if (tabSeo) tabSeo.classList.toggle('active', state.inspectorTab === 'seo');

        body.innerHTML = '';

        if (state.inspectorTab === 'seo') {
            const titleField = document.createElement('div');
            titleField.className = 'purifyve_field';
            titleField.innerHTML = `<label class="purifyve_label"><span>Page Title</span><span id="pveTitleCount" style="font-size:11px;color:var(--purifyve_muted);">${state.title.length} / 60</span></label><input type="text" class="purifyve_input">`;
            const tInp = titleField.querySelector('input');
            tInp.value = state.title;
            tInp.addEventListener('input', () => {
                state.title = tInp.value;
                setDirty(true);
                titleField.querySelector('#pveTitleCount').textContent = `${state.title.length} / 60`;
                const pt = body.querySelector('#pveLiveTitle');
                if (pt) pt.textContent = state.title || 'Page Title';
            });

            const descField = document.createElement('div');
            descField.className = 'purifyve_field';
            descField.innerHTML = `<label class="purifyve_label"><span>Meta Description</span><span id="pveDescCount" style="font-size:11px;color:var(--purifyve_muted);">${state.desc.length} / 160</span></label><textarea class="purifyve_textarea" rows="3"></textarea>`;
            const dInp = descField.querySelector('textarea');
            dInp.value = state.desc;
            dInp.addEventListener('input', () => {
                state.desc = dInp.value;
                setDirty(true);
                descField.querySelector('#pveDescCount').textContent = `${state.desc.length} / 160`;
                const pd = body.querySelector('#pveLiveDesc');
                if (pd) pd.textContent = state.desc || 'Page description will appear here...';
            });

            const preview = document.createElement('div');
            preview.className = 'purifyve_seo_card';
            preview.innerHTML = `
                <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--purifyve_muted);margin-bottom:8px;">Search Preview</div>
                <div style="font-size:11.5px;color:var(--purifyve_muted);margin-bottom:3px;word-break:break-all;">${esc(cfg.previewUrl || '')}</div>
                <div id="pveLiveTitle" style="font-size:15px;font-weight:700;color:var(--purifyve_accent);margin-bottom:4px;">${esc(state.title || 'Page Title')}</div>
                <div id="pveLiveDesc" style="font-size:12.5px;color:var(--purifyve_muted);line-height:1.45;">${esc(state.desc || 'Page description will appear here...')}</div>
            `;

            body.appendChild(titleField);
            body.appendChild(descField);
            body.appendChild(preview);
            return;
        }

        const z = activeZone();
        const blk = z && z.blocks ? z.blocks.find(b => b.id === state.selectedBlockId) : null;
        if (!blk) {
            body.innerHTML = `<div style="text-align:center;padding:40px 16px;color:var(--purifyve_muted);">
                <i class="las la-mouse-pointer" style="font-size:28px;opacity:0.6;margin-bottom:10px;"></i>
                <p style="margin:0;font-size:13px;line-height:1.55;">Select any block on the canvas to edit its properties, or drag new blocks from the left panel above or below existing sections.</p>
            </div>`;
            return;
        }

        const d = blk.data;
        const sync = () => {
            setDirty(true);
            updateBlockPreview(blk);
        };

        const header = document.createElement('div');
        const meta = blk.type === 'template'
            ? { label: (d.label || 'Template Block'), icon: d.hasPhp ? 'la-code' : 'la-file-code' }
            : (BLOCK_CATALOG.find(b => b.type === blk.type) || { label: blk.type, icon: 'la-cube' });
        header.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;margin-bottom:14px;border-bottom:1px solid var(--purifyve_border);font-weight:700;font-size:13.5px;';
        header.innerHTML = `<span style="display:inline-flex;align-items:center;gap:8px;"><i class="las ${meta.icon}" style="color:var(--purifyve_accent);font-size:18px;"></i>${esc(meta.label)}</span>` + (d.hasPhp ? '<span class="purifyve_php_pill">PHP Safe</span>' : '');
        body.appendChild(header);

        if (blk.type === 'template') {
            const editables = extractEditableTexts(d.code);
            if (editables.length > 0) {
                const sub = document.createElement('div');
                sub.style.cssText = 'font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--purifyve_muted);margin-bottom:10px;';
                sub.textContent = 'Quick Text Edit (PHP Preserved)';
                body.appendChild(sub);

                editables.forEach(item => {
                    let currentFull = item.full;
                    const label = `${item.tag.toUpperCase()} Text`;
                    const isLong = item.text.length > 60;
                    const onEdit = (newVal) => {
                        const updatedFull = item.open + newVal + item.close;
                        d.code = d.code.replace(currentFull, updatedFull);
                        currentFull = updatedFull;
                        sync();
                    };
                    body.appendChild(isLong ? fieldTextarea(label, item.text, 3, onEdit) : fieldInput(label, item.text, onEdit));
                });
            }

            const codeBtn = document.createElement('button');
            codeBtn.type = 'button';
            codeBtn.className = 'btn btn-sm btn-secondary';
            codeBtn.style.cssText = 'width:100%;margin-top:8px;';
            codeBtn.innerHTML = '<i class="las la-code"></i> Edit Full PHP / HTML Source';
            codeBtn.addEventListener('click', () => {
                document.getElementById('purifyve_mode_code')?.click();
            });
            body.appendChild(codeBtn);
            return;
        }

        if (blk.type === 'hero') {
            body.appendChild(fieldInput('Badge', d.badge, v => { d.badge = v; sync(); }));
            body.appendChild(fieldInput('Headline', d.title, v => { d.title = v; sync(); }));
            body.appendChild(fieldTextarea('Subtitle', d.subtitle, 3, v => { d.subtitle = v; sync(); }));
            body.appendChild(fieldInput('Primary Button', d.primaryText, v => { d.primaryText = v; sync(); }));
            body.appendChild(fieldInput('Primary URL', d.primaryUrl, v => { d.primaryUrl = v; sync(); }));
            body.appendChild(fieldInput('Secondary Button', d.secondaryText, v => { d.secondaryText = v; sync(); }));
            body.appendChild(fieldInput('Secondary URL', d.secondaryUrl, v => { d.secondaryUrl = v; sync(); }));
            body.appendChild(fieldPills('Alignment', [{ label: 'Left', value: 'left' }, { label: 'Center', value: 'center' }], d.align, v => { d.align = v; pushHistory(); sync(); }));
            body.appendChild(fieldPills('Surface', [{ label: 'Light', value: 'light' }, { label: 'Soft', value: 'soft' }, { label: 'Dark', value: 'dark' }], d.surface, v => { d.surface = v; pushHistory(); sync(); }));
        } else if (blk.type === 'text') {
            body.appendChild(fieldInput('Eyebrow', d.eyebrow, v => { d.eyebrow = v; sync(); }));
            body.appendChild(fieldInput('Heading', d.title, v => { d.title = v; sync(); }));
            body.appendChild(fieldTextarea('Body', d.body, 7, v => { d.body = v; sync(); }));
            body.appendChild(fieldPills('Alignment', [{ label: 'Left', value: 'left' }, { label: 'Center', value: 'center' }], d.align, v => { d.align = v; pushHistory(); sync(); }));
        } else if (blk.type === 'split') {
            body.appendChild(fieldInput('Badge', d.badge, v => { d.badge = v; sync(); }));
            body.appendChild(fieldInput('Heading', d.title, v => { d.title = v; sync(); }));
            body.appendChild(fieldTextarea('Body', d.body, 4, v => { d.body = v; sync(); }));
            body.appendChild(fieldInput('Image URL', d.imageUrl, v => { d.imageUrl = v; sync(); }));
            body.appendChild(fieldInput('Image Alt', d.imageAlt, v => { d.imageAlt = v; sync(); }));
            body.appendChild(fieldPills('Image Side', [{ label: 'Left', value: 'left' }, { label: 'Right', value: 'right' }], d.imagePos, v => { d.imagePos = v; pushHistory(); sync(); }));
            body.appendChild(fieldInput('Button Label', d.buttonText, v => { d.buttonText = v; sync(); }));
            body.appendChild(fieldInput('Button URL', d.buttonUrl, v => { d.buttonUrl = v; sync(); }));
        } else if (blk.type === 'cta') {
            body.appendChild(fieldInput('Headline', d.title, v => { d.title = v; sync(); }));
            body.appendChild(fieldTextarea('Subtitle', d.subtitle, 2, v => { d.subtitle = v; sync(); }));
            body.appendChild(fieldInput('Button Label', d.buttonText, v => { d.buttonText = v; sync(); }));
            body.appendChild(fieldInput('Button URL', d.buttonUrl, v => { d.buttonUrl = v; sync(); }));
            body.appendChild(fieldPills('Style', [{ label: 'Indigo', value: 'indigo' }, { label: 'Dark', value: 'dark' }, { label: 'Light', value: 'light' }], d.theme, v => { d.theme = v; pushHistory(); sync(); }));
        } else if (blk.type === 'notice') {
            body.appendChild(fieldPills('Type', [{ label: 'Info', value: 'info' }, { label: 'Success', value: 'success' }, { label: 'Warning', value: 'warning' }], d.variant, v => { d.variant = v; pushHistory(); sync(); }));
            body.appendChild(fieldInput('Title', d.title, v => { d.title = v; sync(); }));
            body.appendChild(fieldTextarea('Message', d.body, 4, v => { d.body = v; sync(); }));
        } else if (blk.type === 'divider') {
            body.appendChild(fieldPills('Style', [{ label: 'Line', value: 'line' }, { label: 'Space', value: 'space' }], d.style, v => { d.style = v; pushHistory(); sync(); }));
            body.appendChild(fieldPills('Size', [{ label: 'S', value: 'sm' }, { label: 'M', value: 'md' }, { label: 'L', value: 'lg' }], d.size, v => { d.size = v; pushHistory(); sync(); }));
        } else if (blk.type === 'features') {
            body.appendChild(fieldInput('Heading', d.title, v => { d.title = v; sync(); }));
            body.appendChild(fieldInput('Subtitle', d.subtitle, v => { d.subtitle = v; sync(); }));
            body.appendChild(fieldPills('Columns', [{ label: '2 Cols', value: '2' }, { label: '3 Cols', value: '3' }], d.columns, v => { d.columns = v; pushHistory(); sync(); }));
            (d.items || []).forEach((it, idx) => {
                const card = document.createElement('div');
                card.className = 'purifyve_repeater';
                card.innerHTML = `<div class="purifyve_repeater_head"><span>Card #${idx + 1}</span><button type="button" class="purifyve_icon_btn" style="width:22px;height:22px;font-size:12px;"><i class="las la-times"></i></button></div>`;
                card.querySelector('button').addEventListener('click', () => {
                    d.items.splice(idx, 1);
                    pushHistory(); sync(); renderInspector();
                });
                card.appendChild(fieldInput('Icon', it.icon, v => { it.icon = v; sync(); }, 'la-bolt'));
                card.appendChild(fieldInput('Title', it.title, v => { it.title = v; sync(); }));
                card.appendChild(fieldTextarea('Description', it.desc, 2, v => { it.desc = v; sync(); }));
                body.appendChild(card);
            });
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-sm btn-secondary';
            addBtn.style.width = '100%';
            addBtn.innerHTML = '<i class="las la-plus"></i> Add Card';
            addBtn.addEventListener('click', () => {
                d.items.push({ icon: 'la-check-circle', title: 'New Feature', desc: 'Short description.' });
                pushHistory(); sync(); renderInspector();
            });
            body.appendChild(addBtn);
        } else if (blk.type === 'stats') {
            (d.items || []).forEach((it, idx) => {
                const card = document.createElement('div');
                card.className = 'purifyve_repeater';
                card.innerHTML = `<div class="purifyve_repeater_head"><span>Stat #${idx + 1}</span><button type="button" class="purifyve_icon_btn" style="width:22px;height:22px;font-size:12px;"><i class="las la-times"></i></button></div>`;
                card.querySelector('button').addEventListener('click', () => {
                    d.items.splice(idx, 1);
                    pushHistory(); sync(); renderInspector();
                });
                card.appendChild(fieldInput('Value', it.value, v => { it.value = v; sync(); }));
                card.appendChild(fieldInput('Label', it.label, v => { it.label = v; sync(); }));
                body.appendChild(card);
            });
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-sm btn-secondary';
            addBtn.style.width = '100%';
            addBtn.innerHTML = '<i class="las la-plus"></i> Add Stat';
            addBtn.addEventListener('click', () => {
                d.items.push({ value: '100%', label: 'Metric' });
                pushHistory(); sync(); renderInspector();
            });
            body.appendChild(addBtn);
        } else if (blk.type === 'faq') {
            body.appendChild(fieldInput('Heading', d.title, v => { d.title = v; sync(); }));
            (d.items || []).forEach((it, idx) => {
                const card = document.createElement('div');
                card.className = 'purifyve_repeater';
                card.innerHTML = `<div class="purifyve_repeater_head"><span>Item #${idx + 1}</span><button type="button" class="purifyve_icon_btn" style="width:22px;height:22px;font-size:12px;"><i class="las la-times"></i></button></div>`;
                card.querySelector('button').addEventListener('click', () => {
                    d.items.splice(idx, 1);
                    pushHistory(); sync(); renderInspector();
                });
                card.appendChild(fieldInput('Question', it.q, v => { it.q = v; sync(); }));
                card.appendChild(fieldTextarea('Answer', it.a, 3, v => { it.a = v; sync(); }));
                body.appendChild(card);
            });
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-sm btn-secondary';
            addBtn.style.width = '100%';
            addBtn.innerHTML = '<i class="las la-plus"></i> Add Question';
            addBtn.addEventListener('click', () => {
                d.items.push({ q: 'New question?', a: 'Answer here.' });
                pushHistory(); sync(); renderInspector();
            });
            body.appendChild(addBtn);
        } else if (blk.type === 'html') {
            body.appendChild(fieldTextarea('HTML Snippet', d.code, 12, v => { d.code = v; sync(); }, true));
        }
    }

    function savePage() {
        const btn = document.getElementById('purifyve_save');
        if (!btn || btn.disabled) return;
        syncCodeMirror();

        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="las la-spinner la-spin"></i> Saving...';

        const zonesPayload = state.zones.map(z => {
            if (z.mode === 'blocks') {
                return {
                    name: z.name,
                    mode: 'blocks',
                    blocks: z.blocks,
                    content: compileZoneHTML(z.blocks)
                };
            }
            return {
                name: z.name,
                mode: 'code',
                content: z.content
            };
        });

        const fd = new FormData();
        fd.append('csrf_token', CSRF_TOKEN);
        fd.append('title', state.title);
        fd.append('desc', state.desc);
        fd.append('zones', JSON.stringify(zonesPayload));

        fetch(`${API_URL}?action=save&page=${encodeURIComponent(PAGE_KEY)}`, {
            method: 'POST',
            body: fd
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    setDirty(false);
                    showToast('Saved and page cache refreshed.', 'success');
                    btn.innerHTML = '<i class="las la-check"></i> Saved';
                    setTimeout(() => { btn.innerHTML = oldHtml; btn.disabled = false; }, 1400);
                } else {
                    showToast(res.message || 'Save failed.', 'error');
                    btn.innerHTML = oldHtml;
                    btn.disabled = false;
                }
            })
            .catch(() => {
                showToast('Network error while saving.', 'error');
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            });
    }

    function bindControls() {
        renderPalette('');

        document.getElementById('purifyve_block_search')?.addEventListener('input', e => renderPalette(e.target.value));
        document.getElementById('purifyve_save')?.addEventListener('click', savePage);

        document.getElementById('purifyve_tab_block')?.addEventListener('click', () => {
            state.inspectorTab = 'block';
            renderInspector();
        });
        document.getElementById('purifyve_tab_seo')?.addEventListener('click', () => {
            state.inspectorTab = 'seo';
            renderInspector();
        });

        document.querySelectorAll('[data-purifyve-vp]').forEach(btn => {
            btn.addEventListener('click', () => {
                state.viewport = btn.dataset.purifyveVp;
                document.querySelectorAll('[data-purifyve-vp]').forEach(b => b.classList.toggle('active', b === btn));
                const ab = document.querySelector('.purifyve_artboard');
                if (ab) {
                    ab.classList.remove('purifyve_vp_tablet', 'purifyve_vp_mobile');
                    if (state.viewport === 'tablet') ab.classList.add('purifyve_vp_tablet');
                    if (state.viewport === 'mobile') ab.classList.add('purifyve_vp_mobile');
                }
            });
        });

        document.getElementById('purifyve_mode_visual')?.addEventListener('click', () => {
            const z = activeZone();
            if (!z || z.mode === 'blocks') return;
            syncCodeMirror();
            const nice = z.name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            z.blocks = z.content.trim()
                ? [{ id: uid(), type: 'template', data: { label: nice, code: z.content, hasPhp: /<\?(?:php|=|\s)/i.test(z.content) } }]
                : [];
            z.mode = 'blocks';
            state.selectedBlockId = z.blocks[0]?.id || null;
            pushHistory();
            renderModeButtons();
            renderCanvas();
            renderInspector();
        });

        document.getElementById('purifyve_mode_code')?.addEventListener('click', () => {
            const z = activeZone();
            if (!z || z.mode === 'code') return;
            z.content = compileZoneHTML(z.blocks || []);
            z.mode = 'code';
            renderModeButtons();
            renderCanvas();
        });

        document.getElementById('purifyve_undo')?.addEventListener('click', () => {
            const z = activeZone();
            if (!z || z.mode !== 'blocks' || state.historyPtr <= 0) return;
            state.historyPtr--;
            z.blocks = JSON.parse(state.history[state.historyPtr]);
            setDirty(true);
            updateUndoRedo();
            renderCanvas();
            renderInspector();
        });

        document.getElementById('purifyve_redo')?.addEventListener('click', () => {
            const z = activeZone();
            if (!z || z.mode !== 'blocks' || state.historyPtr >= state.history.length - 1) return;
            state.historyPtr++;
            z.blocks = JSON.parse(state.history[state.historyPtr]);
            setDirty(true);
            updateUndoRedo();
            renderCanvas();
            renderInspector();
        });

        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                savePage();
            }
        });

        window.addEventListener('beforeunload', e => {
            if (state.dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    function loadPage() {
        fetch(`${API_URL}?action=read&page=${encodeURIComponent(PAGE_KEY)}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    showToast(res.message || 'Failed to load page.', 'error');
                    return;
                }
                state.title = res.data.title || '';
                state.desc = res.data.desc || '';
                state.zones = (res.data.zones || []).map(z => {
                    const nice = z.name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                    let blocks = [];
                    if (Array.isArray(z.blocks)) {
                        blocks = z.blocks;
                    } else if (z.content && z.content.trim() !== '') {
                        blocks = [{
                            id: uid(),
                            type: 'template',
                            data: { label: nice, code: z.content, hasPhp: !!z.has_php }
                        }];
                    }
                    return {
                        name: z.name,
                        content: z.content || '',
                        blocks,
                        has_php: !!z.has_php,
                        mode: 'blocks'
                    };
                });

                document.getElementById('purifyve_loading').style.display = 'none';
                document.getElementById('purifyve_shell').style.display = 'flex';

                const first = activeZone();
                if (first && first.blocks.length > 0) {
                    state.selectedBlockId = first.blocks[0].id;
                    pushHistory();
                } else {
                    state.inspectorTab = 'seo';
                }

                renderZoneTabs();
                renderModeButtons();
                renderCanvas();
                renderInspector();
            })
            .catch(() => {
                showToast('Network error while loading page.', 'error');
            });
    }

    bindControls();
    loadPage();
})();
