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

it('resolves nested ref chains', function () {
    // Schema A -> Schema B -> Schema C (two levels of $ref)
    $specFile = tempnam(sys_get_temp_dir(), 'openapi_nested_').'.yaml';
    file_put_contents($specFile, <<<'YAML'
openapi: '3.0.3'
info:
  title: Nested Ref Test
  version: '1.0.0'
servers:
  - url: http://localhost
paths:
  /test:
    post:
      tags:
        - Test
      operationId: nestedRefTest
      summary: Nested ref test
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/SchemaA'
      responses:
        '200':
          description: OK
components:
  schemas:
    SchemaA:
      type: object
      properties:
        child:
          $ref: '#/components/schemas/SchemaB'
    SchemaB:
      type: object
      properties:
        grandchild:
          $ref: '#/components/schemas/SchemaC'
    SchemaC:
      type: object
      properties:
        value:
          type: string
          description: The final value
YAML);

    $result = $this->parser->parse($specFile);
    unlink($specFile);

    $endpoint = $result['endpoints']['Test'][0];
    $bodyParams = $endpoint['bodyParameters'];
    $childParam = collect($bodyParams)->firstWhere('name', 'child');

    // The top-level schema (SchemaA) should be resolved, exposing 'child' as a body param
    expect($childParam)->not->toBeNull()
        ->and($childParam['name'])->toBe('child');
});

it('handles circular refs without infinite loop', function () {
    $specFile = tempnam(sys_get_temp_dir(), 'openapi_circular_').'.yaml';
    file_put_contents($specFile, <<<'YAML'
openapi: '3.0.3'
info:
  title: Circular Ref Test
  version: '1.0.0'
servers:
  - url: http://localhost
paths:
  /test:
    post:
      tags:
        - Test
      operationId: circularRefTest
      summary: Circular ref test
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/NodeA'
      responses:
        '200':
          description: OK
components:
  schemas:
    NodeA:
      type: object
      properties:
        sibling:
          $ref: '#/components/schemas/NodeB'
    NodeB:
      type: object
      properties:
        back:
          $ref: '#/components/schemas/NodeA'
YAML);

    $result = $this->parser->parse($specFile);
    unlink($specFile);

    $endpoint = $result['endpoints']['Test'][0];
    $bodyParams = $endpoint['bodyParameters'];

    // Should complete without hanging and produce a valid result
    expect($bodyParams)->toBeArray()
        ->and(collect($bodyParams)->firstWhere('name', 'sibling'))->not->toBeNull();
});

it('resolves refs inside allOf oneOf anyOf compositions', function () {
    $specFile = tempnam(sys_get_temp_dir(), 'openapi_composition_').'.yaml';
    file_put_contents($specFile, <<<'YAML'
openapi: '3.0.3'
info:
  title: Composition Ref Test
  version: '1.0.0'
servers:
  - url: http://localhost
paths:
  /test:
    post:
      tags:
        - Test
      operationId: compositionRefTest
      summary: Composition ref test
      requestBody:
        required: true
        content:
          application/json:
            schema:
              allOf:
                - $ref: '#/components/schemas/BasePerson'
                - type: object
                  properties:
                    role:
                      type: string
      responses:
        '200':
          description: OK
          content:
            application/json:
              example:
                name: Jane
                role: admin
components:
  schemas:
    BasePerson:
      type: object
      required:
        - name
      properties:
        name:
          type: string
          description: Person name
        address:
          $ref: '#/components/schemas/Address'
    Address:
      type: object
      properties:
        city:
          type: string
          description: City name
YAML);

    $result = $this->parser->parse($specFile);
    unlink($specFile);

    $endpoint = $result['endpoints']['Test'][0];

    // The response should parse without error
    expect($endpoint['responses'])->toHaveKey('200');
});

it('resolves refs in response schemas', function () {
    $specFile = tempnam(sys_get_temp_dir(), 'openapi_resp_ref_').'.yaml';
    file_put_contents($specFile, <<<'YAML'
openapi: '3.0.3'
info:
  title: Response Ref Test
  version: '1.0.0'
servers:
  - url: http://localhost
paths:
  /test:
    get:
      tags:
        - Test
      operationId: responseRefTest
      summary: Response ref test
      responses:
        '200':
          $ref: '#/components/responses/SuccessResponse'
        '404':
          $ref: '#/components/responses/NotFoundResponse'
components:
  responses:
    SuccessResponse:
      description: Operation successful
      content:
        application/json:
          example:
            status: ok
    NotFoundResponse:
      description: Resource not found
YAML);

    $result = $this->parser->parse($specFile);
    unlink($specFile);

    $endpoint = $result['endpoints']['Test'][0];

    expect($endpoint['responses']['200']['description'])->toBe('Operation successful')
        ->and($endpoint['responses']['404']['description'])->toBe('Resource not found');
});

it('resolves refs in array items', function () {
    $specFile = tempnam(sys_get_temp_dir(), 'openapi_items_ref_').'.yaml';
    file_put_contents($specFile, <<<'YAML'
openapi: '3.0.3'
info:
  title: Items Ref Test
  version: '1.0.0'
servers:
  - url: http://localhost
paths:
  /test:
    post:
      tags:
        - Test
      operationId: itemsRefTest
      summary: Items ref test
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                tags:
                  type: array
                  items:
                    $ref: '#/components/schemas/Tag'
      responses:
        '200':
          description: OK
components:
  schemas:
    Tag:
      type: object
      properties:
        label:
          type: string
YAML);

    $result = $this->parser->parse($specFile);
    unlink($specFile);

    $endpoint = $result['endpoints']['Test'][0];
    $tagsParam = collect($endpoint['bodyParameters'])->firstWhere('name', 'tags');

    expect($tagsParam)->not->toBeNull()
        ->and($tagsParam['type'])->toBe('array');
});

it('applies custom tag icon overrides from constructor', function () {
    $parser = new OpenApiParser([
        'Authentication' => 'shield',
        'User' => 'account_circle',
        'CustomTag' => 'star',
    ]);

    $result = $parser->parse($this->specFile);

    expect($result['tagIcons']['Authentication'])->toBe('shield')
        ->and($result['tagIcons']['User'])->toBe('account_circle');
});
