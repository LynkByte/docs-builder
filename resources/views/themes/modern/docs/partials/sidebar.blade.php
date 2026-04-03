{{-- Modern Theme — Left Sidebar Navigation --}}
<aside class="w-[260px] shrink-0 hidden lg:flex flex-col docs-sidebar sticky top-16 h-[calc(100vh-64px)] overflow-y-auto docs-thin-scrollbar">
    <nav class="flex flex-col gap-7 p-5 pt-6">
        @foreach($navigation as $section)
        <div>
            <h3 class="docs-sidebar-section-title px-3 mb-2.5">{{ $section['title'] }}</h3>
            <div class="flex flex-col gap-0.5">
                @foreach($section['pages'] as $page)
                @php
                    $isActive = ($currentPage ?? '') === $page['slug'];
                @endphp
                <a href="{{ $page['url'] }}" class="docs-sidebar-link flex items-center gap-2.5 px-3 py-[7px] text-[13px] group {{ $isActive ? 'active' : 'text-[var(--docs-text-secondary)]' }}">
                    @if(!empty($page['icon']))
                    <span class="material-symbols-outlined text-[17px] {{ $isActive ? 'text-[var(--color-primary)]' : 'text-[var(--docs-text-muted)] group-hover:text-[var(--docs-text-secondary)]' }} transition-colors">{{ $page['icon'] }}</span>
                    @endif
                    <span class="truncate">{{ $page['title'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </nav>
</aside>

{{-- Mobile Sidebar Overlay --}}
<div id="docs-mobile-overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

{{-- Mobile Sidebar --}}
<aside id="docs-mobile-sidebar" class="fixed top-0 left-0 w-72 h-full bg-[var(--docs-sidebar-bg)] border-r border-[var(--docs-border)] z-50 overflow-y-auto docs-thin-scrollbar transform -translate-x-full transition-transform duration-200 lg:hidden">
    <div class="flex items-center justify-between p-4 border-b border-[var(--docs-border)]">
        <span class="text-sm font-bold text-[var(--docs-text)]">Navigation</span>
        <button id="docs-mobile-menu-close" class="p-1.5 rounded-lg hover:bg-[var(--docs-sidebar-hover-bg)] transition-colors" aria-label="Close menu">
            <span class="material-symbols-outlined text-[18px] text-[var(--docs-text-muted)]">close</span>
        </button>
    </div>
    <nav class="flex flex-col gap-6 p-5">
        @foreach($navigation as $section)
        <div>
            <h3 class="docs-sidebar-section-title px-3 mb-2.5">{{ $section['title'] }}</h3>
            <div class="flex flex-col gap-0.5">
                @foreach($section['pages'] as $page)
                @php
                    $isActive = ($currentPage ?? '') === $page['slug'];
                @endphp
                <a href="{{ $page['url'] }}" class="docs-sidebar-link flex items-center gap-2.5 px-3 py-[7px] text-[13px] group {{ $isActive ? 'active' : 'text-[var(--docs-text-secondary)]' }}">
                    @if(!empty($page['icon']))
                    <span class="material-symbols-outlined text-[17px] {{ $isActive ? 'text-[var(--color-primary)]' : 'text-[var(--docs-text-muted)] group-hover:text-[var(--docs-text-secondary)]' }} transition-colors">{{ $page['icon'] }}</span>
                    @endif
                    <span class="truncate">{{ $page['title'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </nav>
</aside>
