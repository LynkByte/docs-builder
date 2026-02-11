@extends('docs-builder::docs.layouts.base')

@section('body')
    {{-- Left Sidebar --}}
    @include('docs-builder::docs.partials.sidebar')

    {{-- Main Content --}}
    <main class="flex-1 min-w-0 pb-16">
        <div class="max-w-[800px] mx-auto px-6 lg:px-12 pt-8">
            {{-- Breadcrumbs --}}
            @if(!empty($breadcrumbs))
            <nav class="flex items-center gap-2 mb-8">
                @foreach($breadcrumbs as $i => $crumb)
                    @if($i > 0)
                        <span class="material-symbols-outlined text-[var(--docs-text-muted)] text-xs">chevron_right</span>
                    @endif
                    @if($loop->last)
                        <span class="text-sm font-medium text-[var(--docs-text)]">{{ $crumb['title'] }}</span>
                    @else
                        <a class="text-sm text-[var(--docs-text-muted)] hover:text-[var(--color-primary)]" href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                    @endif
                @endforeach
            </nav>
            @endif

            {{-- Page Heading --}}
            <div class="mb-8">
                <h1 class="text-4xl lg:text-5xl font-black tracking-tight mb-4 text-[var(--docs-text)]">{{ $pageTitle }}</h1>
                @if(!empty($pageDescription))
                <p class="text-lg text-[var(--docs-text-muted)] leading-relaxed max-w-2xl">{{ $pageDescription }}</p>
                @endif
            </div>

            {{-- Tabs --}}
            <div class="mb-10 border-b border-slate-200 dark:border-[#324d67]">
                <div class="flex gap-8">
                    <a class="flex flex-col items-center justify-center border-b-2 border-[var(--color-primary)] text-[var(--color-primary)] pb-3 pt-2" href="#">
                        <p class="text-sm font-bold tracking-wide">Guide</p>
                    </a>
                    <a class="flex flex-col items-center justify-center border-b-2 border-transparent text-[var(--docs-text-muted)] hover:text-slate-800 dark:hover:text-white pb-3 pt-2" href="{{ $baseUrl }}/api-reference/index.html">
                        <p class="text-sm font-bold tracking-wide">API Reference</p>
                    </a>
                    <a class="flex flex-col items-center justify-center border-b-2 border-transparent text-[var(--docs-text-muted)] hover:text-slate-800 dark:hover:text-white pb-3 pt-2" href="#">
                        <p class="text-sm font-bold tracking-wide">Examples</p>
                    </a>
                </div>
            </div>

            {{-- Content --}}
            <article class="docs-content">
                {!! $content !!}
            </article>

            {{-- Previous / Next Navigation --}}
            @if(!empty($prevPage) || !empty($nextPage))
            <div class="mt-12 pt-8 border-t border-[var(--docs-border)] flex justify-between items-center">
                @if(!empty($prevPage))
                <a class="group flex flex-col gap-1" href="{{ $prevPage['url'] }}">
                    <span class="text-xs text-[var(--docs-text-muted)]">PREVIOUS</span>
                    <span class="text-[var(--color-primary)] font-bold flex items-center gap-1 group-hover:-translate-x-1 transition-transform">
                        <span class="material-symbols-outlined">arrow_back</span> {{ $prevPage['title'] }}
                    </span>
                </a>
                @else
                <div></div>
                @endif
                @if(!empty($nextPage))
                <a class="group flex flex-col items-end gap-1" href="{{ $nextPage['url'] }}">
                    <span class="text-xs text-[var(--docs-text-muted)]">NEXT</span>
                    <span class="text-[var(--color-primary)] font-bold flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                        {{ $nextPage['title'] }} <span class="material-symbols-outlined">arrow_forward</span>
                    </span>
                </a>
                @else
                <div></div>
                @endif
            </div>
            @endif
        </div>
    </main>

    {{-- Right Sidebar (Table of Contents) --}}
    @include('docs-builder::docs.partials.toc')
@endsection

@section('footer')
    @include('docs-builder::docs.partials.footer')
@endsection
