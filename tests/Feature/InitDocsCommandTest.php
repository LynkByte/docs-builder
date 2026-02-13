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

    // Clean up AI tool files
    $this->aiFiles = [
        base_path('llms.txt'),
        base_path('llms-full.txt'),
        base_path('.cursor/rules/docs-builder.mdc'),
        base_path('.github/copilot-instructions.md'),
        base_path('CLAUDE.md'),
    ];

    $this->aiDirs = [
        base_path('.cursor/rules'),
        base_path('.cursor'),
        base_path('.github'),
    ];

    foreach ($this->aiFiles as $file) {
        if (file_exists($file)) {
            File::delete($file);
        }
    }
});

afterEach(function () {
    if (is_dir($this->docsDir)) {
        File::deleteDirectory($this->docsDir);
    }

    if (file_exists($this->configPath)) {
        File::delete($this->configPath);
    }

    foreach ($this->aiFiles as $file) {
        if (file_exists($file)) {
            File::delete($file);
        }
    }

    foreach ($this->aiDirs as $dir) {
        if (is_dir($dir) && count(File::files($dir)) === 0) {
            File::deleteDirectory($dir);
        }
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

it('creates llms files with --with-ai=llms', function () {
    $this->artisan('docs:init', ['--with-ai' => ['llms']])
        ->assertSuccessful();

    expect(file_exists(base_path('llms.txt')))->toBeTrue()
        ->and(file_exists(base_path('llms-full.txt')))->toBeTrue();
});

it('creates cursor rules with --with-ai=cursor', function () {
    $this->artisan('docs:init', ['--with-ai' => ['cursor']])
        ->assertSuccessful();

    expect(file_exists(base_path('.cursor/rules/docs-builder.mdc')))->toBeTrue();
});

it('creates copilot instructions with --with-ai=copilot', function () {
    $this->artisan('docs:init', ['--with-ai' => ['copilot']])
        ->assertSuccessful();

    expect(file_exists(base_path('.github/copilot-instructions.md')))->toBeTrue();
});

it('creates claude instructions with --with-ai=claude', function () {
    $this->artisan('docs:init', ['--with-ai' => ['claude']])
        ->assertSuccessful();

    expect(file_exists(base_path('CLAUDE.md')))->toBeTrue();
});

it('creates multiple AI tool configs with multiple --with-ai flags', function () {
    $this->artisan('docs:init', ['--with-ai' => ['llms', 'cursor', 'copilot', 'claude']])
        ->assertSuccessful();

    expect(file_exists(base_path('llms.txt')))->toBeTrue()
        ->and(file_exists(base_path('llms-full.txt')))->toBeTrue()
        ->and(file_exists(base_path('.cursor/rules/docs-builder.mdc')))->toBeTrue()
        ->and(file_exists(base_path('.github/copilot-instructions.md')))->toBeTrue()
        ->and(file_exists(base_path('CLAUDE.md')))->toBeTrue();
});

it('skips existing AI files without --force', function () {
    file_put_contents(base_path('CLAUDE.md'), 'Custom claude content');

    $this->artisan('docs:init', ['--with-ai' => ['claude']])
        ->assertSuccessful();

    expect(file_get_contents(base_path('CLAUDE.md')))->toBe('Custom claude content');
});

it('overwrites existing AI files with --force', function () {
    file_put_contents(base_path('CLAUDE.md'), 'Custom claude content');

    $this->artisan('docs:init', ['--force' => true, '--with-ai' => ['claude']])
        ->assertSuccessful();

    expect(file_get_contents(base_path('CLAUDE.md')))->not->toBe('Custom claude content')
        ->and(file_get_contents(base_path('CLAUDE.md')))->toContain('docs-builder');
});

it('ignores invalid --with-ai values', function () {
    $this->artisan('docs:init', ['--with-ai' => ['invalid-tool']])
        ->assertSuccessful();

    // No AI files should be created
    expect(file_exists(base_path('llms.txt')))->toBeFalse()
        ->and(file_exists(base_path('CLAUDE.md')))->toBeFalse();
});

it('skips AI selection in non-interactive mode without --with-ai', function () {
    $this->artisan('docs:init', ['--no-interaction' => true])
        ->assertSuccessful();

    // No AI files should be created
    expect(file_exists(base_path('llms.txt')))->toBeFalse()
        ->and(file_exists(base_path('CLAUDE.md')))->toBeFalse();
});
