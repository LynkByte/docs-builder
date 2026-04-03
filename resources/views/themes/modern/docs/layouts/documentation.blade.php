{{-- Modern Theme — Documentation Page Layout --}}
@extends('docs-builder::docs.layouts.base')

@section('body')
    {{-- Left Sidebar --}}
    @include('docs-builder::docs.partials.sidebar')

    {{-- Main Content --}}
    <main id="main-content" class="flex-1 min-w-0 pb-20">
        <div class="max-w-[720px] mx-auto px-6 lg:px-10 pt-8">
            {{-- Breadcrumbs --}}
            @if(!empty($breadcrumbs))
            <nav class="flex items-center gap-1.5 mb-6" aria-label="Breadcrumb">
                @foreach($breadcrumbs as $i => $crumb)
                    @if($i > 0)
                        <span class="docs-breadcrumb-separator text-xs">/</span>
                    @endif
                    @if($loop->last)
                        <span class="text-[13px] font-medium text-[var(--docs-text)]">{{ $crumb['title'] }}</span>
                    @else
                        <a class="text-[13px] text-[var(--docs-text-muted)] hover:text-[var(--color-primary)] transition-colors" href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                    @endif
                @endforeach
            </nav>
            @endif

            {{-- Page Heading --}}
            <div class="mb-8">
                <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight mb-3 text-[var(--docs-text)]">{{ $pageTitle }}</h1>
                @if(!empty($pageDescription))
                <p class="text-base text-[var(--docs-text-muted)] leading-relaxed max-w-2xl">{{ $pageDescription }}</p>
                @endif
            </div>

            {{-- Content --}}
            <article class="docs-content">
                {!! $content !!}
            </article>

            {{-- Previous / Next Navigation --}}
            @if(!empty($prevPage) || !empty($nextPage))
            <div class="mt-14 pt-8 border-t border-[var(--docs-border)] flex gap-4">
                @if(!empty($prevPage))
                <a class="docs-prev-next-link group" href="{{ $prevPage['url'] }}">
                    <span class="text-[11px] font-semibold text-[var(--docs-text-muted)] uppercase tracking-wider">Previous</span>
                    <span class="text-[var(--color-primary)] font-semibold text-sm flex items-center gap-1.5 group-hover:-translate-x-0.5 transition-transform">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        <span class="truncate">{{ $prevPage['title'] }}</span>
                    </span>
                </a>
                @else
                <div class="flex-1"></div>
                @endif
                @if(!empty($nextPage))
                <a class="docs-prev-next-link group items-end text-right" href="{{ $nextPage['url'] }}">
                    <span class="text-[11px] font-semibold text-[var(--docs-text-muted)] uppercase tracking-wider">Next</span>
                    <span class="text-[var(--color-primary)] font-semibold text-sm flex items-center justify-end gap-1.5 group-hover:translate-x-0.5 transition-transform">
                        <span class="truncate">{{ $nextPage['title'] }}</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </span>
                </a>
                @else
                <div class="flex-1"></div>
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
