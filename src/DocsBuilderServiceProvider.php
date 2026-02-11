<?php

namespace LynkByte\DocsBuilder;

use Illuminate\Support\ServiceProvider;
use LynkByte\DocsBuilder\Commands\BuildDocsCommand;
use LynkByte\DocsBuilder\Commands\InitDocsCommand;

class DocsBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/docs-builder.php', 'docs-builder');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'docs-builder');

        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildDocsCommand::class,
                InitDocsCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/docs-builder.php' => config_path('docs-builder.php'),
            ], 'docs-builder-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/docs-builder'),
            ], 'docs-builder-views');

            $this->publishes([
                __DIR__.'/../dist' => public_path('docs/assets'),
            ], 'docs-builder-assets');

            $this->publishes([
                __DIR__.'/../resources/css/docs.css' => resource_path('css/docs.css'),
            ], 'docs-builder-css');

            $this->publishes([
                __DIR__.'/../resources/js/docs.js' => resource_path('js/docs.js'),
            ], 'docs-builder-js');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/docs-builder'),
            ], 'docs-builder-stubs');
        }
    }
}
