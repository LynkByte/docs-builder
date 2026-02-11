<?php

namespace LynkByte\DocsBuilder\Tests;

use LynkByte\DocsBuilder\DocsBuilderServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DocsBuilderServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $fixturesPath = __DIR__.'/fixtures';

        $app['config']->set('docs-builder.source_dir', $fixturesPath.'/docs');
        $app['config']->set('docs-builder.output_dir', sys_get_temp_dir().'/docs-builder-test-'.uniqid());
        $app['config']->set('docs-builder.openapi_file', $fixturesPath.'/docs/openapi.yaml');
        $app['config']->set('docs-builder.base_url', '/docs');
        $app['config']->set('docs-builder.site_name', 'Test Docs');
        $app['config']->set('docs-builder.site_description', 'Test documentation site');
        $app['config']->set('docs-builder.navigation', [
            [
                'title' => 'Getting Started',
                'pages' => [
                    ['title' => 'Home', 'file' => 'README.md', 'icon' => 'home'],
                    ['title' => 'Installation', 'file' => 'installation.md', 'icon' => 'download'],
                ],
            ],
            [
                'title' => 'API',
                'pages' => [
                    ['title' => 'API Reference', 'file' => 'api-reference.md', 'icon' => 'api', 'layout' => 'api-reference'],
                ],
            ],
        ]);
    }
}
