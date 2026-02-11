{{-- Search Command Palette Modal --}}
<div id="docs-search-modal" class="fixed inset-0 z-[100] hidden">
    {{-- Overlay --}}
    <div class="docs-search-overlay fixed inset-0"></div>

    {{-- Modal --}}
    <div class="relative flex items-start justify-center pt-[10vh] px-4">
        <div class="w-full max-w-[640px] bg-white dark:bg-[#0b121b] rounded-xl shadow-2xl border border-[var(--docs-border)] overflow-hidden flex flex-col" onclick="event.stopPropagation()">
            {{-- Search Input --}}
            <div class="px-5 py-4">
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-slate-100 dark:bg-[#161f2a] docs-search-input-highlight">
                    <span class="material-symbols-outlined text-[var(--color-primary)] text-xl">search</span>
                    <input
                        id="docs-search-input"
                        type="text"
                        class="w-full bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white text-base placeholder-slate-400 dark:placeholder-slate-500 font-medium p-0 outline-none"
                        placeholder="Search documentation, APIs, and guides..."
                        autocomplete="off"
                    >
                </div>
            </div>

            {{-- Results --}}
            <div id="docs-search-results" class="max-h-[60vh] overflow-y-auto docs-thin-scrollbar">
                <div class="px-5 py-8 text-center text-sm text-slate-500">Type to search documentation...</div>
            </div>

            {{-- Footer --}}
            <footer class="px-6 py-4 border-t border-[var(--docs-border)] bg-slate-50 dark:bg-[#090f17] flex items-center justify-between">
                <div class="flex items-center gap-5">
                    <div class="flex items-center gap-2.5">
                        <div class="flex items-center gap-1.5 p-1.5 bg-slate-200/50 dark:bg-[#1e293b]/50 border border-slate-300 dark:border-slate-700 rounded shadow-sm">
                            <span class="material-symbols-outlined text-[14px] text-slate-400">unfold_more</span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Select</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 bg-slate-200/50 dark:bg-[#1e293b]/50 border border-slate-300 dark:border-slate-700 rounded shadow-sm">
                            <span class="material-symbols-outlined text-[14px] text-slate-400">keyboard_return</span>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Enter</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="px-2 py-1.5 bg-slate-200/50 dark:bg-[#1e293b]/50 border border-slate-300 dark:border-slate-700 rounded shadow-sm min-w-[36px] text-center">
                        <span class="text-[10px] font-bold text-slate-400">ESC</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Close</span>
                </div>
            </footer>
        </div>
    </div>
</div>
