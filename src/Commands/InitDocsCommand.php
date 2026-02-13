<?php

namespace LynkByte\DocsBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\multiselect;

class InitDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:init
                            {--force : Overwrite existing files}
                            {--with-ai=* : Include AI tool configurations (llms, cursor, copilot, claude)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a starter documentation directory with example files';

    /**
     * Available AI tool configurations and their file mappings.
     *
     * @var array<string, array{label: string, files: array<array{stub: string, dest: string}>}>
     */
    private const AI_TOOLS = [
        'llms' => [
            'label' => 'llms.txt — LLM-friendly documentation',
            'files' => [
                ['stub' => 'llms.txt', 'dest' => 'llms.txt'],
                ['stub' => 'llms-full.txt', 'dest' => 'llms-full.txt'],
            ],
        ],
        'cursor' => [
            'label' => 'Cursor — .cursor/rules/',
            'files' => [
                ['stub' => 'cursor-rules.mdc', 'dest' => '.cursor/rules/docs-builder.mdc'],
            ],
        ],
        'copilot' => [
            'label' => 'GitHub Copilot — .github/copilot-instructions.md',
            'files' => [
                ['stub' => 'copilot-instructions.md', 'dest' => '.github/copilot-instructions.md'],
            ],
        ],
        'claude' => [
            'label' => 'Claude — CLAUDE.md',
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
        $this->components->info('Initializing documentation...');

        // Step 1: Publish config file
        $this->publishConfig();

        // Step 2: Create docs directory with stub files
        $this->scaffoldDocs();

        // Step 3: AI tool configurations
        $this->scaffoldAiTools();

        $this->newLine();
        $this->components->info('Documentation initialized successfully!');
        $this->components->bulletList([
            'Config: config/docs-builder.php',
            'Docs directory: docs/',
            'Starter files: docs/README.md, docs/openapi.yaml',
        ]);

        $this->newLine();
        $this->line('  <fg=gray>Next steps:</>');
        $this->line('  <fg=gray>1.</> Edit <fg=cyan>config/docs-builder.php</> to configure navigation and branding');
        $this->line('  <fg=gray>2.</> Add Markdown files to the <fg=cyan>docs/</> directory');
        $this->line('  <fg=gray>3.</> Run <fg=cyan>php artisan docs:build</> to generate the static site');

        return self::SUCCESS;
    }

    /**
     * Publish the config file if it doesn't exist.
     */
    private function publishConfig(): void
    {
        $configPath = config_path('docs-builder.php');

        if (file_exists($configPath) && ! $this->option('force')) {
            $this->components->warn('Config file already exists, skipping. Use --force to overwrite.');

            return;
        }

        $this->components->task('Publishing config file', function () use ($configPath): void {
            File::copy($this->packagePath('config/docs-builder.php'), $configPath);
        });
    }

    /**
     * Create the docs directory and copy stub files.
     */
    private function scaffoldDocs(): void
    {
        $docsDir = base_path('docs');
        $force = (bool) $this->option('force');

        if (! is_dir($docsDir)) {
            File::makeDirectory($docsDir, 0755, true);
        }

        $stubs = [
            'README.md' => 'README.md',
            'openapi.yaml' => 'openapi.yaml',
        ];

        foreach ($stubs as $stub => $target) {
            $targetPath = $docsDir.'/'.$target;
            $stubPath = $this->stubsPath($stub);

            if (file_exists($targetPath) && ! $force) {
                $this->components->warn("{$target} already exists, skipping. Use --force to overwrite.");

                continue;
            }

            $this->components->task("Creating docs/{$target}", function () use ($stubPath, $targetPath): void {
                File::copy($stubPath, $targetPath);
            });
        }
    }

    /**
     * Prompt for and scaffold AI tool configuration files.
     */
    private function scaffoldAiTools(): void
    {
        $selectedTools = $this->resolveAiToolSelection();

        if ($selectedTools === []) {
            return;
        }

        $this->newLine();
        $this->components->info('Adding AI tool configurations...');

        $force = (bool) $this->option('force');

        foreach ($selectedTools as $toolKey) {
            $tool = self::AI_TOOLS[$toolKey];

            foreach ($tool['files'] as $file) {
                $stubPath = $this->aiStubsPath($file['stub']);
                $targetPath = base_path($file['dest']);
                $targetDir = dirname($targetPath);

                if (file_exists($targetPath) && ! $force) {
                    $this->components->warn("{$file['dest']} already exists, skipping. Use --force to overwrite.");

                    continue;
                }

                if (! is_dir($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }

                $this->components->task("Creating {$file['dest']}", function () use ($stubPath, $targetPath): void {
                    File::copy($stubPath, $targetPath);
                });
            }
        }
    }

    /**
     * Resolve which AI tools were selected via --with-ai flags or interactive prompt.
     *
     * @return array<string>
     */
    private function resolveAiToolSelection(): array
    {
        $withAi = $this->option('with-ai');

        // Explicit --with-ai flags provided
        if ($withAi !== []) {
            return array_values(array_intersect($withAi, array_keys(self::AI_TOOLS)));
        }

        // Non-interactive mode: skip AI selection
        if (! $this->input->isInteractive() || app()->runningUnitTests()) {
            return [];
        }

        // Interactive: show multiselect prompt
        $options = [];
        foreach (self::AI_TOOLS as $key => $tool) {
            $options[$key] = $tool['label'];
        }

        return multiselect(
            label: 'Which AI tool configurations would you like to include?',
            options: $options,
            hint: 'Use space to select, enter to confirm. Leave empty to skip.',
        );
    }

    /**
     * Resolve the path to a stub file.
     *
     * Uses published stubs from the host app (stubs/docs-builder/) if they
     * exist, otherwise falls back to the package's own stubs/ directory.
     */
    private function stubsPath(string $relativePath): string
    {
        $publishedPath = base_path('stubs/docs-builder/'.$relativePath);

        if (file_exists($publishedPath)) {
            return $publishedPath;
        }

        return dirname(__DIR__, 2).'/stubs/'.$relativePath;
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

    /**
     * Resolve the path to a file relative to the package root.
     */
    private function packagePath(string $relativePath): string
    {
        return dirname(__DIR__, 2).'/'.$relativePath;
    }
}
