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
                            {--features= : Comma-separated optional sections to include (api_reference,examples). Omit to be prompted interactively.}';

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

        // Step 1: Ask which optional features to include
        $features = $this->resolveFeatureSelection();

        // Step 2: Publish config file (tailored to selected features)
        $this->publishConfig($features);

        // Step 3: Create docs directory with stub files
        $this->scaffoldDocs($features);

        $this->newLine();
        $this->components->info('Documentation initialized successfully!');

        $bullets = [
            'Config: config/docs-builder.php',
            'Docs directory: docs/',
        ];

        $starterFiles = ['docs/README.md'];
        if (in_array('api_reference', $features)) {
            $starterFiles[] = 'docs/openapi.yaml';
        }
        $bullets[] = 'Starter files: '.implode(', ', $starterFiles);

        $this->components->bulletList($bullets);

        $this->newLine();
        $this->line('  <fg=gray>Next steps:</>');
        $this->line('  <fg=gray>1.</> Edit <fg=cyan>config/docs-builder.php</> to configure navigation and branding');
        $this->line('  <fg=gray>2.</> Add Markdown files to the <fg=cyan>docs/</> directory');
        $this->line('  <fg=gray>3.</> Run <fg=cyan>php artisan docs:build</> to generate the static site');

        return self::SUCCESS;
    }

    /**
     * Publish the config file if it doesn't exist.
     *
     * @param  list<string>  $features
     */
    private function publishConfig(array $features): void
    {
        $configPath = config_path('docs-builder.php');

        if (file_exists($configPath) && ! $this->option('force')) {
            $this->components->warn('Config file already exists, skipping. Use --force to overwrite.');

            return;
        }

        $this->components->task('Publishing config file', function () use ($configPath, $features): void {
            File::copy($this->packagePath('config/docs-builder.php'), $configPath);
            $this->tailorConfig($configPath, $features);
        });
    }

    /**
     * Create the docs directory and copy stub files.
     *
     * @param  list<string>  $features
     */
    private function scaffoldDocs(array $features): void
    {
        $docsDir = base_path('docs');
        $force = (bool) $this->option('force');

        if (! is_dir($docsDir)) {
            File::makeDirectory($docsDir, 0755, true);
        }

        $stubs = [
            'README.md' => 'README.md',
        ];

        if (in_array('api_reference', $features)) {
            $stubs['openapi.yaml'] = 'openapi.yaml';
        }

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
     * Ask the user which optional features to include.
     *
     * In non-interactive mode (e.g. CI) or during tests, all features are
     * selected so that the command remains fully backward-compatible.
     *
     * @return list<string>
     */
    private function resolveFeatureSelection(): array
    {
        $allFeatures = ['api_reference', 'examples'];

        // Explicit --features option takes priority (useful for CI/scripts/testing)
        if ($this->option('features') !== null) {
            $value = trim($this->option('features'));

            if ($value === '') {
                return [];
            }

            $requested = array_map('trim', explode(',', $value));

            return array_values(array_intersect($requested, $allFeatures));
        }

        // Non-interactive mode: select everything (backward-compatible)
        if (! $this->input->isInteractive() || app()->runningUnitTests()) {
            return $allFeatures;
        }

        return multiselect(
            label: 'Which optional sections would you like to include?',
            options: [
                'api_reference' => 'API Reference (OpenAPI spec)',
                'examples' => 'Examples',
            ],
            default: $allFeatures,
            hint: 'Use space to toggle, enter to confirm.',
        );
    }

    /**
     * Tailor the published config file based on selected features.
     *
     * Performs targeted string replacements so the config only contains
     * the sections the user opted into, while keeping all keys present
     * (set to null / empty) for easy future enablement.
     *
     * @param  list<string>  $features
     */
    private function tailorConfig(string $configPath, array $features): void
    {
        $hasApi = in_array('api_reference', $features);
        $hasExamples = in_array('examples', $features);

        // Nothing to change when all features are selected
        if ($hasApi && $hasExamples) {
            return;
        }

        $content = File::get($configPath);

        // --- OpenAPI / API Reference ---
        if (! $hasApi) {
            $content = str_replace(
                "'openapi_file' => base_path('docs/openapi.yaml'),",
                "'openapi_file' => null,",
                $content,
            );

            // Replace api_tag_icons array with an empty array
            $content = preg_replace(
                "/('api_tag_icons' => )\[.*?\],/s",
                "'api_tag_icons' => [],",
                $content,
            );
        }

        // --- Header Navigation ---
        // When any feature is excluded we must set an explicit header_nav
        // instead of null (which would show all default links).
        $navLinks = [];
        $navLinks[] = "            ['title' => 'Guides', 'url' => '/docs/index.html'],";

        if ($hasApi) {
            $navLinks[] = "            ['title' => 'API Reference', 'url' => '/docs/api-reference/index.html'],";
        }

        if ($hasExamples) {
            $navLinks[] = "            ['title' => 'Examples', 'url' => '#'],";
        }

        $replacement = "'header_nav' => [\n".implode("\n", $navLinks)."\n        ],";

        $content = str_replace(
            "'header_nav' => null,",
            $replacement,
            $content,
        );

        File::put($configPath, $content);
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
