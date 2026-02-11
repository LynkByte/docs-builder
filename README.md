<p align="center">
    <img src="https://img.shields.io/badge/PHP-8.3+-8892BF?style=flat-square&logo=php&logoColor=white" alt="PHP Version">
    <img src="https://img.shields.io/badge/Laravel-11.x%20|%2012.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel Version">
    <img src="https://img.shields.io/badge/Tailwind%20CSS-v4-38BDF8?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
    <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

# Docs Builder

A Laravel package that compiles Markdown files and OpenAPI 3.x YAML specifications into a fully themed, searchable, static documentation site — with zero frontend build step required.

## Features

- **Markdown with GFM** — Tables, task lists, strikethrough, and all GitHub Flavored Markdown extensions
- **Server-side syntax highlighting** — 15+ languages via [Tempest Highlight](https://github.com/tempestphp/highlight), with styled code blocks, language labels, and copy-to-clipboard buttons
- **OpenAPI 3.x API reference** — Auto-generates endpoint pages from your YAML spec with parameters, responses, and an interactive "Try it out" panel
- **Client-side search** — Instant full-text search powered by [Fuse.js](https://www.fusejs.io/) with a keyboard-driven command palette (`Cmd+K` / `Ctrl+K`)
- **Dark / light theme** — Class-based toggle with OS preference detection and `localStorage` persistence
- **Responsive 3-column layout** — Sidebar navigation, content area, and table of contents with mobile slide-in sidebar
- **Pre-compiled assets** — Ships with production-ready CSS and JS in `dist/` — no Node.js or build step needed
- **Optional Vite integration** — Opt into the host app's Vite pipeline for full asset customization
- **Fully configurable** — Logo, header navigation, Google Fonts, CSS theme overrides, Material Symbols icons, footer, and more
- **Publishable views** — Override any Blade template for complete control over the HTML output

## Requirements

- PHP 8.3 or higher
- Laravel 11.x or 12.x

## Installation

```bash
composer require lynkbyte/docs-builder
```

The package auto-discovers its service provider — no manual registration needed.

## Quick Start

**1. Scaffold starter files**

```bash
php artisan docs:init
```

This creates:
- `config/docs-builder.php` — the configuration file
- `docs/README.md` — a starter documentation page
- `docs/openapi.yaml` — a minimal OpenAPI spec

**2. Build the documentation**

```bash
php artisan docs:build
```

**3. View the output**

The static site is written to `public/docs/` by default. Open `public/docs/index.html` in your browser, or visit `/docs` if your app is running.

## Configuration Reference

Publish the config file (or use the one created by `docs:init`):

```bash
php artisan vendor:publish --tag=docs-builder-config
```

### Site Settings

```php
// config/docs-builder.php

'site_name' => 'My Documentation',

'site_description' => 'Documentation for my project',

// Directory containing your Markdown source files
'source_dir' => base_path('docs'),

// Where the built HTML is written
'output_dir' => public_path('docs'),

// OpenAPI YAML spec for API reference generation
'openapi_file' => base_path('docs/openapi.yaml'),

// URL path prefix for all generated links
'base_url' => '/docs',
```

### Appearance

#### Logo

```php
// null = default diamond SVG
'logo' => null,

// Inline SVG
'logo' => '<svg viewBox="0 0 24 24" ...>...</svg>',

// HTML with an image
'logo' => '<img src="/images/logo.svg" alt="Logo" class="h-8">',
```

#### Header Navigation

```php
// null = defaults (Guides, API Reference, Examples)
'header_nav' => null,

// Custom links
'header_nav' => [
    ['title' => 'Guides', 'url' => '/docs'],
    ['title' => 'API', 'url' => '/docs/api-reference'],
    ['title' => 'Blog', 'url' => '/blog'],
],
```

#### Fonts

```php
// Default: Inter + JetBrains Mono + Material Symbols
'fonts' => [
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=JetBrains+Mono:wght@400;500;600&display=swap',
    'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
],

// Disable external fonts entirely
'fonts' => false,
```

> **Note:** Material Symbols Outlined is used for sidebar page icons and UI elements. If you disable fonts, icons will not render unless you provide them through another mechanism.

#### Theme Overrides

Override the default CSS custom properties by setting key-value pairs. These are injected as an inline `<style>` block on `:root`.

```php
'theme' => [
    'color-primary'       => '#8b5cf6',
    'color-primary-light' => '#a78bfa',
    'color-primary-dark'  => '#7c3aed',
    'color-dark-bg'       => '#0a0a0a',
    'color-light-bg'      => '#fafafa',
],
```

<details>
<summary><strong>All available theme variables</strong></summary>

| Variable | Default | Description |
|----------|---------|-------------|
| `color-primary` | `#137fec` | Primary brand color (links, active states) |
| `color-primary-light` | `#3b9af5` | Lighter primary variant (hover states) |
| `color-primary-dark` | `#0d6ad4` | Darker primary variant |
| `color-dark-bg` | `#101922` | Dark mode background |
| `color-dark-surface` | `#1a2632` | Dark mode surface (cards, sidebar) |
| `color-dark-border` | `#233648` | Dark mode borders |
| `color-dark-muted` | `#92adc9` | Dark mode muted text |
| `color-dark-text` | `#e2e8f0` | Dark mode primary text |
| `color-dark-text-secondary` | `#cbd5e1` | Dark mode secondary text |
| `color-light-bg` | `#f6f7f8` | Light mode background |
| `color-light-surface` | `#ffffff` | Light mode surface |
| `color-light-border` | `#e2e8f0` | Light mode borders |
| `color-light-muted` | `#94a3b8` | Light mode muted text |
| `color-light-text` | `#0f172a` | Light mode primary text |
| `color-light-text-secondary` | `#475569` | Light mode secondary text |
| `color-code-bg` | `#0d1117` | Code block background |
| `color-code-header` | `#1a2632` | Code block header background |
| `color-code-border` | `#233648` | Code block border |
| `color-code-text` | `#f1f5f9` | Code block text |

</details>

### Navigation

The sidebar navigation is defined as an array of sections, each containing an array of pages:

```php
'navigation' => [
    [
        'title' => 'Getting Started',
        'pages' => [
            ['title' => 'Home', 'file' => 'README.md', 'icon' => 'home'],
            ['title' => 'Installation', 'file' => 'installation.md', 'icon' => 'download'],
        ],
    ],
    [
        'title' => 'API',
        'pages' => [
            ['title' => 'API Reference', 'file' => 'api-reference.md', 'icon' => 'api', 'layout' => 'api-reference'],
        ],
    ],
],
```

Each page entry supports:

| Key | Required | Description |
|-----|----------|-------------|
| `title` | Yes | Display title in the sidebar and page heading |
| `file` | Yes | Markdown filename (relative to `source_dir`) |
| `icon` | No | [Material Symbols](https://fonts.google.com/icons) icon name |
| `layout` | No | `'documentation'` (default) or `'api-reference'` |

Pages using the `api-reference` layout render with the API sidebar (tags, endpoints) instead of the standard documentation sidebar.

### API Settings

```php
// Auto-generated from openapi.yaml tags if left empty
'api_navigation' => [],

// Map OpenAPI tags to Material Symbols icon names
'api_tag_icons' => [
    'Authentication' => 'lock',
    'Users'          => 'group',
    'Bookings'       => 'calendar_month',
    // Unlisted tags fall back to 'api'
],
```

### Footer

```php
'footer' => [
    'copyright' => '© ' . date('Y') . ' My Company. All rights reserved.',
    'links' => [
        ['title' => 'Privacy Policy', 'url' => '/privacy'],
        ['title' => 'GitHub', 'url' => 'https://github.com/my-org'],
    ],
],
```

## Writing Documentation

### Markdown Files

Place your Markdown files in the `source_dir` directory (`docs/` by default). The builder supports all standard Markdown plus GitHub Flavored Markdown extensions:

| Feature | Syntax |
|---------|--------|
| Tables | `\| Header \| Header \|` |
| Task lists | `- [x] Done` |
| Strikethrough | `~~text~~` |
| Fenced code blocks | `` ```php `` |
| Heading anchors | Auto-generated from heading text |

### YAML Front Matter

Optionally add front matter to your Markdown files:

```markdown
---
title: Installation Guide
---

# Installation

Your content here...
```

### Syntax Highlighting

Fenced code blocks are syntax-highlighted server-side. Supported languages:

`php`, `blade`, `html`, `css`, `javascript`/`js`, `typescript`/`ts`, `json`, `bash`/`shell`/`sh`, `sql`, `yaml`/`yml`, `xml`

```markdown
    ```php
    $user = User::find(1);
    ```
```

Code blocks render with a language label in the header and a copy-to-clipboard button.

### Site Name Placeholder

Use `{SiteName}` anywhere in your Markdown content and it will be replaced with the configured `site_name` value.

## API Reference Generation

If your project has an OpenAPI 3.x YAML spec, the builder automatically generates individual pages for each endpoint.

### How It Works

1. Set `openapi_file` in your config to point at your YAML spec
2. Add a page with `'layout' => 'api-reference'` to your navigation — this becomes the API overview page
3. Run `php artisan docs:build`

The builder parses the spec and generates one HTML page per `operationId`, grouped by tag. Each endpoint page includes:

- **Method badge** — Color-coded (`GET` blue, `POST` green, `PUT`/`PATCH` amber, `DELETE` red)
- **Full endpoint path** with the server base URL
- **Parameters table** — Path, query, and request body parameters with types, required flags, and descriptions
- **Responses section** — Status codes with descriptions and JSON examples
- **"Try it out" panel** — Interactive form to send requests with a generated cURL command and live response viewer

### Tag Icons

Map your OpenAPI tags to [Material Symbols](https://fonts.google.com/icons) icons for the API sidebar:

```php
'api_tag_icons' => [
    'Authentication' => 'lock',
    'Users'          => 'group',
    'Payments'       => 'payments',
],
```

Any tag not in this map falls back to the `api` icon.

## Asset Modes

The package supports two modes for handling CSS and JavaScript assets.

### Precompiled (Default)

```php
'asset_mode' => 'precompiled',
```

Uses the pre-built `docs.css` and `docs.js` from the package's `dist/` directory. The build command copies them directly to your output's `assets/` folder.

**Pros:** No Node.js required, instant builds, zero configuration.

**When to use:** Most projects. You just want working documentation with the default theme.

### Vite

```php
'asset_mode' => 'vite',
```

Integrates with your host application's Vite build pipeline. The build command runs `npx vite build` and reads the generated manifest to locate and copy the compiled assets.

To use Vite mode, add the package's CSS and JS as entry points in your app's `vite.config.js`:

```js
// vite.config.js
laravel({
    input: [
        'resources/css/app.css',
        'resources/js/app.js',
        // Add these for docs asset customization:
        'vendor/lynkbyte/docs-builder/resources/css/docs.css',
        'vendor/lynkbyte/docs-builder/resources/js/docs.js',
    ],
}),
```

**Pros:** Full control over CSS and JS, can extend Tailwind theme, can add custom JavaScript.

**When to use:** You need to customize the docs styles beyond what theme overrides provide, or want to bundle additional frontend code.

## Commands

### `docs:build`

Build the static documentation site.

```bash
php artisan docs:build
```

| Option | Description |
|--------|-------------|
| `--skip-assets` | Skip copying/compiling CSS and JS assets. Useful when assets haven't changed and you only need to rebuild HTML. |

```bash
# Rebuild only HTML pages, skip asset handling
php artisan docs:build --skip-assets
```

### `docs:init`

Scaffold a starter documentation directory with example files.

```bash
php artisan docs:init
```

| Option | Description |
|--------|-------------|
| `--force` | Overwrite existing config and stub files if they already exist. |

```bash
# Overwrite existing files
php artisan docs:init --force
```

## Publishing

The package offers six publishable groups so you can customize any part of the documentation:

| Tag | What's Published | Destination |
|-----|-----------------|-------------|
| `docs-builder-config` | Configuration file | `config/docs-builder.php` |
| `docs-builder-views` | All Blade templates | `resources/views/vendor/docs-builder/` |
| `docs-builder-assets` | Pre-compiled CSS/JS | `public/docs/assets/` |
| `docs-builder-css` | CSS source file | `resources/css/docs.css` |
| `docs-builder-js` | JavaScript source file | `resources/js/docs.js` |
| `docs-builder-stubs` | Init scaffold templates | `stubs/docs-builder/` |

```bash
# Publish individual groups
php artisan vendor:publish --tag=docs-builder-config
php artisan vendor:publish --tag=docs-builder-views
php artisan vendor:publish --tag=docs-builder-assets
php artisan vendor:publish --tag=docs-builder-css
php artisan vendor:publish --tag=docs-builder-js
php artisan vendor:publish --tag=docs-builder-stubs
```

### Customizing Templates

Published views are placed in `resources/views/vendor/docs-builder/` and take priority over the package views, allowing you to modify any template:

```
views/vendor/docs-builder/docs/
├── layouts/
│   ├── base.blade.php            # Root HTML document (head, scripts, styles)
│   ├── documentation.blade.php   # Standard doc page (sidebar + content + TOC)
│   └── api-reference.blade.php   # API reference page (API sidebar + content)
└── partials/
    ├── header.blade.php          # Top navigation bar (logo, nav links, search, theme toggle)
    ├── sidebar.blade.php         # Left sidebar navigation
    ├── toc.blade.php             # Right-side table of contents
    ├── search-modal.blade.php    # Search command palette overlay
    └── footer.blade.php          # Site footer
```

### Customizing Styles

For changes beyond what [theme overrides](#theme-overrides) offer (e.g. modifying animations, sidebar width, typography, or syntax highlighting colors), publish the CSS source and switch to Vite mode:

```bash
# 1. Publish the CSS source file
php artisan vendor:publish --tag=docs-builder-css

# 2. Add it as a Vite entry point in your vite.config.js
#    (see Asset Modes > Vite section above)

# 3. Set asset_mode to 'vite' in config/docs-builder.php
```

The published `resources/css/docs.css` is a full Tailwind CSS v4 file with the `@theme` block, all custom styles, and syntax highlighting classes. Edit it freely — when in Vite mode, the build command automatically detects published source files in your app's `resources/` directory.

### Customizing JavaScript

Publish the JS source to modify search behavior, theme toggling, keyboard shortcuts, or add your own functionality:

```bash
php artisan vendor:publish --tag=docs-builder-js
```

This copies `resources/js/docs.js` to your app. Like the CSS, it's auto-detected when using Vite mode.

### Customizing Init Stubs

Publish the stubs to customize what `docs:init` scaffolds for new documentation:

```bash
php artisan vendor:publish --tag=docs-builder-stubs
```

This copies `README.md` and `openapi.yaml` templates to `stubs/docs-builder/`. When published stubs exist, `docs:init` uses them instead of the package defaults.

## Search

The builder generates a `search-index.json` file in the output directory containing all documentation pages and API endpoints. Search is powered by [Fuse.js](https://www.fusejs.io/) on the client side.

**Keyboard shortcut:** `Cmd+K` (macOS) / `Ctrl+K` (Windows/Linux) opens the search command palette.

The search index is lazy-loaded on first use and includes:
- Page titles (highest weight)
- Section headings (high weight)
- Page content (medium weight)
- Section names (lower weight)

Results are grouped by section with icons and, for API endpoints, method badges.

## Dependencies

| Package | Purpose |
|---------|---------|
| [league/commonmark](https://commonmark.thephpleague.com/) | Markdown to HTML conversion with GFM extensions |
| [symfony/yaml](https://symfony.com/doc/current/components/yaml.html) | OpenAPI YAML spec parsing |
| [tempest/highlight](https://github.com/tempestphp/highlight) | Server-side syntax highlighting for code blocks |
| [fuse.js](https://www.fusejs.io/) | Client-side fuzzy search (bundled in pre-compiled JS) |
| [Tailwind CSS v4](https://tailwindcss.com/) | Utility-first styling (bundled in pre-compiled CSS) |

## Contributing

Contributions are welcome. Here's how to get started:

```bash
# Clone the repository
git clone https://github.com/lynkbyte/docs-builder.git
cd docs-builder

# Install dependencies
composer install
npm install

# Run the test suite
vendor/bin/pest

# Build pre-compiled assets
npm run build
```

Before submitting a pull request:

1. **Code style** — Run `vendor/bin/pint` to auto-fix formatting (PSR-12)
2. **Tests** — Add tests for any new functionality and ensure all existing tests pass
3. **One concern per PR** — Keep pull requests focused on a single change
4. **Descriptive title** — Use a clear, concise title that summarizes the change

## Security Vulnerabilities

If you discover a security vulnerability within Docs Builder, please report it responsibly. **Do not open a public issue.** Instead, email [security@lynkbyte.com](mailto:security@lynkbyte.com) directly. All reports will be reviewed promptly and handled with care.

## License

Docs Builder is open-sourced software licensed under the [MIT license](LICENSE).
