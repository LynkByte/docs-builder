{{-- TopNavBar --}}
<header class="docs-header sticky top-0 z-50 w-full">
    <div class="max-w-[1440px] mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-8">
            {{-- Mobile menu toggle --}}
            <button id="docs-mobile-menu-toggle" class="lg:hidden p-2 rounded-lg hover:bg-[var(--color-primary)]/10 transition-colors">
                <span class="material-symbols-outlined">menu</span>
            </button>

            {{-- Logo --}}
            <a href="{{ $baseUrl }}/index.html" class="flex items-center gap-3 no-underline">
                <div class="text-[var(--color-primary)]">
                    @if($logo)
                        {!! $logo !!}
                    @else
                        <svg class="size-8" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_logo)">
                                <path clip-rule="evenodd" d="M24 0.757355L47.2426 24L24 47.2426L0.757355 24L24 0.757355ZM21 35.7574V12.2426L9.24264 24L21 35.7574Z" fill="currentColor" fill-rule="evenodd"></path>
                            </g>
                            <defs><clipPath id="clip0_logo"><rect fill="white" height="48" width="48"></rect></clipPath></defs>
                        </svg>
                    @endif
                </div>
                <h2 class="text-xl font-bold leading-tight tracking-tight text-[var(--docs-text)]">{{ str_replace(' Documentation', '', $siteName) }}</h2>
            </a>

            {{-- Desktop nav links --}}
            <div class="hidden md:flex items-center gap-6 ml-4">
                @if($headerNav)
                    @foreach($headerNav as $navLink)
                    <a class="text-sm font-medium text-[var(--docs-text-secondary)] hover:text-[var(--color-primary)] transition-colors" href="{{ $navLink['url'] }}">{{ $navLink['title'] }}</a>
                    @endforeach
                @else
                    <a class="text-sm font-medium text-[var(--docs-text-secondary)] hover:text-[var(--color-primary)] transition-colors" href="{{ $baseUrl }}/index.html">Guides</a>
                    <a class="text-sm font-medium text-[var(--docs-text-secondary)] hover:text-[var(--color-primary)] transition-colors" href="{{ $baseUrl }}/api-reference/index.html">API Reference</a>
                    <a class="text-sm font-medium text-[var(--docs-text-secondary)] hover:text-[var(--color-primary)] transition-colors" href="#">Examples</a>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-4">
            {{-- Search trigger --}}
            <button data-search-trigger class="hidden sm:flex relative items-center w-64 pl-10 pr-4 py-2 text-sm rounded-lg bg-slate-200/50 dark:bg-[#233648] border-none text-[var(--docs-text-muted)] hover:ring-2 hover:ring-[var(--color-primary)] transition-all cursor-pointer text-left outline-none">
                <span class="material-symbols-outlined absolute left-3 text-[var(--docs-text-muted)]">search</span>
                <span>Search documentation...</span>
                <kbd class="ml-auto text-[10px] font-bold border border-[var(--docs-border)] rounded px-1.5 py-0.5">⌘K</kbd>
            </button>

            <div class="flex items-center gap-2 border-l border-[var(--docs-border)] pl-4">
                {{-- Theme toggle --}}
                <button data-theme-toggle class="p-2 rounded-lg bg-slate-200/50 dark:bg-[#233648] hover:bg-[var(--color-primary)]/20 transition-colors">
                    <span class="material-symbols-outlined" data-theme-icon="light">light_mode</span>
                    <span class="material-symbols-outlined" data-theme-icon="dark" style="display:none;">dark_mode</span>
                </button>
            </div>
        </div>
    </div>
</header>
