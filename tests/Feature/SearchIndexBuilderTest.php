<?php

use LynkByte\DocsBuilder\SearchIndexBuilder;

beforeEach(function () {
    $this->builder = new SearchIndexBuilder;
});

it('starts with zero entries', function () {
    expect($this->builder->count())->toBe(0);
});

it('adds page entries', function () {
    $this->builder->addPage(
        title: 'Getting Started',
        url: '/docs/getting-started/index.html',
        section: 'Guides',
        headings: [['id' => 'install', 'text' => 'Installation', 'level' => 2]],
        plainText: 'How to install the application.',
        description: 'Installation guide.',
        icon: 'home',
    );

    expect($this->builder->count())->toBe(1);

    $json = json_decode($this->builder->toJson(), true);

    expect($json[0]['title'])->toBe('Getting Started')
        ->and($json[0]['type'])->toBe('doc')
        ->and($json[0]['icon'])->toBe('home')
        ->and($json[0]['section'])->toBe('Guides');
});

it('adds api endpoint entries', function () {
    $this->builder->addEndpoint(
        title: 'Register User',
        url: '/docs/api-reference/registerUser/index.html',
        method: 'POST',
        path: '/auth/register',
        description: 'Creates a new user account.',
        tag: 'Authentication',
    );

    $json = json_decode($this->builder->toJson(), true);

    expect($json[0]['type'])->toBe('api-endpoint')
        ->and($json[0]['method'])->toBe('POST')
        ->and($json[0]['section'])->toBe('API Reference');
});

it('produces valid json', function () {
    $this->builder->addPage(
        title: 'Test',
        url: '/test',
        section: 'Test',
        headings: [],
        plainText: 'Test content',
        description: 'Test desc',
    );

    $json = $this->builder->toJson();
    $decoded = json_decode($json, true);

    expect($decoded)->toBeArray()
        ->and(json_last_error())->toBe(JSON_ERROR_NONE);
});

it('writes to file', function () {
    $tempFile = sys_get_temp_dir().'/test-search-index-'.uniqid().'.json';

    $this->builder->addPage(
        title: 'Test',
        url: '/test',
        section: 'Test',
        headings: [],
        plainText: 'Content',
        description: 'Desc',
    );

    $this->builder->writeTo($tempFile);

    expect(file_exists($tempFile))->toBeTrue();

    $content = json_decode(file_get_contents($tempFile), true);
    expect($content)->toHaveCount(1);

    unlink($tempFile);
});

it('joins heading texts with commas', function () {
    $this->builder->addPage(
        title: 'Test',
        url: '/test',
        section: 'Test',
        headings: [
            ['id' => 'a', 'text' => 'Section A', 'level' => 2],
            ['id' => 'b', 'text' => 'Section B', 'level' => 2],
        ],
        plainText: 'Content',
        description: 'Desc',
    );

    $json = json_decode($this->builder->toJson(), true);

    expect($json[0]['headings'])->toBe('Section A, Section B');
});
