<?php

use Illuminate\Support\Facades\File;
use LynkByte\DocsBuilder\DocsBuilder;
use LynkByte\DocsBuilder\OpenApiParser;

beforeEach(function () {
    $this->outputDir = config('docs-builder.output_dir');
});

afterEach(function () {
    if (is_dir($this->outputDir)) {
        File::deleteDirectory($this->outputDir);
    }
});

it('runs the docs:build command successfully', function () {
    $this->artisan('docs:build', ['--skip-assets' => true])
        ->assertSuccessful();
});

it('creates output files', function () {
    $this->artisan('docs:build', ['--skip-assets' => true]);

    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue()
        ->and(file_exists($this->outputDir.'/search-index.json'))->toBeTrue()
        ->and(file_exists($this->outputDir.'/installation/index.html'))->toBeTrue();
});

it('creates api endpoint pages', function () {
    $this->artisan('docs:build', ['--skip-assets' => true]);

    expect(file_exists($this->outputDir.'/api-reference/index.html'))->toBeTrue()
        ->and(file_exists($this->outputDir.'/api-reference/registerUser/index.html'))->toBeTrue()
        ->and(file_exists($this->outputDir.'/api-reference/loginUser/index.html'))->toBeTrue()
        ->and(file_exists($this->outputDir.'/api-reference/logoutUser/index.html'))->toBeTrue()
        ->and(file_exists($this->outputDir.'/api-reference/getCurrentUser/index.html'))->toBeTrue();
});

it('generates a valid search index', function () {
    $this->artisan('docs:build', ['--skip-assets' => true]);

    $indexPath = $this->outputDir.'/search-index.json';
    $content = json_decode(file_get_contents($indexPath), true);

    expect($content)->toBeArray()
        ->and(count($content))->toBeGreaterThan(0)
        ->and($content[0])->toHaveKeys(['title', 'url', 'section', 'type']);
});

it('outputs the correct page count', function () {
    // 3 doc pages (README, installation, api-reference) + 4 API endpoint pages = 7
    $this->artisan('docs:build', ['--skip-assets' => true])
        ->expectsOutputToContain('Pages built: 7')
        ->assertSuccessful();
});

it('parses the OpenAPI spec only once during build', function () {
    $mock = Mockery::mock(OpenApiParser::class);
    $mock->shouldReceive('parse')
        ->once()
        ->andReturn([
            'info' => ['title' => 'Test API', 'version' => '1.0.0'],
            'servers' => [['url' => 'https://api.example.com']],
            'tagIcons' => ['Users' => 'people'],
            'endpoints' => [
                'Users' => [
                    [
                        'operationId' => 'list-users',
                        'summary' => 'List Users',
                        'description' => 'Get all users',
                        'method' => 'GET',
                        'path' => '/users',
                        'parameters' => [],
                        'responses' => [],
                    ],
                ],
            ],
        ]);

    $builder = new DocsBuilder($mock);
    $builder->build();
});
