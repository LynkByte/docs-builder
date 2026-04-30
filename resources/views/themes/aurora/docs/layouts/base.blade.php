{{-- Aurora Theme — Base Layout --}}
<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="docs-base-url" content="{{ $baseUrl }}">
    <title>{{ $pageTitle ? $pageTitle . ' - ' : '' }}{{ $siteName }}</title>
    <meta name="description" content="{{ !empty($pageDescription) ? $pageDescription : $siteDescription }}">

    {{-- Preconnect for performance --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    {{-- Aurora fonts: Geist Sans, Geist Mono, Instrument Serif --}}
    @if(!empty($fonts) && $fonts !== false)
        @foreach($fonts as $fontUrl)
        <link href="{{ $fontUrl }}" rel="stylesheet">
        @endforeach
    @else
    <link href="https://cdn.jsdelivr.net/npm/geist@1.3.1/dist/fonts/geist-sans/style.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/geist@1.3.1/dist/fonts/geist-mono/style.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    @endif

    {{-- Material Symbols for user-configured sidebar icons --}}
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

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
            var theme = localStorage.getItem('aurora-theme');
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

    {{-- Compiled JS (ES module) --}}
    <script type="module" src="{{ $baseUrl }}/assets/docs.js"></script>

    {{-- Mermaid Diagrams --}}
    <script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
    <script>
        mermaid.initialize({
            startOnLoad: true,
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
        });
    </script>

    @stack('scripts')
</body>
</html>
