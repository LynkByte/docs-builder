<?php

namespace LynkByte\DocsBuilder\Commands;

use Illuminate\Console\Command;
use LynkByte\DocsBuilder\AssetManager;
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
            $assetManager = new AssetManager(
                outputDir: config('docs-builder.output_dir') ?? '',
                assetMode: config('docs-builder.asset_mode', 'precompiled'),
                themeName: config('docs-builder.theme_name', 'default'),
            );

            $assetManager->handle(fn (string $description, callable $task) => $this->components->task($description, $task));
        }

        // Step 2: Build documentation pages
        $result = null;

        try {
            $this->components->task('Building pages', function () use (&$result): void {
                $builder = new DocsBuilder;
                $result = $builder->build();
            });
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
}
