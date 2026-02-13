<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
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
    foreach ($this->aiFiles as $file) {
        if (file_exists($file)) {
            File::delete($file);
        }
    }

    foreach ($this->aiDirs as $dir) {
        if (is_dir($dir) && count(File::allFiles($dir)) === 0) {
            File::deleteDirectory($dir);
        }
    }
});

// ── Creating files when missing ──────────────────────────────────────

it('creates llms files when missing', function () {
    $this->artisan('docs:ai')
        ->assertSuccessful();

    expect(file_exists(base_path('llms.txt')))->toBeTrue()
        ->and(file_exists(base_path('llms-full.txt')))->toBeTrue()
        ->and(file_get_contents(base_path('llms.txt')))->toContain('<!-- docs-builder:start -->')
        ->and(file_get_contents(base_path('llms.txt')))->toContain('<!-- docs-builder:end -->');
});

it('creates cursor rules when missing', function () {
    $this->artisan('docs:ai')
        ->assertSuccessful();

    expect(file_exists(base_path('.cursor/rules/docs-builder.mdc')))->toBeTrue();
});

it('creates copilot instructions when missing', function () {
    $this->artisan('docs:ai')
        ->assertSuccessful();

    expect(file_exists(base_path('.github/copilot-instructions.md')))->toBeTrue()
        ->and(file_get_contents(base_path('.github/copilot-instructions.md')))->toContain('<!-- docs-builder:start -->');
});

it('creates claude instructions when missing', function () {
    $this->artisan('docs:ai')
        ->assertSuccessful();

    expect(file_exists(base_path('CLAUDE.md')))->toBeTrue()
        ->and(file_get_contents(base_path('CLAUDE.md')))->toContain('<!-- docs-builder:start -->');
});

// ── Appending to existing files ──────────────────────────────────────

it('appends to existing file with separator and markers', function () {
    $existingContent = "# My Project\n\nExisting documentation.\n";
    file_put_contents(base_path('CLAUDE.md'), $existingContent);

    $this->artisan('docs:ai')
        ->assertSuccessful();

    $content = file_get_contents(base_path('CLAUDE.md'));

    expect($content)->toStartWith('# My Project')
        ->and($content)->toContain('---')
        ->and($content)->toContain('<!-- docs-builder:start -->')
        ->and($content)->toContain('<!-- docs-builder:end -->')
        ->and($content)->toContain('docs-builder');
});

// ── Skipping when markers already present ────────────────────────────

it('skips when docs-builder section already exists', function () {
    $content = "# My Project\n\n---\n\n<!-- docs-builder:start -->\nOriginal section\n<!-- docs-builder:end -->\n";
    file_put_contents(base_path('CLAUDE.md'), $content);

    $this->artisan('docs:ai')
        ->assertSuccessful();

    expect(file_get_contents(base_path('CLAUDE.md')))->toBe($content);
});

// ── Force replaces existing section ──────────────────────────────────

it('replaces docs-builder section with --force while preserving user content', function () {
    $content = "# My Project\n\nUser notes.\n\n---\n\n<!-- docs-builder:start -->\nOld docs-builder content\n<!-- docs-builder:end -->\n\n## Footer\n\nMore user content.\n";
    file_put_contents(base_path('CLAUDE.md'), $content);

    $this->artisan('docs:ai', ['--force' => true])
        ->assertSuccessful();

    $updated = file_get_contents(base_path('CLAUDE.md'));

    // User content preserved
    expect($updated)->toContain('# My Project')
        ->and($updated)->toContain('User notes.')
        ->and($updated)->toContain('## Footer')
        ->and($updated)->toContain('More user content.')
        // Old content replaced
        ->and($updated)->not->toContain('Old docs-builder content')
        // New content present
        ->and($updated)->toContain('<!-- docs-builder:start -->')
        ->and($updated)->toContain('<!-- docs-builder:end -->')
        ->and($updated)->toContain('Claude Code Instructions');
});

// ── Standalone (cursor) behavior ─────────────────────────────────────

it('skips existing standalone cursor file without --force', function () {
    $dir = base_path('.cursor/rules');
    if (! is_dir($dir)) {
        File::makeDirectory($dir, 0755, true);
    }

    file_put_contents(base_path('.cursor/rules/docs-builder.mdc'), 'Custom cursor rules');

    $this->artisan('docs:ai')
        ->assertSuccessful();

    expect(file_get_contents(base_path('.cursor/rules/docs-builder.mdc')))->toBe('Custom cursor rules');
});

it('overwrites standalone cursor file with --force', function () {
    $dir = base_path('.cursor/rules');
    if (! is_dir($dir)) {
        File::makeDirectory($dir, 0755, true);
    }

    file_put_contents(base_path('.cursor/rules/docs-builder.mdc'), 'Custom cursor rules');

    $this->artisan('docs:ai', ['--force' => true])
        ->assertSuccessful();

    expect(file_get_contents(base_path('.cursor/rules/docs-builder.mdc')))->not->toBe('Custom cursor rules')
        ->and(file_get_contents(base_path('.cursor/rules/docs-builder.mdc')))->toContain('docs-builder');
});

// ── All tools at once ────────────────────────────────────────────────

it('publishes all AI tool configs in non-interactive mode', function () {
    $this->artisan('docs:ai')
        ->assertSuccessful();

    expect(file_exists(base_path('llms.txt')))->toBeTrue()
        ->and(file_exists(base_path('llms-full.txt')))->toBeTrue()
        ->and(file_exists(base_path('.cursor/rules/docs-builder.mdc')))->toBeTrue()
        ->and(file_exists(base_path('.github/copilot-instructions.md')))->toBeTrue()
        ->and(file_exists(base_path('CLAUDE.md')))->toBeTrue();
});

// ── Appendable files don't have duplicate separators ─────────────────

it('does not add extra separator when existing content already ends with separator', function () {
    $existingContent = "# My Project\n\nSome content.\n\n---";
    file_put_contents(base_path('CLAUDE.md'), $existingContent);

    $this->artisan('docs:ai')
        ->assertSuccessful();

    $content = file_get_contents(base_path('CLAUDE.md'));

    // Should not have double ---
    expect($content)->not->toContain("---\n\n---");
});
