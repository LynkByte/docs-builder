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
