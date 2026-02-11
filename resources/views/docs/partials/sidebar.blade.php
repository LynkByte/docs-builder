{{-- Left Sidebar Navigation --}}
<aside class="w-64 shrink-0 hidden lg:block docs-sidebar sticky top-16 h-[calc(100vh-64px)] overflow-y-auto p-6 docs-thin-scrollbar">
    <div class="flex flex-col gap-6">
        @foreach($navigation as $section)
        <div>
            <h3 class="docs-sidebar-section-title text-[var(--docs-text-muted)] mb-4">{{ $section['title'] }}</h3>
            <div class="flex flex-col gap-1">
                @foreach($section['pages'] as $page)
                @php
                    $isActive = ($currentPage ?? '') === $page['slug'];
                @endphp
                <a href="{{ $page['url'] }}" class="docs-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm group {{ $isActive ? 'active font-semibold' : 'font-medium text-[var(--docs-text-secondary)]' }}">
                    @if(!empty($page['icon']))
                    <span class="material-symbols-outlined text-[18px] {{ $isActive ? 'text-[var(--color-primary)]' : 'text-[var(--docs-text-muted)] group-hover:text-[var(--color-primary)]' }}">{{ $page['icon'] }}</span>
                    @endif
                    <span>{{ $page['title'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</aside>

{{-- Mobile Sidebar Overlay --}}
<div id="docs-mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

{{-- Mobile Sidebar --}}
<aside id="docs-mobile-sidebar" class="fixed top-0 left-0 w-72 h-full docs-sidebar z-50 overflow-y-auto p-6 docs-thin-scrollbar transform -translate-x-full transition-transform lg:hidden">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-bold text-[var(--docs-text)]">Navigation</h2>
        <button id="docs-mobile-menu-close" class="p-2 rounded-lg hover:bg-[var(--color-primary)]/10">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <div class="flex flex-col gap-6">
        @foreach($navigation as $section)
        <div>
            <h3 class="docs-sidebar-section-title text-[var(--docs-text-muted)] mb-4">{{ $section['title'] }}</h3>
            <div class="flex flex-col gap-1">
                @foreach($section['pages'] as $page)
                @php
                    $isActive = ($currentPage ?? '') === $page['slug'];
                @endphp
                <a href="{{ $page['url'] }}" class="docs-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm group {{ $isActive ? 'active font-semibold' : 'font-medium text-[var(--docs-text-secondary)]' }}">
                    @if(!empty($page['icon']))
                    <span class="material-symbols-outlined text-[18px] {{ $isActive ? 'text-[var(--color-primary)]' : 'text-[var(--docs-text-muted)] group-hover:text-[var(--color-primary)]' }}">{{ $page['icon'] }}</span>
                    @endif
                    <span>{{ $page['title'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</aside>
