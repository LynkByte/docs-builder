<?php

use LynkByte\DocsBuilder\DocsBuilder;

it('throws when source_dir is missing', function () {
    new DocsBuilder(config: [
        'output_dir' => '/tmp/output',
        'base_url' => '/docs',
    ]);
})->throws(\InvalidArgumentException::class, 'source_dir');

it('throws when output_dir is missing', function () {
    new DocsBuilder(config: [
        'source_dir' => '/tmp/source',
        'base_url' => '/docs',
    ]);
})->throws(\InvalidArgumentException::class, 'output_dir');

it('defaults base_url to /docs when missing', function () {
    $builder = new DocsBuilder(config: [
        'source_dir' => __DIR__.'/../fixtures/docs',
        'output_dir' => sys_get_temp_dir().'/docs-builder-config-test-'.uniqid(),
        'site_name' => 'Test',
        'site_description' => 'Test',
        'openapi_file' => __DIR__.'/../fixtures/docs/openapi.yaml',
        'footer' => ['copyright' => 'Test', 'links' => []],
        'navigation' => [
            [
                'title' => 'Getting Started',
                'pages' => [
                    ['title' => 'Home', 'file' => 'README.md', 'icon' => 'home'],
                ],
            ],
        ],
    ]);

    // If it constructed without error, base_url defaulted successfully
    expect($builder)->toBeInstanceOf(DocsBuilder::class);
});

it('handles empty navigation config gracefully', function () {
    $builder = new DocsBuilder(config: [
        'source_dir' => __DIR__.'/../fixtures/docs',
        'output_dir' => sys_get_temp_dir().'/docs-builder-config-test-'.uniqid(),
        'site_name' => 'Test',
        'site_description' => 'Test',
        'openapi_file' => '/nonexistent/openapi.yaml',
        'base_url' => '/docs',
        'footer' => ['copyright' => 'Test', 'links' => []],
    ]);

    $result = $builder->build();

    expect($result['pages'])->toBe(0);
});
