{{-- Right Sidebar - Table of Contents --}}
@if(!empty($tableOfContents))
<aside class="w-64 shrink-0 hidden xl:block sticky top-16 h-[calc(100vh-64px)] overflow-y-auto p-8 docs-thin-scrollbar">
    <h4 class="text-xs font-bold uppercase tracking-widest text-[var(--docs-text-muted)] mb-6">On this page</h4>
    <nav class="flex flex-col gap-4">
        @foreach($tableOfContents as $heading)
        <a href="#{{ $heading['id'] }}" class="docs-toc-link text-sm pl-{{ ($heading['level'] - 2) * 4 + 4 }} text-[var(--docs-text-muted)] hover:text-slate-100 transition-colors" style="padding-left: {{ ($heading['level'] - 2) * 1 + 1 }}rem;">
            {{ $heading['text'] }}
        </a>
        @endforeach
    </nav>
    <div class="mt-12 p-4 rounded-xl bg-[var(--color-primary)]/10 border border-[var(--color-primary)]/20">
        <p class="text-xs font-bold text-[var(--color-primary)] mb-2 uppercase">Need help?</p>
        <p class="text-xs text-[var(--docs-text-muted)] mb-4">Can't find what you're looking for?</p>
        <a href="#" class="block w-full py-2 bg-[var(--color-primary)] hover:bg-[var(--color-primary)]/90 text-white rounded-lg text-xs font-bold transition-all shadow-lg shadow-[var(--color-primary)]/20 text-center">
            Contact Support
        </a>
    </div>
</aside>
@endif
