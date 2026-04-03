<?php

use LynkByte\DocsBuilder\OpenApiParser;

beforeEach(function () {
    $this->parser = new OpenApiParser;
    $this->specFile = __DIR__.'/../fixtures/docs/openapi.yaml';
});

it('parses the openapi yaml file', function () {
    $result = $this->parser->parse($this->specFile);

    expect($result)->toHaveKeys(['info', 'endpoints', 'tagIcons', 'serverUrl']);
});

it('extracts api info', function () {
    $result = $this->parser->parse($this->specFile);

    expect($result['info']['title'])->toBe('Test API')
        ->and($result['info']['version'])->toBe('1.0.0');
});

it('extracts server url', function () {
    $result = $this->parser->parse($this->specFile);

    expect($result['serverUrl'])->toBe('http://localhost:8000/api/v1');
});

it('groups endpoints by tags', function () {
    $result = $this->parser->parse($this->specFile);

    expect($result['endpoints'])->toHaveKeys(['Authentication', 'User'])
        ->and($result['endpoints']['Authentication'])->toHaveCount(3)
        ->and($result['endpoints']['User'])->toHaveCount(2);
});

it('extracts endpoint details', function () {
    $result = $this->parser->parse($this->specFile);
    $register = $result['endpoints']['Authentication'][0];

    expect($register['method'])->toBe('POST')
        ->and($register['path'])->toBe('/auth/register')
        ->and($register['operationId'])->toBe('registerUser')
        ->and($register['summary'])->toBe('Register a new user');
});

it('extracts request body parameters', function () {
    $result = $this->parser->parse($this->specFile);
    $register = $result['endpoints']['Authentication'][0];
    $paramNames = array_column($register['parameters'], 'name');

    expect($paramNames)->toContain('name', 'email', 'password', 'password_confirmation', 'device_name');
});

it('marks required parameters', function () {
    $result = $this->parser->parse($this->specFile);
    $register = $result['endpoints']['Authentication'][0];

    $emailParam = collect($register['parameters'])->firstWhere('name', 'email');

    expect($emailParam['required'])->toBeTrue();
});

it('splits parameters by location', function () {
    $result = $this->parser->parse($this->specFile);
    $register = $result['endpoints']['Authentication'][0];

    expect($register)->toHaveKeys(['pathParameters', 'queryParameters', 'bodyParameters'])
        ->and($register['pathParameters'])->toBeEmpty()
        ->and($register['queryParameters'])->toBeEmpty()
        ->and($register['bodyParameters'])->toHaveCount(5)
        ->and(array_column($register['bodyParameters'], 'name'))->toContain('name', 'email', 'password');
});

it('sets correct in field for body parameters', function () {
    $result = $this->parser->parse($this->specFile);
    $register = $result['endpoints']['Authentication'][0];

    foreach ($register['bodyParameters'] as $param) {
        expect($param['in'])->toBe('body');
    }
});

it('splits path, query, and body parameters correctly', function () {
    $result = $this->parser->parse($this->specFile);
    $updateUser = $result['endpoints']['User'][1];

    expect($updateUser['operationId'])->toBe('updateUser')
        ->and($updateUser['pathParameters'])->toHaveCount(1)
        ->and($updateUser['pathParameters'][0]['name'])->toBe('id')
        ->and($updateUser['pathParameters'][0]['in'])->toBe('path')
        ->and($updateUser['queryParameters'])->toHaveCount(1)
        ->and($updateUser['queryParameters'][0]['name'])->toBe('include')
        ->and($updateUser['queryParameters'][0]['in'])->toBe('query')
        ->and($updateUser['bodyParameters'])->toHaveCount(2)
        ->and(array_column($updateUser['bodyParameters'], 'name'))->toBe(['name', 'email']);
});

it('extracts response codes', function () {
    $result = $this->parser->parse($this->specFile);
    $register = $result['endpoints']['Authentication'][0];

    expect($register['responses'])->toHaveKeys(['201', '422']);
});

it('extracts security requirements', function () {
    $result = $this->parser->parse($this->specFile);
    $logout = $result['endpoints']['Authentication'][2];

    expect($logout['security'])->toContain('bearerAuth');
});

it('builds tag icons', function () {
    $result = $this->parser->parse($this->specFile);

    expect($result['tagIcons'])->toHaveKey('Authentication')
        ->and($result['tagIcons']['Authentication'])->toBe('lock')
        ->and($result['tagIcons']['User'])->toBe('person');
});

it('returns empty data for missing file', function () {
    $result = $this->parser->parse('/nonexistent/openapi.yaml');

    expect($result['endpoints'])->toBe([])
        ->and($result['info'])->toBe([]);
});
