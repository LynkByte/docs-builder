<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Name
    |--------------------------------------------------------------------------
    */
    'site_name' => 'Documentation',

    /*
    |--------------------------------------------------------------------------
    | Site Description
    |--------------------------------------------------------------------------
    */
    'site_description' => 'Project documentation',

    /*
    |--------------------------------------------------------------------------
    | Source Directory
    |--------------------------------------------------------------------------
    | The directory where the Markdown documentation files are stored.
    */
    'source_dir' => base_path('docs'),

    /*
    |--------------------------------------------------------------------------
    | Output Directory
    |--------------------------------------------------------------------------
    | The directory where the built HTML documentation will be written.
    */
    'output_dir' => public_path('docs'),

    /*
    |--------------------------------------------------------------------------
    | OpenAPI Spec File
    |--------------------------------------------------------------------------
    | Path to the OpenAPI YAML specification file used for generating
    | API reference pages.
    */
    'openapi_file' => base_path('docs/openapi.yaml'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    | The base URL path where the documentation will be served from.
    */
    'base_url' => '/docs',

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    | Configure the logo displayed in the header. Set to null to use the
    | default diamond SVG, a string for inline SVG/HTML, or a URL path
    | to an image file.
    */
    'logo' => null,

    /*
    |--------------------------------------------------------------------------
    | Header Navigation
    |--------------------------------------------------------------------------
    | Links displayed in the top navigation bar. Each entry should have
    | a 'title' and 'url'. Set to null to use defaults (Guides, API
    | Reference, Examples).
    */
    'header_nav' => null,

    /*
    |--------------------------------------------------------------------------
    | Fonts
    |--------------------------------------------------------------------------
    | Google Font URLs to load. Set to false to disable external fonts.
    | Defaults to Inter + JetBrains Mono + Material Symbols.
    */
    'fonts' => [
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=JetBrains+Mono:wght@400;500;600&display=swap',
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Mode
    |--------------------------------------------------------------------------
    | How CSS/JS assets are handled during docs:build.
    |
    | 'precompiled' (default) - Uses pre-built assets from the package's
    |                           dist/ directory. Zero build step required.
    | 'vite'                  - Uses the host app's Vite build pipeline.
    |                           Requires docs CSS/JS as Vite entry points.
    */
    'asset_mode' => 'precompiled',

    /*
    |--------------------------------------------------------------------------
    | Theme Overrides
    |--------------------------------------------------------------------------
    | Override default CSS custom properties. These are injected as inline
    | <style> in the base layout. Set individual keys to override colors.
    |
    | Available keys: color-primary, color-primary-light, color-primary-dark,
    | and all --color-dark-* / --color-light-* / --color-code-* variables.
    */
    'theme' => [],

    /*
    |--------------------------------------------------------------------------
    | API Tag Icons
    |--------------------------------------------------------------------------
    | Map OpenAPI tags to Material Symbols icon names for sidebar display.
    | Extend or override the defaults. Any tag not listed here falls back
    | to 'api'.
    */
    'api_tag_icons' => [
        'Authentication' => 'lock',
        'User' => 'person',
        'Users' => 'group',
        'Booking' => 'calendar_month',
        'Bookings' => 'calendar_month',
        'Portfolio' => 'photo_library',
        'Admin' => 'admin_panel_settings',
        'General' => 'api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    | Defines the sidebar navigation structure. Each section has a title
    | and an array of pages. Each page has a title, file (markdown source),
    | and an optional icon (Material Symbols name).
    |
    | Supported layouts: 'documentation' (default), 'api-reference'
    */
    'navigation' => [
        [
            'title' => 'Getting Started',
            'pages' => [
                ['title' => 'Home', 'file' => 'README.md', 'icon' => 'home'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoint Navigation
    |--------------------------------------------------------------------------
    | Defines which OpenAPI tags/groups should appear in the API reference
    | sidebar and their display settings. Auto-generated from openapi.yaml
    | if left empty.
    */
    'api_navigation' => [],

    /*
    |--------------------------------------------------------------------------
    | Footer
    |--------------------------------------------------------------------------
    */
    'footer' => [
        'copyright' => '© '.date('Y').' All rights reserved.',
        'links' => [],
    ],

];
