<?php

namespace LynkByte\DocsBuilder;

use Illuminate\Support\Facades\Process;

class AssetManager
{
    /**
     * @param  string  $outputDir  The documentation output directory.
     * @param  string  $assetMode  Either 'precompiled' or 'vite'.
     * @param  string  $themeName  The active theme name (e.g. 'default', 'modern').
     */
    public function __construct(
        private string $outputDir,
        private string $assetMode = 'precompiled',
        private string $themeName = 'default',
    ) {}

    /**
     * Handle asset compilation/copying based on the configured asset mode.
     *
     * @param  callable(string, callable): void  $taskReporter  Reports progress; receives a description and a task callable.
     */
    public function handle(callable $taskReporter): void
    {
        if ($this->assetMode === 'vite') {
            $this->buildAssetsWithVite($taskReporter);
        } else {
            $this->copyPrecompiledAssets($taskReporter);
        }
    }

    /**
     * Copy pre-compiled assets from the package's dist/ directory.
     *
     * @param  callable(string, callable): void  $taskReporter
     */
    private function copyPrecompiledAssets(callable $taskReporter): void
    {
        $taskReporter('Copying pre-compiled assets', function (): void {
            $assetsDir = $this->outputDir.'/assets';

            if (! is_dir($assetsDir)) {
                mkdir($assetsDir, 0755, true);
            }

            $distDir = $this->packageDistPath();

            if (! is_dir($distDir)) {
                throw new \RuntimeException(
                    "Pre-compiled assets not found at [{$distDir}]. "
                    .'Run `npm run build` inside the docs-builder package, or switch to asset_mode "vite".'
                );
            }

            // Resolve CSS and JS sources based on the active theme
            if ($this->themeName !== 'default' && file_exists($distDir.'/themes/'.$this->themeName.'-css.css')) {
                $cssSource = $distDir.'/themes/'.$this->themeName.'-css.css';
            } else {
                $cssSource = $distDir.'/docs-css.css';
            }

            if ($this->themeName !== 'default' && file_exists($distDir.'/themes/'.$this->themeName.'.js')) {
                $jsSource = $distDir.'/themes/'.$this->themeName.'.js';
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
            foreach (glob($distDir.'/*.js') ?: [] as $chunkFile) {
                $basename = basename($chunkFile);
                if ($basename === 'docs.js') {
                    continue; // Already handled above
                }
                copy($chunkFile, $assetsDir.'/'.$basename);
            }

            // Fix relative import paths when sourced from a theme subdirectory
            if ($this->themeName !== 'default') {
                $this->fixRelativeImports($assetsDir.'/docs.js');
            }
        });
    }

    /**
     * Build CSS and JS assets using the host app's Vite pipeline.
     *
     * @param  callable(string, callable): void  $taskReporter
     */
    private function buildAssetsWithVite(callable $taskReporter): void
    {
        $assetsDir = $this->outputDir.'/assets';

        $taskReporter('Compiling assets with Vite', function () use ($assetsDir): void {
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

        $manifestContent = file_get_contents($manifestPath);

        if ($manifestContent === false) {
            throw new \RuntimeException("Unable to read Vite manifest at [{$manifestPath}].");
        }

        $manifest = json_decode($manifestContent, true);

        if ($manifest === null) {
            throw new \RuntimeException('Failed to decode Vite manifest: '.json_last_error_msg());
        }

        // Resolve theme-specific entries first, falling back to default
        $cssKey = null;
        $jsKey = null;

        if ($this->themeName !== 'default') {
            $cssKey = $this->resolveViteEntryKey($manifest, 'css/themes/'.$this->themeName.'.css');
            $jsKey = $this->resolveViteEntryKey($manifest, 'js/themes/'.$this->themeName.'.js');
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

        // Fix relative import paths when sourced from a theme subdirectory
        if ($this->themeName !== 'default') {
            $this->fixRelativeImports($assetsDir.'/docs.js');
        }
    }

    /**
     * Resolve the Vite manifest entry key for a docs asset.
     *
     * Checks for published source files (resources/css/docs.css) first,
     * then falls back to the vendor package path, and finally attempts
     * to match by entry name (e.g. 'docs-css', 'themes/modern') since
     * the host app's Vite config may use custom entry names as keys.
     *
     * @param  array<string, mixed>  $manifest
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
     * Rewrite relative "../" import paths to "./" in a JS file.
     *
     * Theme JS files reference shared chunks via "../chunk.js" but all assets
     * are flattened into the same output directory, so the prefix must be "./".
     */
    private function fixRelativeImports(string $filePath): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $fixed = preg_replace('/from\s*"\.\.\/([^"]+)"/', 'from"./$1"', $content);

        if ($fixed !== null && $fixed !== $content) {
            file_put_contents($filePath, $fixed);
        }
    }

    /**
     * Resolve the path to the package's dist/ directory.
     */
    private function packageDistPath(): string
    {
        return dirname(__DIR__).'/dist';
    }
}
