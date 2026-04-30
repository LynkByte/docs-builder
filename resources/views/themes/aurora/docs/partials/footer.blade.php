{{-- Aurora Theme — Footer --}}
<footer class="w-full border-t border-[var(--docs-border)] mt-auto">
    <div class="max-w-[1400px] mx-auto px-6 py-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-[13px] text-[var(--docs-text-muted)]">
        <div class="flex items-center gap-1.5">
            <span>{{ $footer['copyright'] ?? '' }}</span>
        </div>
        <div class="flex items-center gap-5">
            @if(!empty($footer['links']))
                @foreach($footer['links'] as $linkGroup)
                    @if(!empty($linkGroup['items']))
                        @foreach($linkGroup['items'] as $link)
                        <a class="hover:text-[var(--color-primary)] transition-colors" href="{{ $link['url'] }}">{{ $link['title'] }}</a>
                        @endforeach
                    @elseif(!empty($linkGroup['url']))
                        <a class="hover:text-[var(--color-primary)] transition-colors" href="{{ $linkGroup['url'] }}">{{ $linkGroup['title'] }}</a>
                    @endif
                @endforeach
            @endif
            @if(!empty($socialLinks))
                @foreach($socialLinks as $social)
                <a class="hover:text-[var(--color-primary)] transition-colors" href="{{ $social['url'] }}" target="_blank" rel="noopener">{{ $social['label'] ?? $social['platform'] ?? '' }}</a>
                @endforeach
            @endif
        </div>
    </div>
</footer>
