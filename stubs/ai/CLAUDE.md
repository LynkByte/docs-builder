<!-- docs-builder:start -->
# lynkbyte/docs-builder — Claude Code Instructions

## Project Overview

This is **lynkbyte/docs-builder**, a Laravel package that compiles Markdown files and OpenAPI 3.x YAML specs into a static documentation site. It targets PHP 8.3+ and Laravel 11.x/12.x/13.x.

## Architecture Map

```
src/
├── Commands/
│   ├── AiDocsCommand.php         # `docs:ai` — publishes AI tool configs
│   ├── BuildDocsCommand.php      # `docs:build` — builds the static site
│   └── InitDocsCommand.php       # `docs:init` — scaffolds starter files
├── DocsBuilder.php               # Core orchestrator — coordinates the entire build pipeline
├── DocsBuilderServiceProvider.php # Auto-discovered service provider
├── MarkdownParser.php            # Markdown → HTML (CommonMark + GFM + syntax highlighting + Mermaid + images + videos)
├── OpenApiParser.php             # OpenAPI 3.x YAML → structured endpoint data
└── SearchIndexBuilder.php        # Builds search-index.json for Fuse.js
```

### Class Relationships

- `BuildDocsCommand` instantiates `DocsBuilder` and calls `build()`.
- `DocsBuilder` owns instances of `MarkdownParser`, `OpenApiParser`, and `SearchIndexBuilder`.
- `DocsBuilderServiceProvider` registers config, views, commands, and publishable assets.
- All classes are in the `LynkByte\DocsBuilder` namespace.

### Key Directories

- `config/` — Default `docs-builder.php` config file
- `dist/` — Pre-compiled CSS/JS (built via `npm run build`, NOT edited manually)
- `resources/views/docs/` — Blade templates (layouts + partials)
- `resources/css/docs.css` — Tailwind CSS v4 source (710 lines)
- `resources/js/docs.js` — Client-side JS source (search, theme toggle, code copy, Mermaid)
- `stubs/` — Scaffold templates for `docs:init`
- `tests/Feature/` — Pest test suite

## Commands

- `vendor/bin/pint` — Format code (run before committing)
- `vendor/bin/pest` — Run test suite
- `npm run build` — Rebuild pre-compiled CSS/JS assets

## Code Style

- **Formatter:** Laravel Pint (PSR-12 based). Run `vendor/bin/pint` before committing.
- **PHP version:** 8.3+ — use modern syntax: named arguments, match expressions, readonly properties, enums, first-class callables, fibers where appropriate.
- **Type hints:** Always use parameter and return type declarations. Use PHPDoc `@param` and `@return` only when types need additional detail (generics, array shapes).
- **Array shapes:** Document complex array structures with `@param array{key: type}` or `@return array{key: type}` PHPDoc annotations.
- **Strict types:** Do NOT add `declare(strict_types=1)` — the codebase does not use it.
- **Imports:** Use fully qualified imports, no aliases unless necessary.
- **String style:** Single quotes for simple strings, double quotes only when interpolation is needed.

## Testing

- **Framework:** Pest (v3/v4) with Orchestra Testbench (v9/v10).
- **Run tests:** `vendor/bin/pest`
- **Test location:** `tests/Feature/` — all tests are feature-level.
- **Test base class:** `tests/TestCase.php` extends `Orchestra\Testbench\TestCase`.
- **Conventions:**
  - Use Pest's functional syntax (`test()`, `it()`, `beforeEach()`, `afterEach()`).
  - Use `$this->parser`, `$this->builder`, etc. set in `beforeEach()`.
  - Clean up temp files in `afterEach()`.
  - Test file names match the class: `MarkdownParserTest.php` tests `MarkdownParser`.

## Common Development Tasks

### Adding a new config option

1. Add the key with a sensible default to `config/docs-builder.php`.
2. Read it via `config('docs-builder.your_key')` or `$this->config['your_key']` in `DocsBuilder`.
3. If it affects view rendering, pass it through `resolveSharedViewData()` or `buildPage()` view data.
4. Document the option in the README under the relevant config section.

### Adding a new supported syntax highlighting language

1. Add the alias mapping to `$languageExtensions` in `MarkdownParser.php`.
2. Add the display label to the `$labels` array in `getCodeBlockLabel()`.
3. Ensure Tempest Highlight supports the language (check their docs).

### Adding a new Blade partial

1. Create the file in `resources/views/docs/partials/`.
2. Include it from the appropriate layout in `resources/views/docs/layouts/`.
3. It will be published with the `docs-builder-views` tag automatically.

### Modifying the build pipeline

1. The pipeline is orchestrated in `DocsBuilder::build()`.
2. Each step is a private method. Add new steps as private methods and call them from `build()`.
3. Update the `BuildDocsCommand` output messaging if the new step produces user-visible results.

### Adding a new publishable asset group

1. Add a new `$this->publishes([...], 'tag-name')` call in `DocsBuilderServiceProvider::boot()`.
2. Document the tag in the README Publishing section.

## Important Conventions

- **Asset files in `dist/` are generated** — never edit them directly. Edit `resources/css/docs.css` or `resources/js/docs.js` and run `npm run build`.
- **`README.md` is treated specially** — it slugifies to `index`, producing `index.html` at the output root.
- **`{SiteName}` placeholder** — replaced in `DocsBuilder::buildPage()`, not in `MarkdownParser`.
- **Front matter** — YAML front matter (`---` delimited) is stripped by the parser but not used for metadata. Page titles come from the navigation config.
- **No routes** — this package produces static HTML files. There are no Laravel routes.
- **No models/migrations** — this package has no database interaction.
- **The `package.json` is private** — it's for internal asset compilation only. End users never run `npm install`.

## Dependencies to Be Aware Of

- `league/commonmark` ^2.7 — Markdown parsing. Extensions: CommonMarkCore, GFM, HeadingPermalink.
- `symfony/yaml` ^6.4|^7.0|^8.0 — YAML parsing for OpenAPI specs.
- `tempest/highlight` ^2.0 — Server-side syntax highlighting. Languages must be supported by this library.
- Fuse.js ^7.1 — Bundled in `dist/docs.js`. Client-side fuzzy search.
- Tailwind CSS v4 — Used in `resources/css/docs.css`. Bundled in `dist/docs-css.css`.

## Do's

- DO add PHPDoc annotations for complex array shapes.
- DO write Pest tests for any new functionality.
- DO run `vendor/bin/pint` before committing.
- DO follow the existing code patterns (e.g., how `buildPage()` works for new page types).
- DO keep the pre-compiled assets in sync with source — run `npm run build` after changing CSS/JS.
- DO preserve backward compatibility in config — add new keys with sensible defaults.

## Don'ts

- DON'T add `declare(strict_types=1)` — the codebase doesn't use it.
- DON'T edit files in `dist/` directly — they are generated.
- DON'T add Laravel routes — this package produces static HTML.
- DON'T add database models or migrations — this is a static site generator.
- DON'T break the zero-build-step promise — precompiled mode must work without Node.js.
- DON'T add heavy runtime dependencies — this runs during build time only.
<!-- docs-builder:end -->
