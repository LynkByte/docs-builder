---
title: Home
---

# Welcome to Your Documentation

This is the home page of your documentation site, powered by [DocsBuilder](https://github.com/lynkbyte/docs-builder).

## Getting Started

Edit this file at `docs/README.md` to customize your documentation home page.

### Configuration

All settings are in `config/docs-builder.php`. You can configure:

- **Site name and description** — shown in the header and meta tags
- **Navigation** — define sidebar sections and pages
- **Branding** — custom logo, header links, fonts, and theme colors
- **API Reference** — point to your OpenAPI YAML spec

### Adding Pages

Create Markdown files in the `docs/` directory and reference them in the navigation config:

```php
'navigation' => [
    [
        'title' => 'Getting Started',
        'pages' => [
            ['title' => 'Home', 'file' => 'README.md', 'icon' => 'home'],
            ['title' => 'Installation', 'file' => 'installation.md', 'icon' => 'download'],
        ],
    ],
],
```

### Supported Markdown Features

DocsBuilder supports GitHub Flavored Markdown including:

| Feature          | Syntax                    |
|------------------|---------------------------|
| Bold             | `**bold**`                |
| Italic           | `*italic*`                |
| Strikethrough    | `~~deleted~~`             |
| Code blocks      | Triple backticks          |
| Tables           | Pipe-delimited rows       |
| Task lists       | `- [x] Done`             |

### Code Highlighting

Fenced code blocks with language hints get syntax highlighting:

```bash
php artisan docs:build
```

## Building

Run the build command to generate static HTML:

```bash
php artisan docs:build
```

The output will be written to the directory configured in `output_dir` (default: `public/docs`).
