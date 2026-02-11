<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="docs-base-url" content="{{ $baseUrl }}">
    <title>{{ $pageTitle ? $pageTitle . ' - ' : '' }}{{ $siteName }}</title>
    <meta name="description" content="{{ $siteDescription }}">

    {{-- Fonts --}}
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

    {{-- Prevent FOUC --}}
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
<body class="bg-[var(--docs-bg)] text-[var(--docs-text)] min-h-screen font-sans">

    @include('docs-builder::docs.partials.header')

    <div class="max-w-[1440px] mx-auto flex">
        @yield('body')
    </div>

    @yield('footer')

    @include('docs-builder::docs.partials.search-modal')

    {{-- Compiled JS --}}
    <script src="{{ $baseUrl }}/assets/docs.js"></script>
</body>
</html>
