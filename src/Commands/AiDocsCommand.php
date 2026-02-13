<?php

namespace LynkByte\DocsBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\multiselect;

class AiDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:ai
                            {--force : Replace existing docs-builder sections}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish AI coding assistant configurations for docs-builder';

    /**
     * Sentinel markers used to identify docs-builder content in existing files.
     */
    private const MARKER_START = '<!-- docs-builder:start -->';

    private const MARKER_END = '<!-- docs-builder:end -->';

    /**
     * Available AI tool configurations and their file mappings.
     *
     * Each tool defines a label (for the interactive prompt), a mode
     * ('appendable' or 'standalone'), and a list of files to publish.
     *
     * - appendable: content is wrapped in markers and appended to existing files
     * - standalone: file is created as-is, skipped if it already exists
     *
     * @var array<string, array{label: string, mode: string, files: array<array{stub: string, dest: string}>}>
     */
    private const AI_TOOLS = [
        'llms' => [
            'label' => 'llms.txt — LLM-friendly documentation',
            'mode' => 'appendable',
            'files' => [
                ['stub' => 'llms.txt', 'dest' => 'llms.txt'],
                ['stub' => 'llms-full.txt', 'dest' => 'llms-full.txt'],
            ],
        ],
        'cursor' => [
            'label' => 'Cursor — .cursor/rules/',
            'mode' => 'standalone',
            'files' => [
                ['stub' => 'cursor-rules.mdc', 'dest' => '.cursor/rules/docs-builder.mdc'],
            ],
        ],
        'copilot' => [
            'label' => 'GitHub Copilot — .github/copilot-instructions.md',
            'mode' => 'appendable',
            'files' => [
                ['stub' => 'copilot-instructions.md', 'dest' => '.github/copilot-instructions.md'],
            ],
        ],
        'claude' => [
            'label' => 'Claude — CLAUDE.md',
            'mode' => 'appendable',
            'files' => [
                ['stub' => 'CLAUDE.md', 'dest' => 'CLAUDE.md'],
            ],
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $selectedTools = $this->resolveToolSelection();

        if ($selectedTools === []) {
            $this->components->info('No AI tools selected.');

            return self::SUCCESS;
        }

        $this->components->info('Publishing AI tool configurations...');

        $force = (bool) $this->option('force');

        foreach ($selectedTools as $toolKey) {
            $tool = self::AI_TOOLS[$toolKey];

            foreach ($tool['files'] as $file) {
                $stubPath = $this->aiStubsPath($file['stub']);
                $targetPath = base_path($file['dest']);

                if ($tool['mode'] === 'standalone') {
                    $this->publishStandalone($stubPath, $targetPath, $file['dest'], $force);
                } else {
                    $this->publishAppendable($stubPath, $targetPath, $file['dest'], $force);
                }
            }
        }

        $this->newLine();
        $this->components->info('Done!');

        return self::SUCCESS;
    }

    /**
     * Publish a standalone file (create or skip, overwrite with --force).
     */
    private function publishStandalone(string $stubPath, string $targetPath, string $displayPath, bool $force): void
    {
        if (file_exists($targetPath) && ! $force) {
            $this->components->warn("{$displayPath} already exists, skipping. Use --force to overwrite.");

            return;
        }

        $targetDir = dirname($targetPath);
        if (! is_dir($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $this->components->task(file_exists($targetPath) ? "Overwriting {$displayPath}" : "Creating {$displayPath}", function () use ($stubPath, $targetPath): void {
            File::copy($stubPath, $targetPath);
        });
    }

    /**
     * Publish an appendable file (create, append, or replace section).
     *
     * - File does not exist: create with markers
     * - File exists, no markers: append with separator
     * - File exists, has markers: skip (or replace with --force)
     */
    private function publishAppendable(string $stubPath, string $targetPath, string $displayPath, bool $force): void
    {
        $stubContent = File::get($stubPath);
        $wrappedContent = self::MARKER_START."\n".$stubContent.self::MARKER_END."\n";

        $targetDir = dirname($targetPath);
        if (! is_dir($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // File does not exist — create it
        if (! file_exists($targetPath)) {
            $this->components->task("Creating {$displayPath}", function () use ($targetPath, $wrappedContent): void {
                File::put($targetPath, $wrappedContent);
            });

            return;
        }

        $existingContent = File::get($targetPath);
        $hasMarkers = str_contains($existingContent, self::MARKER_START);

        // File exists and already has our section
        if ($hasMarkers && ! $force) {
            $this->components->warn("{$displayPath} already contains docs-builder section, skipping. Use --force to replace.");

            return;
        }

        // --force with existing markers: replace the section
        if ($hasMarkers && $force) {
            $this->components->task("Replacing docs-builder section in {$displayPath}", function () use ($targetPath, $existingContent, $wrappedContent): void {
                $updated = $this->replaceMarkedSection($existingContent, $wrappedContent);
                File::put($targetPath, $updated);
            });

            return;
        }

        // File exists but no markers — append
        $this->components->task("Appending to {$displayPath}", function () use ($targetPath, $existingContent, $wrappedContent): void {
            $separator = str_ends_with(rtrim($existingContent), '---') ? "\n\n" : "\n\n---\n\n";
            File::put($targetPath, rtrim($existingContent)."{$separator}{$wrappedContent}");
        });
    }

    /**
     * Replace the marked docs-builder section within existing content.
     */
    private function replaceMarkedSection(string $content, string $replacement): string
    {
        $startPos = strpos($content, self::MARKER_START);
        $endPos = strpos($content, self::MARKER_END);

        if ($startPos === false || $endPos === false) {
            return $content;
        }

        $endPos += strlen(self::MARKER_END);

        // Consume trailing newline if present
        if (isset($content[$endPos]) && $content[$endPos] === "\n") {
            $endPos++;
        }

        $before = substr($content, 0, $startPos);
        $after = substr($content, $endPos);

        return $before.$replacement.$after;
    }

    /**
     * Resolve which AI tools were selected via interactive prompt.
     *
     * @return array<string>
     */
    private function resolveToolSelection(): array
    {
        // Non-interactive mode: skip selection
        if (! $this->input->isInteractive() || app()->runningUnitTests()) {
            return array_keys(self::AI_TOOLS);
        }

        // Interactive: show multiselect prompt
        $options = [];
        foreach (self::AI_TOOLS as $key => $tool) {
            $options[$key] = $tool['label'];
        }

        return multiselect(
            label: 'Which AI tool configurations would you like to publish?',
            options: $options,
            hint: 'Use space to select, enter to confirm. Leave empty to skip.',
        );
    }

    /**
     * Resolve the path to an AI stub file.
     *
     * Uses published stubs from the host app (stubs/docs-builder/ai/) if they
     * exist, otherwise falls back to the package's own stubs/ai/ directory.
     */
    private function aiStubsPath(string $relativePath): string
    {
        $publishedPath = base_path('stubs/docs-builder/ai/'.$relativePath);

        if (file_exists($publishedPath)) {
            return $publishedPath;
        }

        return dirname(__DIR__, 2).'/stubs/ai/'.$relativePath;
    }
}
