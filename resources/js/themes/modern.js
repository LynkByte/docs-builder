import Fuse from 'fuse.js';

/*
|--------------------------------------------------------------------------
| LynkByte DevDocs — Modern Theme JavaScript
|--------------------------------------------------------------------------
| Handles: theme toggle, search command palette, code copy, TOC highlight,
| smooth scroll, mobile sidebar, lightbox, mermaid controls, header scroll
*/

(function () {
    'use strict';

    // =========================================================================
    // THEME TOGGLE
    // =========================================================================
    const THEME_KEY = 'devdocs-theme';

    function getPreferredTheme() {
        const stored = localStorage.getItem(THEME_KEY);
        if (stored) {
            return stored;
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        localStorage.setItem(THEME_KEY, theme);
        updateThemeToggleIcons(theme);
        reRenderMermaidDiagrams(theme);
    }

    function updateThemeToggleIcons(theme) {
        document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
            const lightIcon = btn.querySelector('[data-theme-icon="light"]');
            const darkIcon = btn.querySelector('[data-theme-icon="dark"]');
            if (lightIcon && darkIcon) {
                lightIcon.style.display = theme === 'dark' ? 'none' : 'block';
                darkIcon.style.display = theme === 'dark' ? 'block' : 'none';
            }
        });
    }

    function initThemeToggle() {
        applyTheme(getPreferredTheme());

        document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(THEME_KEY)) {
                applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // =========================================================================
    // HEADER SCROLL EFFECT
    // =========================================================================
    function initHeaderScroll() {
        const header = document.querySelector('.docs-header');
        if (!header) return;

        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    header.classList.toggle('is-scrolled', window.scrollY > 8);
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }

    // =========================================================================
    // MERMAID DIAGRAM THEME SYNC
    // =========================================================================
    function initMermaidSource() {
        document.querySelectorAll('.docs-mermaid-content pre.mermaid').forEach(el => {
            if (!el.getAttribute('data-mermaid-source')) {
                el.setAttribute('data-mermaid-source', el.textContent);
            }
        });
    }

    async function reRenderMermaidDiagrams(theme) {
        if (typeof mermaid === 'undefined') return;

        const blocks = document.querySelectorAll('.docs-mermaid-content pre.mermaid');
        if (!blocks.length) return;

        mermaid.initialize({
            startOnLoad: false,
            theme: theme === 'dark' ? 'dark' : 'default',
        });

        for (let i = 0; i < blocks.length; i++) {
            const el = blocks[i];
            const source = el.getAttribute('data-mermaid-source');
            if (!source) continue;
            try {
                const { svg } = await mermaid.render('mermaid-re-' + i, source);
                el.innerHTML = svg;
            } catch (e) {
                console.warn('Mermaid re-render failed:', e);
            }
        }
    }

    // =========================================================================
    // MERMAID DIAGRAM CONTROLS (Zoom, Reset, Fullscreen)
    // =========================================================================
    const MERMAID_MIN_SCALE = 0.25;
    const MERMAID_MAX_SCALE = 3;
    const MERMAID_SCALE_STEP = 0.25;

    function initMermaidControls() {
        document.querySelectorAll('.docs-mermaid-block').forEach(block => {
            let scale = 1;
            const content = block.querySelector('.docs-mermaid-content');
            if (!content) return;

            function applyZoom() {
                const pre = content.querySelector('pre.mermaid');
                if (!pre) return;
                pre.style.transform = `scale(${scale})`;
                pre.style.transformOrigin = 'center top';
                content.classList.toggle('is-zoomed', scale !== 1);
            }

            const zoomInBtn = block.querySelector('[data-mermaid-zoom-in]');
            const zoomOutBtn = block.querySelector('[data-mermaid-zoom-out]');
            const resetBtn = block.querySelector('[data-mermaid-reset]');
            const fullscreenBtn = block.querySelector('[data-mermaid-fullscreen]');

            if (zoomInBtn) {
                zoomInBtn.addEventListener('click', () => {
                    scale = Math.min(scale + MERMAID_SCALE_STEP, MERMAID_MAX_SCALE);
                    applyZoom();
                });
            }

            if (zoomOutBtn) {
                zoomOutBtn.addEventListener('click', () => {
                    scale = Math.max(scale - MERMAID_SCALE_STEP, MERMAID_MIN_SCALE);
                    applyZoom();
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    scale = 1;
                    applyZoom();
                    content.scrollTop = 0;
                    content.scrollLeft = 0;
                });
            }

            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', () => {
                    openMermaidFullscreen(block);
                });
            }
        });
    }

    function openMermaidFullscreen(sourceBlock) {
        const pre = sourceBlock.querySelector('.docs-mermaid-content pre.mermaid');
        if (!pre) return;

        let scale = 1;

        const overlay = document.createElement('div');
        overlay.className = 'docs-mermaid-fullscreen-overlay';

        const toolbar = document.createElement('div');
        toolbar.className = 'docs-mermaid-fullscreen-toolbar';
        toolbar.innerHTML =
            '<button data-fs-zoom-in title="Zoom in"><span class="material-symbols-outlined">zoom_in</span></button>' +
            '<button data-fs-zoom-out title="Zoom out"><span class="material-symbols-outlined">zoom_out</span></button>' +
            '<button data-fs-reset title="Reset zoom"><span class="material-symbols-outlined">fit_screen</span></button>' +
            '<button data-fs-close title="Close"><span class="material-symbols-outlined">close</span></button>';

        const container = document.createElement('div');
        container.className = 'docs-mermaid-fullscreen-content';
        container.innerHTML = pre.innerHTML;

        overlay.appendChild(toolbar);
        overlay.appendChild(container);
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        function applyFullscreenZoom() {
            container.style.transform = `scale(${scale})`;
            container.style.transformOrigin = 'center top';
        }

        toolbar.querySelector('[data-fs-zoom-in]').addEventListener('click', () => {
            scale = Math.min(scale + MERMAID_SCALE_STEP, MERMAID_MAX_SCALE);
            applyFullscreenZoom();
        });

        toolbar.querySelector('[data-fs-zoom-out]').addEventListener('click', () => {
            scale = Math.max(scale - MERMAID_SCALE_STEP, MERMAID_MIN_SCALE);
            applyFullscreenZoom();
        });

        toolbar.querySelector('[data-fs-reset]').addEventListener('click', () => {
            scale = 1;
            applyFullscreenZoom();
        });

        function closeFullscreen() {
            overlay.remove();
            document.body.style.overflow = '';
            document.removeEventListener('keydown', escHandler);
        }

        toolbar.querySelector('[data-fs-close]').addEventListener('click', closeFullscreen);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeFullscreen();
        });

        function escHandler(e) {
            if (e.key === 'Escape') closeFullscreen();
        }
        document.addEventListener('keydown', escHandler);
    }

    // =========================================================================
    // SEARCH COMMAND PALETTE
    // =========================================================================
    let searchIndex = null;
    let fuse = null;
    let searchModalOpen = false;
    let selectedResultIndex = 0;

    async function loadSearchIndex() {
        if (searchIndex) return;
        try {
            const baseUrl = document.querySelector('meta[name="docs-base-url"]')?.content || '/docs';
            const response = await fetch(`${baseUrl}/search-index.json`);
            searchIndex = await response.json();
            fuse = new Fuse(searchIndex, {
                keys: [
                    { name: 'title', weight: 0.4 },
                    { name: 'headings', weight: 0.3 },
                    { name: 'content', weight: 0.2 },
                    { name: 'section', weight: 0.1 },
                ],
                threshold: 0.3,
                includeMatches: true,
                minMatchCharLength: 2,
            });
        } catch (e) {
            console.warn('Failed to load search index:', e);
        }
    }

    function openSearchModal() {
        const modal = document.getElementById('docs-search-modal');
        if (!modal) return;
        searchModalOpen = true;
        modal.classList.remove('hidden');
        const input = modal.querySelector('input');
        if (input) {
            input.value = '';
            input.focus();
        }
        selectedResultIndex = 0;
        loadSearchIndex();
        document.body.style.overflow = 'hidden';
    }

    function closeSearchModal() {
        const modal = document.getElementById('docs-search-modal');
        if (!modal) return;
        searchModalOpen = false;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function performSearch(query) {
        const resultsContainer = document.getElementById('docs-search-results');
        if (!resultsContainer || !fuse) return;

        if (!query || query.length < 2) {
            resultsContainer.innerHTML = '<div class="px-5 py-10 text-center text-sm text-[var(--docs-text-muted)]"><span class="material-symbols-outlined text-2xl mb-2 block opacity-40">travel_explore</span>Type to search documentation...</div>';
            return;
        }

        const results = fuse.search(query, { limit: 15 });
        selectedResultIndex = 0;

        if (results.length === 0) {
            resultsContainer.innerHTML = '<div class="px-5 py-10 text-center text-sm text-[var(--docs-text-muted)]"><span class="material-symbols-outlined text-2xl mb-2 block opacity-40">search_off</span>No results found.</div>';
            return;
        }

        // Group results by section
        const grouped = {};
        results.forEach(result => {
            const section = result.item.section || 'Documentation';
            if (!grouped[section]) grouped[section] = [];
            grouped[section].push(result);
        });

        let html = '';
        let index = 0;
        for (const [section, items] of Object.entries(grouped)) {
            html += `<section class="py-2 px-4">`;
            html += `<h3 class="text-[10px] font-bold text-[var(--docs-text-muted)] uppercase tracking-[0.12em] px-3 mb-2">${escapeHtml(section)}</h3>`;
            html += `<div class="space-y-0.5">`;
            items.forEach(result => {
                const item = result.item;
                const isApi = item.type === 'api-endpoint';
                html += `<a href="${escapeHtml(item.url)}" class="docs-search-result group flex items-center gap-3 p-2.5 rounded-lg hover:bg-[var(--docs-search-hover-bg)] cursor-pointer transition-colors" data-result-index="${index}">`;
                if (isApi && item.method) {
                    const methodLower = item.method.toLowerCase();
                    const methodColors = {
                        get: 'bg-blue-500/10 text-blue-500',
                        post: 'bg-emerald-500/10 text-emerald-500',
                        put: 'bg-amber-500/10 text-amber-500',
                        patch: 'bg-amber-500/10 text-amber-500',
                        delete: 'bg-red-500/10 text-red-500',
                    };
                    html += `<div class="w-10 h-5 flex items-center justify-center rounded ${methodColors[methodLower] || 'bg-slate-500/10 text-slate-500'} text-[9px] font-bold shrink-0">${item.method.toUpperCase()}</div>`;
                } else {
                    html += `<div class="w-8 h-8 flex items-center justify-center rounded-lg bg-[var(--docs-sidebar-active-bg)] text-[var(--color-primary)] shrink-0"><span class="material-symbols-outlined text-[18px]">${item.icon || 'description'}</span></div>`;
                }
                html += `<div class="min-w-0 flex-1">`;
                html += `<h4 class="text-[var(--docs-text)] font-medium text-sm truncate">${escapeHtml(item.title)}</h4>`;
                if (item.description) {
                    html += `<p class="text-[var(--docs-text-muted)] text-xs mt-0.5 truncate">${escapeHtml(item.description)}</p>`;
                }
                html += `</div>`;
                html += `<span class="material-symbols-outlined text-[16px] text-[var(--docs-text-muted)] opacity-0 group-hover:opacity-100 transition-opacity shrink-0">arrow_forward</span>`;
                html += `</a>`;
                index++;
            });
            html += `</div></section>`;
        }

        resultsContainer.innerHTML = html;
        updateSelectedResult();
    }

    function updateSelectedResult() {
        const results = document.querySelectorAll('.docs-search-result');
        results.forEach((el, i) => {
            const isSelected = i === selectedResultIndex;
            el.classList.toggle('bg-[var(--docs-search-hover-bg)]', isSelected);
            if (isSelected) {
                el.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function navigateToSelectedResult() {
        const results = document.querySelectorAll('.docs-search-result');
        if (results[selectedResultIndex]) {
            results[selectedResultIndex].click();
        }
    }

    function initSearch() {
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                if (searchModalOpen) {
                    closeSearchModal();
                } else {
                    openSearchModal();
                }
            }
            if (e.key === 'Escape' && searchModalOpen) {
                closeSearchModal();
            }
            if (searchModalOpen) {
                const results = document.querySelectorAll('.docs-search-result');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedResultIndex = Math.min(selectedResultIndex + 1, results.length - 1);
                    updateSelectedResult();
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedResultIndex = Math.max(selectedResultIndex - 1, 0);
                    updateSelectedResult();
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    navigateToSelectedResult();
                }
            }
        });

        document.querySelectorAll('[data-search-trigger]').forEach(btn => {
            btn.addEventListener('click', openSearchModal);
        });

        const modal = document.getElementById('docs-search-modal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeSearchModal();
            });

            // Also close when clicking the overlay div
            const overlay = modal.querySelector('.docs-search-overlay');
            if (overlay) {
                overlay.addEventListener('click', closeSearchModal);
            }
        }

        const searchInput = document.getElementById('docs-search-input');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    performSearch(e.target.value);
                }, 150);
            });
        }
    }

    // =========================================================================
    // CODE COPY TO CLIPBOARD
    // =========================================================================
    function initCodeCopy() {
        document.querySelectorAll('[data-copy-code]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const codeBlock = btn.closest('.docs-code-block')?.querySelector('code');
                if (!codeBlock) return;
                try {
                    await navigator.clipboard.writeText(codeBlock.textContent);
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="material-symbols-outlined text-sm">check</span><span>Copied!</span>';
                    btn.classList.add('text-green-400');
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('text-green-400');
                    }, 2000);
                } catch (e) {
                    console.warn('Copy failed:', e);
                }
            });
        });
    }

    // =========================================================================
    // TABLE OF CONTENTS - ACTIVE HIGHLIGHTING
    // =========================================================================
    function initTocHighlight() {
        const tocLinks = document.querySelectorAll('.docs-toc-link');
        if (!tocLinks.length) return;

        const headings = [];
        tocLinks.forEach(link => {
            const id = link.getAttribute('href')?.replace('#', '');
            if (id) {
                const heading = document.getElementById(id);
                if (heading) {
                    headings.push({ id, element: heading, link });
                }
            }
        });

        if (!headings.length) return;

        function updateActiveHeading() {
            let activeIndex = 0;
            const scrollTop = window.scrollY + 100;

            for (let i = headings.length - 1; i >= 0; i--) {
                if (headings[i].element.offsetTop <= scrollTop) {
                    activeIndex = i;
                    break;
                }
            }

            tocLinks.forEach(link => link.classList.remove('active'));
            headings[activeIndex]?.link.classList.add('active');
        }

        window.addEventListener('scroll', updateActiveHeading, { passive: true });
        updateActiveHeading();
    }

    // =========================================================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // =========================================================================
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const id = anchor.getAttribute('href')?.slice(1);
                if (!id) return;
                const target = document.getElementById(id);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    history.pushState(null, '', `#${id}`);
                }
            });
        });
    }

    // =========================================================================
    // MOBILE SIDEBAR TOGGLE
    // =========================================================================
    function initMobileSidebar() {
        const toggleBtn = document.getElementById('docs-mobile-menu-toggle');
        const sidebar = document.getElementById('docs-mobile-sidebar');
        const overlay = document.getElementById('docs-mobile-overlay');
        const closeBtn = document.getElementById('docs-mobile-menu-close');

        if (!toggleBtn || !sidebar) return;

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay?.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay?.classList.add('hidden');
            document.body.style.overflow = '';
        }

        toggleBtn.addEventListener('click', openSidebar);
        overlay?.addEventListener('click', closeSidebar);
        closeBtn?.addEventListener('click', closeSidebar);
    }

    // =========================================================================
    // IMAGE LIGHTBOX
    // =========================================================================
    function initLightbox() {
        let lightboxTrigger = null;

        const overlay = document.createElement('div');
        overlay.className = 'docs-lightbox';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Image preview');
        overlay.innerHTML =
            '<button class="docs-lightbox-close" aria-label="Close"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>' +
            '<img src="" alt="" />';
        document.body.appendChild(overlay);

        const lightboxImg = overlay.querySelector('img');
        const closeBtn = overlay.querySelector('.docs-lightbox-close');

        function openLightbox(src, alt) {
            lightboxTrigger = document.activeElement;
            lightboxImg.src = src;
            lightboxImg.alt = alt || '';
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            closeBtn.focus();
        }

        function closeLightbox() {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            lightboxTrigger?.focus();
            setTimeout(() => {
                if (!overlay.classList.contains('active')) {
                    lightboxImg.src = '';
                }
            }, 300);
        }

        document.querySelectorAll('.docs-content img').forEach(img => {
            img.addEventListener('click', () => {
                openLightbox(img.src, img.alt);
            });
        });

        closeBtn.addEventListener('click', closeLightbox);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeLightbox();
        });
        document.addEventListener('keydown', (e) => {
            if (!overlay.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'Tab') {
                e.preventDefault();
                closeBtn.focus();
            }
        });
    }

    // =========================================================================
    // INITIALIZE
    // =========================================================================
    function init() {
        initMermaidSource();
        initThemeToggle();
        initHeaderScroll();
        initMermaidControls();
        initSearch();
        initCodeCopy();
        initTocHighlight();
        initSmoothScroll();
        initMobileSidebar();
        initLightbox();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
})();
