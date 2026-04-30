<?php

use LynkByte\DocsBuilder\NavigationBuilder;

it('builds navigation from config sections', function () {
    $config = [
        'navigation' => [
            [
                'title' => 'Getting Started',
                'pages' => [
                    ['title' => 'Home', 'file' => 'README.md', 'icon' => 'home'],
                    ['title' => 'Installation', 'file' => 'installation.md'],
                ],
            ],
            [
                'title' => 'Advanced',
                'pages' => [
                    ['title' => 'Configuration', 'file' => 'config.md', 'icon' => 'settings'],
                ],
            ],
        ],
    ];

    $builder = new NavigationBuilder($config, '/docs');
    $navigation = $builder->build();

    expect($navigation)->toHaveCount(2)
        ->and($navigation[0]['title'])->toBe('Getting Started')
        ->and($navigation[0]['pages'])->toHaveCount(2)
        ->and($navigation[0]['pages'][0]->title)->toBe('Home')
        ->and($navigation[0]['pages'][0]->slug)->toBe('index')
        ->and($navigation[0]['pages'][0]->url)->toBe('/docs/index.html')
        ->and($navigation[0]['pages'][0]->icon)->toBe('home')
        ->and($navigation[0]['pages'][1]->title)->toBe('Installation')
        ->and($navigation[0]['pages'][1]->slug)->toBe('installation')
        ->and($navigation[0]['pages'][1]->url)->toBe('/docs/installation/index.html')
        ->and($navigation[0]['pages'][1]->icon)->toBeNull()
        ->and($navigation[1]['title'])->toBe('Advanced')
        ->and($navigation[1]['pages'])->toHaveCount(1)
        ->and($navigation[1]['pages'][0]->title)->toBe('Configuration')
        ->and($navigation[1]['pages'][0]->icon)->toBe('settings');
});

it('returns empty array when config has no navigation', function () {
    $builder = new NavigationBuilder([], '/docs');
    $navigation = $builder->build();

    expect($navigation)->toBe([]);
});

it('flattens nested navigation into a flat page list', function () {
    $config = [
        'navigation' => [
            [
                'title' => 'Section A',
                'pages' => [
                    ['title' => 'Page 1', 'file' => 'page1.md'],
                    ['title' => 'Page 2', 'file' => 'page2.md'],
                ],
            ],
            [
                'title' => 'Section B',
                'pages' => [
                    ['title' => 'Page 3', 'file' => 'page3.md'],
                ],
            ],
        ],
    ];

    $builder = new NavigationBuilder($config, '/docs');
    $navigation = $builder->build();

    $flat = array_merge(
        $navigation[0]['pages'],
        $navigation[1]['pages'],
    );

    expect($flat)->toHaveCount(3)
        ->and($flat[0]->title)->toBe('Page 1')
        ->and($flat[1]->title)->toBe('Page 2')
        ->and($flat[2]->title)->toBe('Page 3');
});

it('returns correct prev and next for a middle page', function () {
    $config = [
        'navigation' => [
            [
                'title' => 'Docs',
                'pages' => [
                    ['title' => 'First', 'file' => 'first.md'],
                    ['title' => 'Middle', 'file' => 'middle.md'],
                    ['title' => 'Last', 'file' => 'last.md'],
                ],
            ],
        ],
    ];

    $builder = new NavigationBuilder($config, '/docs');
    $builder->build();

    [$prev, $next] = $builder->getPrevNextPages('middle');

    expect($prev)->toBe(['title' => 'First', 'url' => '/docs/first/index.html'])
        ->and($next)->toBe(['title' => 'Last', 'url' => '/docs/last/index.html']);
});

it('returns null prev for the first page', function () {
    $config = [
        'navigation' => [
            [
                'title' => 'Docs',
                'pages' => [
                    ['title' => 'First', 'file' => 'first.md'],
                    ['title' => 'Second', 'file' => 'second.md'],
                ],
            ],
        ],
    ];

    $builder = new NavigationBuilder($config, '/docs');
    $builder->build();

    [$prev, $next] = $builder->getPrevNextPages('first');

    expect($prev)->toBeNull()
        ->and($next)->toBe(['title' => 'Second', 'url' => '/docs/second/index.html']);
});

it('returns null next for the last page', function () {
    $config = [
        'navigation' => [
            [
                'title' => 'Docs',
                'pages' => [
                    ['title' => 'First', 'file' => 'first.md'],
                    ['title' => 'Last', 'file' => 'last.md'],
                ],
            ],
        ],
    ];

    $builder = new NavigationBuilder($config, '/docs');
    $builder->build();

    [$prev, $next] = $builder->getPrevNextPages('last');

    expect($prev)->toBe(['title' => 'First', 'url' => '/docs/first/index.html'])
        ->and($next)->toBeNull();
});

it('converts filename to slug by removing .md extension', function () {
    $builder = new NavigationBuilder([], '/docs');

    expect($builder->fileToSlug('installation.md'))->toBe('installation')
        ->and($builder->fileToSlug('getting-started.md'))->toBe('getting-started')
        ->and($builder->fileToSlug('README.md'))->toBe('index')
        ->and($builder->fileToSlug('nested/path.md'))->toBe('nested/path')
        ->and($builder->fileToSlug('no-extension'))->toBe('no-extension');
});

it('converts slug to URL with base path', function () {
    $builder = new NavigationBuilder([], '/docs');

    expect($builder->slugToUrl('installation'))->toBe('/docs/installation/index.html')
        ->and($builder->slugToUrl('index'))->toBe('/docs/index.html')
        ->and($builder->slugToUrl('nested/path'))->toBe('/docs/nested/path/index.html');
});
