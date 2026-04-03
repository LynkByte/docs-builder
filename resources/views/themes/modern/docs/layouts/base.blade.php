<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="docs-base-url" content="{{ $baseUrl }}">
    <title>{{ $pageTitle ? $pageTitle . ' - ' : '' }}{{ $siteName }}</title>
    <meta name="description" content="{{ !empty($pageDescription) ? $pageDescription : $siteDescription }}">

    {{-- Preconnect for performance --}}
    @if(!empty($fonts) && $fonts !== false)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @foreach($fonts as $fontUrl)
    <link href="{{ $fontUrl }}" rel="stylesheet">
    @endforeach
    @endif

    {{-- Theme Overrides --}}
    @if(!empty($themeOverrides))
    <style>
        :root {
            @foreach($themeOverrides as $property => $value)
            --{{ $property }}: {{ $value }};
            @endforeach
        }
    </style>
    @endif

    {{-- Compiled CSS --}}
    <link rel="stylesheet" href="{{ $baseUrl }}/assets/docs.css">

    {{-- Prevent FOUC — apply dark class before render --}}
    <script>
        (function() {
            var theme = localStorage.getItem('devdocs-theme');
            if (!theme) {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="bg-[var(--docs-bg)] text-[var(--docs-text)] min-h-screen font-sans antialiased">

    {{-- Skip to main content (accessibility) --}}
    <a href="#main-content" class="docs-skip-link">Skip to content</a>

    @include('docs-builder::docs.partials.header')

    <div class="max-w-[1400px] mx-auto flex min-h-[calc(100vh-64px)]">
        @yield('body')
    </div>

    @yield('footer')

    @include('docs-builder::docs.partials.search-modal')

    {{-- Compiled JS (ES module — allows code-split shared chunks like fuse.js) --}}
    <script type="module" src="{{ $baseUrl }}/assets/docs.js"></script>

    {{-- Mermaid Diagrams --}}
    <script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
    <script>
        mermaid.initialize({
            startOnLoad: true,
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
        });
    </script>
</body>
</html>
