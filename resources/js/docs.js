import Fuse from 'fuse.js';

/*
|--------------------------------------------------------------------------
| LynkByte DevDocs - Client-Side JavaScript
|--------------------------------------------------------------------------
| Handles: theme toggle, search command palette, code copy, TOC highlight
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

        // Listen for OS preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(THEME_KEY)) {
                applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // =========================================================================
    // MERMAID DIAGRAM THEME SYNC
    // =========================================================================
    function initMermaidSource() {
        document.querySelectorAll('.docs-mermaid-block pre.mermaid').forEach(el => {
            if (!el.getAttribute('data-mermaid-source')) {
                el.setAttribute('data-mermaid-source', el.textContent);
            }
        });
    }

    async function reRenderMermaidDiagrams(theme) {
        if (typeof mermaid === 'undefined') {
            return;
        }

        const blocks = document.querySelectorAll('.docs-mermaid-block pre.mermaid');
        if (!blocks.length) {
            return;
        }

        mermaid.initialize({
            startOnLoad: false,
            theme: theme === 'dark' ? 'dark' : 'default',
        });

        for (let i = 0; i < blocks.length; i++) {
            const el = blocks[i];
            const source = el.getAttribute('data-mermaid-source');
            if (!source) {
                continue;
            }
            try {
                const { svg } = await mermaid.render('mermaid-re-' + i, source);
                el.innerHTML = svg;
            } catch (e) {
                console.warn('Mermaid re-render failed:', e);
            }
        }
    }

    // =========================================================================
    // SEARCH COMMAND PALETTE
    // =========================================================================
    let searchIndex = null;
    let fuse = null;
    let searchModalOpen = false;
    let selectedResultIndex = 0;

    async function loadSearchIndex() {
        if (searchIndex) {
            return;
        }
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
        if (!modal) {
            return;
        }
        searchModalOpen = true;
        modal.classList.remove('hidden');
        modal.querySelector('input')?.focus();
        selectedResultIndex = 0;
        loadSearchIndex();
        document.body.style.overflow = 'hidden';
    }

    function closeSearchModal() {
        const modal = document.getElementById('docs-search-modal');
        if (!modal) {
            return;
        }
        searchModalOpen = false;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function performSearch(query) {
        const resultsContainer = document.getElementById('docs-search-results');
        if (!resultsContainer || !fuse) {
            return;
        }

        if (!query || query.length < 2) {
            resultsContainer.innerHTML = '<div class="px-5 py-8 text-center text-sm text-slate-500">Type to search documentation...</div>';
            return;
        }

        const results = fuse.search(query, { limit: 15 });
        selectedResultIndex = 0;

        if (results.length === 0) {
            resultsContainer.innerHTML = '<div class="px-5 py-8 text-center text-sm text-slate-500">No results found.</div>';
            return;
        }

        // Group results by section
        const grouped = {};
        results.forEach(result => {
            const section = result.item.section || 'Documentation';
            if (!grouped[section]) {
                grouped[section] = [];
            }
            grouped[section].push(result);
        });

        let html = '';
        let index = 0;
        for (const [section, items] of Object.entries(grouped)) {
            html += `<section class="mt-2 mb-6 px-5">`;
            html += `<h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em] px-3 mb-4">${escapeHtml(section)}</h3>`;
            html += `<div class="space-y-1">`;
            items.forEach(result => {
                const item = result.item;
                const isApi = item.type === 'api-endpoint';
                html += `<a href="${escapeHtml(item.url)}" class="docs-search-result group flex items-center gap-4 p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/40 cursor-pointer transition-colors" data-result-index="${index}">`;
                if (isApi && item.method) {
                    const methodLower = item.method.toLowerCase();
                    const methodColors = {
                        get: 'bg-blue-500/10 text-blue-500',
                        post: 'bg-emerald-500/10 text-emerald-500',
                        put: 'bg-amber-500/10 text-amber-500',
                        patch: 'bg-amber-500/10 text-amber-500',
                        delete: 'bg-red-500/10 text-red-500',
                    };
                    html += `<div class="w-12 h-6 flex items-center justify-center rounded ${methodColors[methodLower] || 'bg-slate-500/10 text-slate-500'} text-[10px] font-bold">${item.method.toUpperCase()}</div>`;
                } else {
                    html += `<div class="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-500/10 text-primary shrink-0 border border-blue-500/20"><span class="material-symbols-outlined text-xl">${item.icon || 'description'}</span></div>`;
                }
                html += `<div class="min-w-0 flex-1">`;
                html += `<h4 class="text-slate-800 dark:text-slate-200 font-semibold text-sm truncate">${escapeHtml(item.title)}</h4>`;
                if (item.description) {
                    html += `<p class="text-slate-500 text-xs mt-0.5 truncate">${escapeHtml(item.description)}</p>`;
                }
                html += `</div></a>`;
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
            el.classList.toggle('bg-slate-100', isSelected);
            el.classList.toggle('dark:bg-slate-800/40', isSelected);
        });
    }

    function navigateToSelectedResult() {
        const results = document.querySelectorAll('.docs-search-result');
        if (results[selectedResultIndex]) {
            results[selectedResultIndex].click();
        }
    }

    function initSearch() {
        // Open search with Ctrl+K / Cmd+K
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

        // Search trigger buttons
        document.querySelectorAll('[data-search-trigger]').forEach(btn => {
            btn.addEventListener('click', openSearchModal);
        });

        // Close when clicking overlay
        const modal = document.getElementById('docs-search-modal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeSearchModal();
                }
            });
        }

        // Search input handler
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
                if (!codeBlock) {
                    return;
                }
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
        if (!tocLinks.length) {
            return;
        }

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

        if (!headings.length) {
            return;
        }

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
                if (!id) {
                    return;
                }
                const target = document.getElementById(id);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    // Update URL without scrolling
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

        if (!toggleBtn || !sidebar) {
            return;
        }

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay?.classList.toggle('hidden');
        });

        overlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }

    // =========================================================================
    // INITIALIZE
    // =========================================================================
    function init() {
        initMermaidSource();
        initThemeToggle();
        initSearch();
        initCodeCopy();
        initTocHighlight();
        initSmoothScroll();
        initMobileSidebar();
    }

    // Run on DOM ready
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
