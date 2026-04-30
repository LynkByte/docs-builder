{{-- Aurora Theme — Header / Top Navigation --}}
<header class="docs-header sticky top-0 z-50 w-full">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        {{-- Left: Logo + Nav --}}
        <div class="flex items-center gap-6">
            {{-- Mobile menu toggle --}}
            <button id="docs-mobile-menu-toggle" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-[var(--docs-sidebar-hover-bg)] transition-colors" aria-label="Open menu">
                {{-- menu icon --}}
                <svg class="w-5 h-5 text-[var(--docs-text-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>

            {{-- Logo --}}
            <a href="{{ $baseUrl }}/index.html" class="flex items-center gap-2.5 no-underline group">
                <div class="text-[var(--color-primary)]">
                    @if($logo)
                        {!! $logo !!}
                    @else
                        <svg class="size-7" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_aurora_logo)">
                                <path clip-rule="evenodd" d="M24 0.757355L47.2426 24L24 47.2426L0.757355 24L24 0.757355ZM21 35.7574V12.2426L9.24264 24L21 35.7574Z" fill="currentColor" fill-rule="evenodd"></path>
                            </g>
                            <defs><clipPath id="clip0_aurora_logo"><rect fill="white" height="48" width="48"></rect></clipPath></defs>
                        </svg>
                    @endif
                </div>
                <span class="text-lg font-bold tracking-tight text-[var(--docs-text)] group-hover:text-[var(--color-primary)] transition-colors">{{ str_replace(' Documentation', '', $siteName) }}</span>
            </a>

            {{-- Desktop nav links --}}
            <nav class="hidden md:flex items-center gap-1 ml-2">
                @if($headerNav)
                    @foreach($headerNav as $navLink)
                    <a class="docs-nav-link px-3 py-1.5 text-sm font-medium text-[var(--docs-text-muted)] hover:text-[var(--docs-text)] hover:bg-[var(--docs-sidebar-hover-bg)] rounded-lg transition-all" href="{{ $navLink['url'] }}">{{ $navLink['title'] }}</a>
                    @endforeach
                @else
                    <a class="docs-nav-link px-3 py-1.5 text-sm font-medium text-[var(--docs-text-muted)] hover:text-[var(--docs-text)] hover:bg-[var(--docs-sidebar-hover-bg)] rounded-lg transition-all" href="{{ $baseUrl }}/index.html">Guides</a>
                    <a class="docs-nav-link px-3 py-1.5 text-sm font-medium text-[var(--docs-text-muted)] hover:text-[var(--docs-text)] hover:bg-[var(--docs-sidebar-hover-bg)] rounded-lg transition-all" href="{{ $baseUrl }}/api-reference/index.html">API Reference</a>
                @endif
            </nav>
        </div>

        {{-- Right: Search + Social + Theme toggle --}}
        <div class="flex items-center gap-2">
            {{-- Search trigger (desktop) --}}
            <button data-search-trigger class="hidden sm:flex items-center gap-2.5 px-3.5 py-2 text-sm rounded-lg border border-[var(--docs-border)] bg-[var(--docs-surface)] text-[var(--docs-text-muted)] hover:border-[var(--color-primary)] hover:text-[var(--docs-text)] transition-all cursor-pointer outline-none group">
                {{-- search icon --}}
                <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <span class="text-[13px] hidden lg:inline">Search docs...</span>
                <kbd class="ml-1 text-[10px] font-semibold border border-[var(--docs-border)] rounded px-1.5 py-0.5 text-[var(--docs-text-muted)] bg-[var(--docs-bg-secondary)] group-hover:border-[var(--color-primary)]/30 transition-colors">&#8984;K</kbd>
            </button>

            {{-- Search trigger (mobile) --}}
            <button data-search-trigger class="sm:hidden p-2 rounded-lg hover:bg-[var(--docs-sidebar-hover-bg)] transition-colors" aria-label="Search">
                <svg class="w-5 h-5 text-[var(--docs-text-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>

            {{-- Divider --}}
            <div class="w-px h-5 bg-[var(--docs-border)] mx-1 hidden sm:block"></div>

            {{-- Social links --}}
            @if(!empty($socialLinks))
                @foreach($socialLinks as $social)
                <a href="{{ $social['url'] }}" class="p-2 rounded-lg hover:bg-[var(--docs-sidebar-hover-bg)] transition-colors text-[var(--docs-text-muted)] hover:text-[var(--docs-text)]" aria-label="{{ $social['label'] ?? $social['platform'] ?? 'Social' }}" target="_blank" rel="noopener noreferrer">
                    @if(($social['platform'] ?? '') === 'github')
                        {{-- github icon --}}
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.57.1.78-.25.78-.55v-2.16c-3.2.7-3.87-1.36-3.87-1.36-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.76 2.69 1.25 3.35.96.1-.74.4-1.25.73-1.54-2.55-.29-5.23-1.27-5.23-5.66 0-1.25.45-2.27 1.18-3.07-.12-.29-.51-1.45.11-3.02 0 0 .96-.31 3.15 1.17.91-.25 1.89-.38 2.86-.38s1.95.13 2.86.38c2.18-1.48 3.14-1.17 3.14-1.17.62 1.57.23 2.73.11 3.02.74.8 1.18 1.82 1.18 3.07 0 4.4-2.69 5.36-5.25 5.65.41.36.78 1.06.78 2.14v3.17c0 .31.21.66.79.55C20.21 21.39 23.5 17.08 23.5 12 23.5 5.65 18.35.5 12 .5z"/></svg>
                    @else
                        <span class="text-sm font-medium">{{ $social['label'] ?? $social['platform'] ?? '' }}</span>
                    @endif
                </a>
                @endforeach
            @endif

            {{-- Theme toggle --}}
            <button data-theme-toggle class="p-2 rounded-lg hover:bg-[var(--docs-sidebar-hover-bg)] transition-colors" aria-label="Toggle theme">
                {{-- sun icon (shown in dark mode) --}}
                <svg class="w-5 h-5 text-[var(--docs-text-muted)]" data-theme-icon="light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                {{-- moon icon (shown in light mode) --}}
                <svg class="w-5 h-5 text-[var(--docs-text-muted)]" data-theme-icon="dark" style="display:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </button>
        </div>
    </div>
</header>
