{{-- Modern Theme — Footer --}}
<footer class="w-full border-t border-[var(--docs-border)] mt-auto">
    <div class="max-w-[1400px] mx-auto px-6 py-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-[13px] text-[var(--docs-text-muted)]">
        <div class="flex items-center gap-1.5">
            <span>{{ $footer['copyright'] ?? '' }}</span>
        </div>
        @if(!empty($footer['links']))
        <div class="flex items-center gap-5">
            @foreach($footer['links'] as $link)
            <a class="hover:text-[var(--color-primary)] transition-colors" href="{{ $link['url'] }}">{{ $link['title'] }}</a>
            @endforeach
        </div>
        @endif
    </div>
</footer>
