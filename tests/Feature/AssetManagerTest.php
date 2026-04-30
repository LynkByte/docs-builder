<?php

use LynkByte\DocsBuilder\AssetManager;

beforeEach(function () {
    $this->tempFiles = [];
});

afterEach(function () {
    foreach ($this->tempFiles as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
});

/**
 * Call a private method on an object via Reflection.
 *
 * @param  array<int, mixed>  $args
 */
function callPrivate(object $object, string $method, array $args = []): mixed
{
    $ref = new ReflectionMethod($object, $method);
    $ref->setAccessible(true);

    return $ref->invoke($object, ...$args);
}

// ── Vite entry key resolution ────────────────────────────────────────

it('resolves published source path in Vite manifest', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'vite');

    $manifest = [
        'resources/css/docs.css' => ['file' => 'assets/docs-abc123.css'],
    ];

    $result = callPrivate($manager, 'resolveViteEntryKey', [$manifest, 'css/docs.css']);

    expect($result)->toBe('resources/css/docs.css');
});

it('resolves vendor package path in Vite manifest', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'vite');

    $manifest = [
        'vendor/lynkbyte/docs-builder/resources/css/docs.css' => ['file' => 'assets/docs-abc123.css'],
    ];

    $result = callPrivate($manager, 'resolveViteEntryKey', [$manifest, 'css/docs.css']);

    expect($result)->toBe('vendor/lynkbyte/docs-builder/resources/css/docs.css');
});

it('resolves entry by derived name in Vite manifest', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'vite');

    $manifest = [
        'docs-css' => ['file' => 'assets/docs-abc123.css'],
    ];

    $result = callPrivate($manager, 'resolveViteEntryKey', [$manifest, 'css/docs.css']);

    expect($result)->toBe('docs-css');
});

it('returns null when no Vite manifest entry matches', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'vite');

    $manifest = [
        'resources/js/app.js' => ['file' => 'assets/app-abc123.js'],
    ];

    $result = callPrivate($manager, 'resolveViteEntryKey', [$manifest, 'css/docs.css']);

    expect($result)->toBeNull();
});

// ── Entry name derivation ────────────────────────────────────────────

it('derives entry names from css/docs.css', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'vite');

    $names = callPrivate($manager, 'deriveEntryNames', ['css/docs.css']);

    expect($names)->toBe(['docs', 'docs-css']);
});

it('derives entry names from js/docs.js', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'vite');

    $names = callPrivate($manager, 'deriveEntryNames', ['js/docs.js']);

    expect($names)->toBe(['docs', 'docs-js']);
});

it('derives entry names from theme path', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'vite');

    $names = callPrivate($manager, 'deriveEntryNames', ['css/themes/modern.css']);

    expect($names)->toBe(['themes/modern', 'themes/modern-css']);
});

it('resolves theme-specific Vite entry key before default', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'vite', themeName: 'modern');

    $manifest = [
        'resources/css/themes/modern.css' => ['file' => 'assets/modern-abc123.css'],
        'resources/css/docs.css' => ['file' => 'assets/docs-abc123.css'],
    ];

    // Theme entry should be found for theme path
    $themeResult = callPrivate($manager, 'resolveViteEntryKey', [$manifest, 'css/themes/modern.css']);
    expect($themeResult)->toBe('resources/css/themes/modern.css');
});

// ── Relative import fixing ───────────────────────────────────────────

it('rewrites relative parent imports to same-directory imports', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'precompiled', themeName: 'modern');

    $tmpFile = sys_get_temp_dir().'/docs-builder-test-'.uniqid().'.js';
    $this->tempFiles[] = $tmpFile;
    file_put_contents($tmpFile, 'import{f as e}from"../fuse-DkR3xFBI.js";');

    callPrivate($manager, 'fixRelativeImports', [$tmpFile]);

    expect(file_get_contents($tmpFile))->toBe('import{f as e}from"./fuse-DkR3xFBI.js";');
});

it('leaves file unchanged when no relative parent imports exist', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'precompiled');

    $tmpFile = sys_get_temp_dir().'/docs-builder-test-'.uniqid().'.js';
    $this->tempFiles[] = $tmpFile;
    $original = 'import{f as e}from"./fuse-DkR3xFBI.js";';
    file_put_contents($tmpFile, $original);

    callPrivate($manager, 'fixRelativeImports', [$tmpFile]);

    expect(file_get_contents($tmpFile))->toBe($original);
});

it('does nothing when fixRelativeImports target file does not exist', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'precompiled');

    // Should not throw
    callPrivate($manager, 'fixRelativeImports', ['/nonexistent/path/docs.js']);
})->throwsNoExceptions();

// ── Package dist path ────────────────────────────────────────────────

it('resolves package dist path relative to src directory', function () {
    $manager = new AssetManager(outputDir: sys_get_temp_dir(), assetMode: 'precompiled');

    $distPath = callPrivate($manager, 'packageDistPath');

    expect($distPath)->toEndWith('/dist')
        ->and(str_contains($distPath, 'docs-builder'))->toBeTrue();
});
