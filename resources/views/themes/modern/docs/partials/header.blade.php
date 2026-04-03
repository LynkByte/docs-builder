{{-- Modern Theme — Header / Top Navigation --}}
<header class="docs-header sticky top-0 z-50 w-full">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        {{-- Left: Logo + Nav --}}
        <div class="flex items-center gap-6">
            {{-- Mobile menu toggle --}}
            <button id="docs-mobile-menu-toggle" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-[var(--docs-sidebar-hover-bg)] transition-colors" aria-label="Open menu">
                <span class="material-symbols-outlined text-[var(--docs-text-muted)]">menu</span>
            </button>

            {{-- Logo --}}
            <a href="{{ $baseUrl }}/index.html" class="flex items-center gap-2.5 no-underline group">
                <div class="text-[var(--color-primary)]">
                    @if($logo)
                        {!! $logo !!}
                    @else
                        <svg class="size-7" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_modern_logo)">
                                <path clip-rule="evenodd" d="M24 0.757355L47.2426 24L24 47.2426L0.757355 24L24 0.757355ZM21 35.7574V12.2426L9.24264 24L21 35.7574Z" fill="currentColor" fill-rule="evenodd"></path>
                            </g>
                            <defs><clipPath id="clip0_modern_logo"><rect fill="white" height="48" width="48"></rect></clipPath></defs>
                        </svg>
                    @endif
                </div>
                <span class="text-lg font-bold tracking-tight text-[var(--docs-text)] group-hover:text-[var(--color-primary)] transition-colors">{{ str_replace(' Documentation', '', $siteName) }}</span>
            </a>

            {{-- Desktop nav links --}}
            <nav class="hidden md:flex items-center gap-1 ml-2">
                @if($headerNav)
                    @foreach($headerNav as $navLink)
                    <a class="px-3 py-1.5 text-sm font-medium text-[var(--docs-text-muted)] hover:text-[var(--docs-text)] hover:bg-[var(--docs-sidebar-hover-bg)] rounded-lg transition-all" href="{{ $navLink['url'] }}">{{ $navLink['title'] }}</a>
                    @endforeach
                @else
                    <a class="px-3 py-1.5 text-sm font-medium text-[var(--docs-text-muted)] hover:text-[var(--docs-text)] hover:bg-[var(--docs-sidebar-hover-bg)] rounded-lg transition-all" href="{{ $baseUrl }}/index.html">Guides</a>
                    <a class="px-3 py-1.5 text-sm font-medium text-[var(--docs-text-muted)] hover:text-[var(--docs-text)] hover:bg-[var(--docs-sidebar-hover-bg)] rounded-lg transition-all" href="{{ $baseUrl }}/api-reference/index.html">API Reference</a>
                @endif
            </nav>
        </div>

        {{-- Right: Search + Theme toggle --}}
        <div class="flex items-center gap-2">
            {{-- Search trigger --}}
            <button data-search-trigger class="hidden sm:flex items-center gap-2.5 px-3.5 py-2 text-sm rounded-lg border border-[var(--docs-border)] bg-[var(--docs-surface)] text-[var(--docs-text-muted)] hover:border-[var(--color-primary)] hover:text-[var(--docs-text)] transition-all cursor-pointer outline-none group">
                <span class="material-symbols-outlined text-[18px]">search</span>
                <span class="text-[13px] hidden lg:inline">Search docs...</span>
                <kbd class="ml-1 text-[10px] font-semibold border border-[var(--docs-border)] rounded px-1.5 py-0.5 text-[var(--docs-text-muted)] bg-[var(--docs-bg-secondary)] group-hover:border-[var(--color-primary)]/30 transition-colors">&#8984;K</kbd>
            </button>

            {{-- Mobile search --}}
            <button data-search-trigger class="sm:hidden p-2 rounded-lg hover:bg-[var(--docs-sidebar-hover-bg)] transition-colors" aria-label="Search">
                <span class="material-symbols-outlined text-[var(--docs-text-muted)]">search</span>
            </button>

            {{-- Divider --}}
            <div class="w-px h-5 bg-[var(--docs-border)] mx-1 hidden sm:block"></div>

            {{-- Theme toggle --}}
            <button data-theme-toggle class="p-2 rounded-lg hover:bg-[var(--docs-sidebar-hover-bg)] transition-colors" aria-label="Toggle theme">
                <span class="material-symbols-outlined text-[var(--docs-text-muted)]" data-theme-icon="light">light_mode</span>
                <span class="material-symbols-outlined text-[var(--docs-text-muted)]" data-theme-icon="dark" style="display:none;">dark_mode</span>
            </button>
        </div>
    </div>
</header>
