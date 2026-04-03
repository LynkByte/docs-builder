<?php

namespace LynkByte\DocsBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use LynkByte\DocsBuilder\DocsBuilder;

class BuildDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:build
                            {--skip-assets : Skip compiling/copying CSS/JS assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the static documentation site from Markdown and OpenAPI sources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        $this->components->info('Building documentation...');

        // Step 1: Handle assets unless skipped
        if (! $this->option('skip-assets')) {
            $this->handleAssets();
        }

        // Step 2: Build documentation pages
        $this->components->task('Building pages', function (): void {
            // Handled below so we can capture the result
        });

        try {
            $builder = new DocsBuilder;
            $result = $builder->build();
        } catch (\Throwable $e) {
            $this->components->error('Build failed: '.$e->getMessage());
            $this->line($e->getTraceAsString());

            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->components->info('Documentation built successfully!');
        $this->components->bulletList([
            "Pages built: {$result['pages']}",
            "Search entries: {$result['searchEntries']}",
            'Output: '.config('docs-builder.output_dir'),
            "Time: {$elapsed}s",
        ]);

        return self::SUCCESS;
    }

    /**
     * Handle asset compilation/copying based on the configured asset mode.
     */
    private function handleAssets(): void
    {
        $assetMode = config('docs-builder.asset_mode', 'precompiled');

        if ($assetMode === 'vite') {
            $this->buildAssetsWithVite();
        } else {
            $this->copyPrecompiledAssets();
        }
    }

    /**
     * Copy pre-compiled assets from the package's dist/ directory.
     */
    private function copyPrecompiledAssets(): void
    {
        $this->components->task('Copying pre-compiled assets', function (): void {
            $outputDir = config('docs-builder.output_dir');
            $assetsDir = $outputDir.'/assets';

            if (! is_dir($assetsDir)) {
                mkdir($assetsDir, 0755, true);
            }

            $distDir = $this->packageDistPath();
            $themeName = config('docs-builder.theme_name', 'default');

            if (! is_dir($distDir)) {
                throw new \RuntimeException(
                    "Pre-compiled assets not found at [{$distDir}]. "
                    .'Run `npm run build` inside the docs-builder package, or switch to asset_mode "vite".'
                );
            }

            // Resolve CSS and JS sources based on the active theme
            if ($themeName !== 'default' && file_exists($distDir.'/themes/'.$themeName.'-css.css')) {
                $cssSource = $distDir.'/themes/'.$themeName.'-css.css';
            } else {
                $cssSource = $distDir.'/docs-css.css';
            }

            if ($themeName !== 'default' && file_exists($distDir.'/themes/'.$themeName.'.js')) {
                $jsSource = $distDir.'/themes/'.$themeName.'.js';
            } else {
                $jsSource = $distDir.'/docs.js';
            }

            if (file_exists($cssSource)) {
                copy($cssSource, $assetsDir.'/docs.css');
            }

            if (file_exists($jsSource)) {
                copy($jsSource, $assetsDir.'/docs.js');
            }

            // Copy shared JS chunks (e.g. fuse.js) that entry points import.
            // Theme JS in dist/themes/ imports these via "../<chunk>.js", so we
            // rewrite any "../" import prefixes to "./" since all assets are
            // flattened into the same output directory.
            foreach (glob($distDir.'/*.js') as $chunkFile) {
                $basename = basename($chunkFile);
                if ($basename === 'docs.js') {
                    continue; // Already handled above
                }
                copy($chunkFile, $assetsDir.'/'.$basename);
            }

            // Fix relative import paths in docs.js when sourced from a theme
            // subdirectory (e.g. "../fuse.js" → "./fuse.js")
            $docsJsPath = $assetsDir.'/docs.js';
            if ($themeName !== 'default' && file_exists($docsJsPath)) {
                $content = file_get_contents($docsJsPath);
                $fixed = preg_replace('/from\s*"\.\.\/([^"]+)"/', 'from"./$1"', $content);
                if ($fixed !== $content) {
                    file_put_contents($docsJsPath, $fixed);
                }
            }
        });
    }

    /**
     * Build CSS and JS assets using the host app's Vite pipeline.
     */
    private function buildAssetsWithVite(): void
    {
        $outputDir = config('docs-builder.output_dir');
        $assetsDir = $outputDir.'/assets';

        $this->components->task('Compiling assets with Vite', function () use ($assetsDir): void {
            $result = Process::timeout(120)->run('npx vite build');

            if (! $result->successful()) {
                throw new \RuntimeException('Vite build failed: '.$result->errorOutput());
            }

            $this->copyViteAssets($assetsDir);
        });
    }

    /**
     * Copy compiled Vite assets (docs.css, docs.js) to the docs output.
     *
     * When a non-default theme is active, looks for theme-specific manifest
     * entries first (e.g. css/themes/modern.css), falling back to the default
     * entries if not found.
     */
    private function copyViteAssets(string $assetsDir): void
    {
        if (! is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }

        $manifestPath = public_path('build/manifest.json');

        if (! file_exists($manifestPath)) {
            throw new \RuntimeException('Vite manifest not found. Run `npm run build` first.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $themeName = config('docs-builder.theme_name', 'default');

        // Resolve theme-specific entries first, falling back to default
        $cssKey = null;
        $jsKey = null;

        if ($themeName !== 'default') {
            $cssKey = $this->resolveViteEntryKey($manifest, 'css/themes/'.$themeName.'.css');
            $jsKey = $this->resolveViteEntryKey($manifest, 'js/themes/'.$themeName.'.js');
        }

        $cssKey = $cssKey ?? $this->resolveViteEntryKey($manifest, 'css/docs.css');
        $jsKey = $jsKey ?? $this->resolveViteEntryKey($manifest, 'js/docs.js');

        // Find and copy docs.css
        $cssEntry = $cssKey ? ($manifest[$cssKey] ?? null) : null;
        if ($cssEntry && isset($cssEntry['file'])) {
            $sourceCss = public_path('build/'.$cssEntry['file']);
            if (file_exists($sourceCss)) {
                copy($sourceCss, $assetsDir.'/docs.css');
            }
        }

        // Find and copy docs.js
        $jsEntry = $jsKey ? ($manifest[$jsKey] ?? null) : null;
        if ($jsEntry && isset($jsEntry['file'])) {
            $sourceJs = public_path('build/'.$jsEntry['file']);
            if (file_exists($sourceJs)) {
                copy($sourceJs, $assetsDir.'/docs.js');
            }
        }

        // Also copy any CSS imported by the JS entry
        if ($jsEntry && isset($jsEntry['css'])) {
            foreach ($jsEntry['css'] as $cssFile) {
                $sourceCss = public_path('build/'.$cssFile);
                if (file_exists($sourceCss)) {
                    file_put_contents(
                        $assetsDir.'/docs.css',
                        file_get_contents($sourceCss),
                        FILE_APPEND
                    );
                }
            }
        }

        // Copy shared JS chunks imported by the entry point (e.g. fuse.js)
        if ($jsEntry && isset($jsEntry['imports'])) {
            foreach ($jsEntry['imports'] as $importKey) {
                $importEntry = $manifest[$importKey] ?? null;
                if ($importEntry && isset($importEntry['file'])) {
                    $chunkSource = public_path('build/'.$importEntry['file']);
                    $chunkBasename = basename($importEntry['file']);
                    if (file_exists($chunkSource)) {
                        copy($chunkSource, $assetsDir.'/'.$chunkBasename);
                    }
                }
            }
        }

        // Fix relative import paths in docs.js when sourced from a theme
        // subdirectory (e.g. "../fuse.js" → "./fuse.js")
        $docsJsPath = $assetsDir.'/docs.js';
        if ($themeName !== 'default' && file_exists($docsJsPath)) {
            $content = file_get_contents($docsJsPath);
            $fixed = preg_replace('/from\s*"\.\.\/([^"]+)"/', 'from"./$1"', $content);
            if ($fixed !== $content) {
                file_put_contents($docsJsPath, $fixed);
            }
        }
    }

    /**
     * Resolve the Vite manifest entry key for a docs asset.
     *
     * Checks for published source files (resources/css/docs.css) first,
     * then falls back to the vendor package path, and finally attempts
     * to match by entry name (e.g. 'docs-css', 'themes/modern') since
     * the host app's Vite config may use custom entry names as keys.
     */
    private function resolveViteEntryKey(array $manifest, string $relativePath): ?string
    {
        // Published source file in the host app (e.g. resources/css/docs.css)
        $publishedKey = 'resources/'.$relativePath;
        if (isset($manifest[$publishedKey])) {
            return $publishedKey;
        }

        // Vendor package path (e.g. vendor/lynkbyte/docs-builder/resources/css/docs.css)
        $vendorKey = 'vendor/lynkbyte/docs-builder/resources/'.$relativePath;
        if (isset($manifest[$vendorKey])) {
            return $vendorKey;
        }

        // Match by entry name — the host app's vite.config may use named entries
        // (e.g. 'docs-css' for 'resources/css/docs.css', 'themes/modern' for
        // 'resources/js/themes/modern.js'). Derive candidate names from the path.
        $candidateNames = $this->deriveEntryNames($relativePath);
        foreach ($candidateNames as $name) {
            if (isset($manifest[$name])) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Derive possible Vite entry names from a relative asset path.
     *
     * For example, 'css/docs.css' yields ['docs-css'], and
     * 'js/themes/modern.js' yields ['themes/modern', 'themes/modern-js'].
     *
     * @return array<int, string>
     */
    private function deriveEntryNames(string $relativePath): array
    {
        $withoutExt = preg_replace('/\.[^.]+$/', '', $relativePath);
        $ext = pathinfo($relativePath, PATHINFO_EXTENSION);

        // Strip leading 'css/' or 'js/' prefix
        $stripped = preg_replace('#^(css|js)/#', '', $withoutExt);

        $names = [$stripped];

        // Also try appending the extension as a suffix (e.g. 'docs-css')
        if ($ext && ! str_ends_with($stripped, '-'.$ext)) {
            $names[] = $stripped.'-'.$ext;
        }

        return $names;
    }

    /**
     * Resolve the path to the package's dist/ directory.
     */
    private function packageDistPath(): string
    {
        return dirname(__DIR__, 2).'/dist';
    }
}
