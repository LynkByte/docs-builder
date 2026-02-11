@extends('docs-builder::docs.layouts.base')

@section('body')
    {{-- Left Sidebar - API Navigation --}}
    <aside class="w-72 shrink-0 hidden lg:flex flex-col docs-sidebar sticky top-16 h-[calc(100vh-64px)] overflow-y-auto p-6 docs-thin-scrollbar">
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-[var(--docs-text-muted)]">Version</span>
                <span class="px-2 py-0.5 rounded bg-[var(--color-primary)]/10 text-[var(--color-primary)] text-[10px] font-bold">{{ $apiVersion ?? 'v1' }}</span>
            </div>
            <div class="space-y-1">
                <a href="{{ $baseUrl }}/api-reference/index.html" class="flex items-center gap-3 px-3 py-2 text-[var(--docs-text-secondary)] hover:bg-[var(--color-primary)]/10 rounded-lg">
                    <span class="material-symbols-outlined text-[20px]">home</span>
                    <span class="text-sm font-medium">Overview</span>
                </a>
                <a href="{{ $baseUrl }}/api-reference/index.html#authentication" class="flex items-center gap-3 px-3 py-2 text-[var(--docs-text-secondary)] hover:bg-[var(--color-primary)]/10 rounded-lg">
                    <span class="material-symbols-outlined text-[20px]">lock</span>
                    <span class="text-sm font-medium">Authentication</span>
                </a>
            </div>
        </div>
        @if(!empty($apiEndpoints))
        <div class="mb-8">
            <span class="text-xs font-bold uppercase tracking-wider text-[var(--docs-text-muted)] mb-4 block">Resources</span>
            <div class="space-y-1">
                @foreach($apiEndpoints as $tag => $endpoints)
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between px-3 py-2 bg-[var(--color-primary)]/5 text-[var(--color-primary)] rounded-lg cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[20px]">{{ $tagIcons[$tag] ?? 'api' }}</span>
                            <span class="text-sm font-bold">{{ $tag }}</span>
                        </div>
                        <span class="material-symbols-outlined text-sm">expand_more</span>
                    </div>
                    <div class="ml-9 mt-1 space-y-1">
                        @foreach($endpoints as $endpoint)
                        <a href="{{ $endpoint['url'] }}" class="block px-3 py-1.5 text-xs text-[var(--docs-text-muted)] hover:text-[var(--color-primary)] border-l border-[var(--docs-border)] {{ ($currentEndpoint ?? '') === $endpoint['operationId'] ? 'text-[var(--color-primary)] font-semibold !border-l-2 !border-[var(--color-primary)] -ml-[1px]' : '' }}">
                            {{ $endpoint['summary'] }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </aside>

    {{-- Main Content & Try It Panel --}}
    <main class="flex-1 flex flex-col lg:flex-row min-w-0">
        {{-- Documentation Content --}}
        <div class="flex-1 max-w-4xl px-8 lg:px-12 py-10 bg-[var(--docs-bg)]">
            {{-- Breadcrumbs --}}
            @if(!empty($breadcrumbs))
            <nav class="flex items-center gap-2 mb-8 text-sm font-medium">
                @foreach($breadcrumbs as $i => $crumb)
                    @if($i > 0)
                        <span class="text-[var(--docs-border)]">/</span>
                    @endif
                    @if($loop->last)
                        <span class="text-[var(--docs-text)]">{{ $crumb['title'] }}</span>
                    @else
                        <a class="text-[var(--docs-text-muted)] hover:text-[var(--docs-text-secondary)] transition-colors" href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                    @endif
                @endforeach
            </nav>
            @endif

            {{-- Endpoint Header (if this is an endpoint page) --}}
            @if(!empty($endpointMethod) && !empty($endpointPath))
            <div class="mb-10">
                <div class="flex items-center gap-3 mb-4">
                    <span class="docs-method-badge docs-method-{{ strtolower($endpointMethod) }}">{{ strtoupper($endpointMethod) }}</span>
                    <code class="text-[var(--docs-text-muted)] font-mono text-sm">{{ $endpointPath }}</code>
                </div>
                <h1 class="text-4xl font-black text-[var(--docs-text)] tracking-tight mb-4">{{ $pageTitle }}</h1>
                @if(!empty($pageDescription))
                <p class="text-[var(--docs-text-secondary)] text-lg leading-relaxed">{{ $pageDescription }}</p>
                @endif
            </div>
            @else
            <div class="mb-10">
                <h1 class="text-4xl font-black text-[var(--docs-text)] tracking-tight mb-4">{{ $pageTitle }}</h1>
                @if(!empty($pageDescription))
                <p class="text-[var(--docs-text-secondary)] text-lg leading-relaxed">{{ $pageDescription }}</p>
                @endif
            </div>
            @endif

            {{-- Content --}}
            <article class="docs-content">
                {!! $content !!}

                {{-- Parameters Table (auto-generated from OpenAPI) --}}
                @if(!empty($parameters))
                <div class="mb-12">
                    <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                        Request Body
                        <span class="text-xs font-normal text-[var(--docs-text-muted)] px-2 py-0.5 border border-[var(--docs-border)] rounded">application/json</span>
                    </h3>
                    <div class="border border-[var(--docs-border)] rounded-xl overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-[var(--docs-bg-secondary)] border-b border-[var(--docs-border)]">
                                <tr>
                                    <th class="px-6 py-4 font-bold text-[var(--docs-text)]">Parameter</th>
                                    <th class="px-6 py-4 font-bold text-[var(--docs-text)]">Type</th>
                                    <th class="px-6 py-4 font-bold text-[var(--docs-text)]">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--docs-border)]">
                                @foreach($parameters as $param)
                                <tr>
                                    <td class="px-6 py-5">
                                        <div class="flex flex-col">
                                            <span class="font-mono font-bold text-[var(--docs-text)]">{{ $param['name'] }}</span>
                                            @if($param['required'] ?? false)
                                            <span class="text-[10px] font-bold text-red-500 uppercase mt-1 tracking-wider">Required</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span class="px-2 py-1 bg-[var(--docs-bg-secondary)] rounded text-[var(--docs-text-muted)] font-mono text-xs">{{ $param['type'] }}</span>
                                    </td>
                                    <td class="px-6 py-5 text-[var(--docs-text-secondary)] leading-relaxed">{{ $param['description'] ?? '' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Responses (auto-generated from OpenAPI) --}}
                @if(!empty($responses))
                <div class="mb-12">
                    <h3 class="text-xl font-bold mb-6">Responses</h3>
                    <div class="space-y-4">
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
                            <span class="font-bold {{ $textColor }}">{{ $code }}</span>
                            <span class="text-sm font-medium text-[var(--docs-text-secondary)]">{{ $response['description'] ?? '' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </article>
        </div>

        {{-- Try It Out Panel --}}
        @if(!empty($endpointMethod) && !empty($endpointPath))
        <div class="w-full lg:w-[420px] docs-tryit-panel p-6 lg:sticky lg:top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-slate-800 docs-thin-scrollbar">
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-500">Try it out</span>
                    <div class="flex gap-1">
                        <button class="px-3 py-1 bg-slate-800 rounded text-[10px] font-bold text-white uppercase tracking-wider">Curl</button>
                        <button class="px-3 py-1 hover:bg-slate-800 rounded text-[10px] font-bold text-slate-500 uppercase tracking-wider transition-colors">Node</button>
                        <button class="px-3 py-1 hover:bg-slate-800 rounded text-[10px] font-bold text-slate-500 uppercase tracking-wider transition-colors">Python</button>
                    </div>
                </div>
                <div class="relative bg-black rounded-lg p-4 font-mono text-xs group">
                    <button class="absolute top-3 right-3 p-1.5 bg-slate-800 rounded opacity-0 group-hover:opacity-100 transition-opacity" data-copy-code>
                        <span class="material-symbols-outlined text-sm">content_copy</span>
                    </button>
                    <div class="docs-code-block space-y-1">
                        <code>
                            <div><span class="text-[var(--color-primary)]">curl</span> --request {{ strtoupper($endpointMethod) }} \</div>
                            <div class="pl-4">--url http://localhost:8000/api/v1{{ $endpointPath }} \</div>
                            <div class="pl-4">--header <span class="text-green-400">'Authorization: Bearer &lt;TOKEN&gt;'</span> \</div>
                            <div class="pl-4">--header <span class="text-green-400">'Content-Type: application/json'</span>@if(!empty($parameters)) \@endif</div>
                            @if(!empty($parameters))
                            <div class="pl-4">--data <span class="text-orange-400">'{</span></div>
                            @foreach($parameters as $i => $param)
                            <div class="pl-8 text-orange-400">"{{ $param['name'] }}": "{{ $param['example'] ?? '' }}"{{ !$loop->last ? ',' : '' }}</div>
                            @endforeach
                            <div class="pl-4 text-orange-400">}'</div>
                            @endif
                        </code>
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                @if(!empty($parameters))
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-3 block">Body Parameters</span>
                    <div class="space-y-3">
                        @foreach($parameters as $param)
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">{{ strtoupper($param['name']) }}</label>
                            <input type="text" class="w-full bg-slate-800 border-none rounded text-xs py-2 px-3 text-white focus:ring-1 focus:ring-[var(--color-primary)]" value="{{ $param['example'] ?? '' }}" placeholder="{{ $param['description'] ?? '' }}">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                <button class="w-full py-3 bg-[var(--color-primary)] text-white rounded-lg font-bold text-sm hover:shadow-lg hover:shadow-[var(--color-primary)]/20 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                    Send Request
                </button>
            </div>
            <div class="mt-8 pt-8 border-t border-slate-800">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4 block">Response</span>
                <div class="bg-black/50 border border-slate-800 rounded-lg p-4 font-mono text-xs min-h-32 text-slate-500 flex items-center justify-center">
                    <div class="text-center">
                        <span class="material-symbols-outlined block text-3xl mb-2">terminal</span>
                        Click "Send Request" to see output
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
