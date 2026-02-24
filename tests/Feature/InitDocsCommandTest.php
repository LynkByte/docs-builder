<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Ensure clean state - no docs directory or config file
    $this->docsDir = base_path('docs');
    $this->configPath = config_path('docs-builder.php');

    if (is_dir($this->docsDir)) {
        File::deleteDirectory($this->docsDir);
    }

    if (file_exists($this->configPath)) {
        File::delete($this->configPath);
    }
});

afterEach(function () {
    if (is_dir($this->docsDir)) {
        File::deleteDirectory($this->docsDir);
    }

    if (file_exists($this->configPath)) {
        File::delete($this->configPath);
    }
});

it('runs the docs:init command successfully', function () {
    $this->artisan('docs:init')
        ->assertSuccessful();
});

it('creates the docs directory with stub files', function () {
    $this->artisan('docs:init');

    expect(is_dir($this->docsDir))->toBeTrue()
        ->and(file_exists($this->docsDir.'/README.md'))->toBeTrue()
        ->and(file_exists($this->docsDir.'/openapi.yaml'))->toBeTrue();
});

it('publishes the config file', function () {
    $this->artisan('docs:init');

    expect(file_exists($this->configPath))->toBeTrue();

    $config = include $this->configPath;
    expect($config)->toBeArray()
        ->and($config)->toHaveKey('site_name')
        ->and($config)->toHaveKey('navigation');
});

it('skips existing files without --force', function () {
    // Create the docs dir and a file
    mkdir($this->docsDir, 0755, true);
    file_put_contents($this->docsDir.'/README.md', 'Custom content');

    $this->artisan('docs:init')
        ->assertSuccessful();

    // Original content should be preserved
    expect(file_get_contents($this->docsDir.'/README.md'))->toBe('Custom content');
});

it('overwrites existing files with --force', function () {
    // Create the docs dir and a file
    mkdir($this->docsDir, 0755, true);
    file_put_contents($this->docsDir.'/README.md', 'Custom content');

    $this->artisan('docs:init', ['--force' => true])
        ->assertSuccessful();

    // Content should be overwritten with stub content
    expect(file_get_contents($this->docsDir.'/README.md'))->not->toBe('Custom content')
        ->and(file_get_contents($this->docsDir.'/README.md'))->toContain('Welcome');
});

// --- Feature selection tests ---

it('excludes openapi.yaml when api_reference feature is not selected', function () {
    $this->artisan('docs:init', ['--features' => 'examples'])
        ->assertSuccessful();

    expect(file_exists($this->docsDir.'/README.md'))->toBeTrue()
        ->and(file_exists($this->docsDir.'/openapi.yaml'))->toBeFalse();
});

it('sets openapi_file to null in config when api_reference is excluded', function () {
    $this->artisan('docs:init', ['--features' => 'examples']);

    $config = include $this->configPath;

    expect($config['openapi_file'])->toBeNull()
        ->and($config['api_tag_icons'])->toBe([]);
});

it('sets explicit header_nav when examples is excluded', function () {
    $this->artisan('docs:init', ['--features' => 'api_reference']);

    $config = include $this->configPath;

    expect($config['header_nav'])->toBeArray()
        ->and($config['header_nav'])->toHaveCount(2)
        ->and(collect($config['header_nav'])->pluck('title')->all())
        ->toBe(['Guides', 'API Reference']);
});

it('sets explicit header_nav with only Guides when both features are excluded', function () {
    $this->artisan('docs:init', ['--features' => '']);

    $config = include $this->configPath;

    expect($config['header_nav'])->toBeArray()
        ->and($config['header_nav'])->toHaveCount(1)
        ->and($config['header_nav'][0]['title'])->toBe('Guides');
});

it('includes all features when --features lists both', function () {
    $this->artisan('docs:init', ['--features' => 'api_reference,examples']);

    $config = include $this->configPath;

    expect($config['header_nav'])->toBeNull()
        ->and($config['openapi_file'])->not->toBeNull()
        ->and(file_exists($this->docsDir.'/openapi.yaml'))->toBeTrue();
});

it('ignores unknown feature names in --features', function () {
    $this->artisan('docs:init', ['--features' => 'api_reference,unknown_feature'])
        ->assertSuccessful();

    // Only api_reference should be recognized; examples is excluded
    $config = include $this->configPath;

    expect($config['header_nav'])->toBeArray()
        ->and(collect($config['header_nav'])->pluck('title')->all())
        ->toBe(['Guides', 'API Reference']);
});

it('creates only README.md when no features are selected', function () {
    $this->artisan('docs:init', ['--features' => ''])
        ->assertSuccessful();

    expect(file_exists($this->docsDir.'/README.md'))->toBeTrue()
        ->and(file_exists($this->docsDir.'/openapi.yaml'))->toBeFalse();
});

it('keeps config header_nav null when all features are selected by default', function () {
    // Without --features, tests select all features (backward-compatible)
    $this->artisan('docs:init');

    $config = include $this->configPath;

    expect($config['header_nav'])->toBeNull();
});
