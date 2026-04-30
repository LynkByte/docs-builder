{{-- Aurora Theme — API Reference Page Layout --}}
@extends('docs-builder::docs.layouts.base')

@section('body')
    {{-- Left Sidebar - API Navigation --}}
    <aside class="w-[240px] shrink-0 hidden lg:flex flex-col docs-sidebar sticky top-16 h-[calc(100vh-64px)] overflow-y-auto docs-thin-scrollbar">
        <nav class="p-4 pt-6">
            {{-- Version badge + static links --}}
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3 px-3">
                    <span class="text-[11px] font-bold uppercase tracking-widest text-[var(--docs-text-muted)]">API Reference</span>
                    <span class="px-2 py-0.5 rounded-md bg-[var(--color-primary)]/10 text-[var(--color-primary)] text-[10px] font-bold">{{ $apiVersion ?? 'v1' }}</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <a href="{{ $baseUrl }}/api-reference/index.html" class="docs-sidebar-link flex items-center gap-2.5 px-3 py-[7px] text-[13px] text-[var(--docs-text-secondary)]">
                        <span class="material-symbols-outlined text-[17px] text-[var(--docs-text-muted)]">home</span>
                        <span>Overview</span>
                    </a>
                    <a href="{{ $baseUrl }}/api-reference/index.html#authentication" class="docs-sidebar-link flex items-center gap-2.5 px-3 py-[7px] text-[13px] text-[var(--docs-text-secondary)]">
                        <span class="material-symbols-outlined text-[17px] text-[var(--docs-text-muted)]">lock</span>
                        <span>Authentication</span>
                    </a>
                </div>
            </div>

            {{-- Endpoint groups --}}
            @if(!empty($apiEndpoints))
            <div>
                <span class="text-[11px] font-bold uppercase tracking-widest text-[var(--docs-text-muted)] mb-3 block px-3">Resources</span>
                <div class="flex flex-col gap-1.5">
                    @foreach($apiEndpoints as $tag => $endpoints)
                    <div>
                        {{-- Tag header --}}
                        <div class="flex items-center justify-between px-3 py-[7px] bg-[var(--docs-sidebar-active-bg)] text-[var(--color-primary)] rounded-lg cursor-pointer">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[17px]">{{ $tagIcons[$tag] ?? 'api' }}</span>
                                <span class="text-[13px] font-bold">{{ $tag }}</span>
                            </div>
                            <span class="material-symbols-outlined text-sm">expand_more</span>
                        </div>
                        {{-- Endpoints --}}
                        <div class="ml-8 mt-1 space-y-0.5">
                            @foreach($endpoints as $endpoint)
                            @php
                                $isEndpointActive = ($currentEndpoint ?? '') === $endpoint['operationId'];
                            @endphp
                            <a href="{{ $endpoint['url'] }}" class="block px-3 py-1.5 text-xs border-l transition-colors {{ $isEndpointActive ? 'text-[var(--color-primary)] font-semibold border-[var(--color-primary)] border-l-2 -ml-[1px]' : 'text-[var(--docs-text-muted)] hover:text-[var(--docs-text-secondary)] border-[var(--docs-border)]' }}">
                                {{ $endpoint['summary'] }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </nav>
    </aside>

    {{-- Main Content & Try It Panel --}}
    <main id="main-content" class="flex-1 flex flex-col lg:flex-row min-w-0">
        {{-- Documentation Content --}}
        <div class="flex-1 max-w-3xl px-6 lg:px-10 py-8 bg-[var(--docs-bg)]">
            {{-- Breadcrumbs --}}
            @if(!empty($breadcrumbs))
            <nav class="flex items-center gap-1.5 mb-6 text-[13px]" aria-label="Breadcrumb">
                @foreach($breadcrumbs as $i => $crumb)
                    @if($i > 0)
                        <span class="docs-breadcrumb-separator text-xs">/</span>
                    @endif
                    @if($loop->last)
                        <span class="font-medium text-[var(--docs-text)]">{{ $crumb['title'] }}</span>
                    @elseif(!empty($crumb['url']))
                        <a class="text-[var(--docs-text-muted)] hover:text-[var(--color-primary)] transition-colors" href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                    @else
                        <span class="text-[var(--docs-text-muted)]">{{ $crumb['title'] }}</span>
                    @endif
                @endforeach
            </nav>
            @endif

            {{-- Endpoint Header --}}
            @if(!empty($endpointMethod) && !empty($endpointPath))
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-3">
                    <span class="docs-method-badge docs-method-{{ strtolower($endpointMethod) }}">{{ strtoupper($endpointMethod) }}</span>
                    <code class="text-[var(--docs-text-muted)] font-mono text-sm">{{ $endpointPath }}</code>
                </div>
                <h1 class="text-3xl font-extrabold text-[var(--docs-text)] tracking-tight mb-3">{{ $pageTitle }}</h1>
                @if(!empty($pageDescription))
                <p class="text-[var(--docs-text-secondary)] text-base leading-relaxed">{{ $pageDescription }}</p>
                @endif
            </div>
            @else
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-[var(--docs-text)] tracking-tight mb-3">{{ $pageTitle }}</h1>
                @if(!empty($pageDescription))
                <p class="text-[var(--docs-text-secondary)] text-base leading-relaxed">{{ $pageDescription }}</p>
                @endif
            </div>
            @endif

            {{-- Content --}}
            <article class="docs-content">
                {!! $content !!}

                {{-- Parameters Tables (grouped by location) --}}
                @php
                    $paramSections = [
                        ['params' => $pathParameters ?? [], 'title' => 'Path Parameters', 'badge' => null],
                        ['params' => $queryParameters ?? [], 'title' => 'Query Parameters', 'badge' => null],
                        ['params' => $bodyParameters ?? [], 'title' => 'Request Body', 'badge' => 'application/json'],
                    ];
                @endphp
                @foreach($paramSections as $section)
                    @if(!empty($section['params']))
                    <div class="mb-10">
                        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                            {{ $section['title'] }}
                            @if($section['badge'])
                            <span class="text-[11px] font-medium text-[var(--docs-text-muted)] px-2 py-0.5 border border-[var(--docs-border)] rounded">{{ $section['badge'] }}</span>
                            @endif
                        </h3>
                        <div class="docs-table-wrapper">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($section['params'] as $param)
                                    <tr>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="font-mono font-semibold text-[var(--docs-text)] text-[13px]">{{ $param['name'] }}</span>
                                                @if($param['required'] ?? false)
                                                <span class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Required</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="px-2 py-0.5 bg-[var(--docs-bg-secondary)] rounded text-[var(--docs-text-muted)] font-mono text-xs">{{ $param['type'] }}</span>
                                        </td>
                                        <td class="text-[var(--docs-text-secondary)]">{{ $param['description'] ?? '' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                @endforeach

                {{-- Responses --}}
                @if(!empty($responses))
                <div class="mb-10">
                    <h3 class="text-lg font-bold mb-4">Responses</h3>
                    <div class="space-y-3">
                        @foreach($responses as $code => $response)
                        @php
                            $borderColor = match(true) {
                                $code >= 200 && $code < 300 => 'border-green-500',
                                $code >= 400 && $code < 500 => 'border-red-500',
                                default => 'border-yellow-500',
                            };
                            $textColor = match(true) {
                                $code >= 200 && $code < 300 => 'text-green-600 dark:text-green-400',
                                $code >= 400 && $code < 500 => 'text-red-600 dark:text-red-400',
                                default => 'text-yellow-600 dark:text-yellow-400',
                            };
                        @endphp
                        <div class="flex items-center gap-4 p-4 rounded-xl border-l-4 {{ $borderColor }} bg-[var(--docs-bg-secondary)]">
                            <span class="font-bold font-mono text-sm {{ $textColor }}">{{ $code }}</span>
                            <span class="text-sm text-[var(--docs-text-secondary)]">{{ $response['description'] ?? '' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </article>
        </div>

        {{-- Try It Out Panel --}}
        @if(!empty($endpointMethod) && !empty($endpointPath))
        <div class="w-full lg:w-[400px] docs-tryit-panel p-5 lg:sticky lg:top-16 h-[calc(100vh-64px)] overflow-y-auto docs-thin-scrollbar">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-widest text-[var(--docs-text-muted)]">Try it out</span>
                    <div class="flex gap-0.5">
                        <button class="px-2.5 py-1 bg-[var(--color-code-header)] rounded text-[10px] font-bold text-white uppercase tracking-wider">Curl</button>
                        <button class="px-2.5 py-1 hover:bg-[var(--color-code-header)] rounded text-[10px] font-bold text-[var(--docs-text-muted)] uppercase tracking-wider transition-colors">Node</button>
                        <button class="px-2.5 py-1 hover:bg-[var(--color-code-header)] rounded text-[10px] font-bold text-[var(--docs-text-muted)] uppercase tracking-wider transition-colors">Python</button>
                    </div>
                </div>
                <div class="relative bg-[var(--color-code-bg)] rounded-lg p-4 font-mono text-xs group border border-[var(--color-code-border)]">
                    <button class="absolute top-3 right-3 p-1.5 bg-[var(--color-code-header)] rounded opacity-0 group-hover:opacity-100 transition-opacity" data-copy-code>
                        {{-- copy icon --}}
                        <svg class="w-3.5 h-3.5 text-[var(--docs-text-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                    </button>
                    <div class="docs-code-block space-y-1" style="border: none; margin: 0; box-shadow: none; background: transparent;">
                        <code class="text-[var(--color-code-text)]">
                            <div><span class="text-[var(--color-primary-light)]">curl</span> --request {{ strtoupper($endpointMethod) }} \</div>
                            <div class="pl-4">--url {{ rtrim($apiServerUrl ?? 'http://localhost:8000/api/v1', '/') }}{{ $endpointPath }} \</div>
                            <div class="pl-4">--header <span class="text-green-400">'Authorization: Bearer &lt;TOKEN&gt;'</span> \</div>
                            <div class="pl-4">--header <span class="text-green-400">'Content-Type: application/json'</span>@if(!empty($bodyParameters)) \@endif</div>
                            @if(!empty($bodyParameters))
                            <div class="pl-4">--data <span class="text-[var(--color-primary-light)]">'{</span></div>
                            @foreach($bodyParameters as $param)
                            <div class="pl-8 text-[var(--color-primary-light)]">"{{ $param['name'] }}": "{{ $param['example'] ?? '' }}"{{ !$loop->last ? ',' : '' }}</div>
                            @endforeach
                            <div class="pl-4 text-[var(--color-primary-light)]">}'</div>
                            @endif
                        </code>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                @if(!empty($bodyParameters))
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-widest text-[var(--docs-text-muted)] mb-2.5 block">Body Parameters</span>
                    <div class="space-y-2.5">
                        @foreach($bodyParameters as $param)
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--docs-text-muted)] mb-1 uppercase tracking-wider">{{ $param['name'] }}</label>
                            <input type="text" class="w-full text-xs py-2 px-3" value="{{ $param['example'] ?? '' }}" placeholder="{{ $param['description'] ?? '' }}">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <button class="w-full py-2.5 bg-[var(--color-primary)] text-white rounded-lg font-semibold text-sm hover:opacity-90 transition-colors flex items-center justify-center gap-2">
                    {{-- play icon --}}
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v16l14-8z"/></svg>
                    Send Request
                </button>
            </div>

            <div class="mt-6 pt-6 border-t border-[var(--color-code-border)]">
                <span class="text-[11px] font-bold uppercase tracking-widest text-[var(--docs-text-muted)] mb-3 block">Response</span>
                <div class="bg-[var(--color-code-bg)] border border-[var(--color-code-border)] rounded-lg p-4 font-mono text-xs min-h-28 text-[var(--docs-text-muted)] flex items-center justify-center">
                    <div class="text-center">
                        {{-- terminal icon --}}
                        <svg class="w-6 h-6 mx-auto mb-1.5 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m8 6-6 6 6 6"/><path d="m16 6 6 6-6 6"/></svg>
                        <span class="text-[11px]">Click "Send Request" to see output</span>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </main>
@endsection

@section('footer')
    @include('docs-builder::docs.partials.footer')
@endsection
