{{-- Footer --}}
<footer class="w-full border-t border-[var(--docs-border)] py-8 mt-auto">
    <div class="max-w-[1440px] mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-[var(--docs-text-muted)]">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">copyright</span>
            <span>{{ $footer['copyright'] ?? '' }}</span>
        </div>
        @if(!empty($footer['links']))
        <div class="flex gap-6">
            @foreach($footer['links'] as $link)
            <a class="hover:text-[var(--color-primary)] transition-colors" href="{{ $link['url'] }}">{{ $link['title'] }}</a>
            @endforeach
        </div>
        @endif
    </div>
</footer>
