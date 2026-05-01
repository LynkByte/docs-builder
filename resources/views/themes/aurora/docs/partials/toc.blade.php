{{-- Aurora Theme — Right Sidebar / Table of Contents --}}
@if(!empty($tableOfContents))
<aside class="w-56 shrink-0 hidden xl:block sticky top-16 h-[calc(100vh-64px)] overflow-y-auto docs-thin-scrollbar">
    <div class="py-8 pr-4 pl-6">
        <h4 class="text-[11px] font-bold uppercase tracking-widest text-[var(--docs-text-muted)] mb-4">On this page</h4>
        <nav class="flex flex-col">
            @foreach($tableOfContents as $heading)
            <a href="#{{ $heading['id'] }}" class="docs-toc-link py-1.5 text-[var(--docs-text-muted)] hover:text-[var(--docs-text)] transition-colors" style="padding-left: {{ ($heading['level'] - 2) * 0.875 + 0.75 }}rem;">
                {{ $heading['text'] }}
            </a>
            @endforeach
        </nav>

        {{-- Help Card — only shown when a support URL is configured --}}
        @php $supportUrl = config('docs-builder.support_url'); @endphp
        @if(!empty($supportUrl))
        <div class="mt-10 p-4 rounded-xl bg-[var(--docs-sidebar-active-bg)] border border-[var(--color-primary)]/10">
            <p class="text-xs font-bold text-[var(--color-primary)] mb-1.5">Need help?</p>
            <p class="text-xs text-[var(--docs-text-muted)] leading-relaxed mb-3">Can't find what you're looking for?</p>
            <a href="{{ $supportUrl }}" class="block w-full py-2 bg-[var(--color-primary)] hover:opacity-90 text-white rounded-lg text-xs font-semibold transition-colors text-center">
                Contact Support
            </a>
        </div>
        @endif
    </div>
</aside>
@endif
