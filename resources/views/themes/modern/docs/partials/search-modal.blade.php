{{-- Modern Theme — Search Command Palette Modal --}}
<div id="docs-search-modal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true" aria-label="Search documentation">
    {{-- Overlay --}}
    <div class="docs-search-overlay fixed inset-0"></div>

    {{-- Modal --}}
    <div class="relative flex items-start justify-center pt-[12vh] px-4">
        <div class="w-full max-w-[600px] bg-[var(--docs-search-bg)] rounded-xl shadow-2xl border border-[var(--docs-border)] overflow-hidden flex flex-col docs-animate-fade-in" onclick="event.stopPropagation()">
            {{-- Search Input --}}
            <div class="p-4">
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-[var(--docs-search-input-bg)] docs-search-input-highlight">
                    <span class="material-symbols-outlined text-[var(--color-primary)] text-xl">search</span>
                    <input
                        id="docs-search-input"
                        type="text"
                        class="w-full bg-transparent border-none focus:ring-0 text-[var(--docs-text)] text-[15px] placeholder-[var(--docs-text-muted)] font-medium p-0 outline-none"
                        placeholder="Search documentation..."
                        autocomplete="off"
                        aria-label="Search documentation"
                    >
                </div>
            </div>

            {{-- Results --}}
            <div id="docs-search-results" class="max-h-[50vh] overflow-y-auto docs-thin-scrollbar border-t border-[var(--docs-border)]">
                <div class="px-5 py-10 text-center text-sm text-[var(--docs-text-muted)]">
                    <span class="material-symbols-outlined text-2xl mb-2 block opacity-40">travel_explore</span>
                    Type to search documentation...
                </div>
            </div>

            {{-- Footer with keyboard hints --}}
            <footer class="px-5 py-3 border-t border-[var(--docs-border)] bg-[var(--docs-search-footer-bg)] flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <div class="flex items-center gap-1 px-1.5 py-1 bg-[var(--docs-bg-secondary)] border border-[var(--docs-border)] rounded text-[var(--docs-text-muted)]">
                            <span class="material-symbols-outlined text-[12px]">unfold_more</span>
                        </div>
                        <span class="text-[10px] font-semibold text-[var(--docs-text-muted)] uppercase tracking-wider">Navigate</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="px-1.5 py-1 bg-[var(--docs-bg-secondary)] border border-[var(--docs-border)] rounded text-[var(--docs-text-muted)]">
                            <span class="material-symbols-outlined text-[12px]">keyboard_return</span>
                        </div>
                        <span class="text-[10px] font-semibold text-[var(--docs-text-muted)] uppercase tracking-wider">Open</span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="px-2 py-1 bg-[var(--docs-bg-secondary)] border border-[var(--docs-border)] rounded min-w-[32px] text-center text-[var(--docs-text-muted)]">
                        <span class="text-[10px] font-semibold">ESC</span>
                    </div>
                    <span class="text-[10px] font-semibold text-[var(--docs-text-muted)] uppercase tracking-wider">Close</span>
                </div>
            </footer>
        </div>
    </div>
</div>
