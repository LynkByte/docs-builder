<?php

namespace LynkByte\DocsBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InitDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:init
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a starter documentation directory with example files';

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
     * Resolve the path to a file relative to the package root.
     */
    private function packagePath(string $relativePath): string
    {
        return dirname(__DIR__, 2).'/'.$relativePath;
    }
}
