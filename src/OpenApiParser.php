<?php

namespace LynkByte\DocsBuilder;

use LynkByte\DocsBuilder\Data\Endpoint;
use Symfony\Component\Yaml\Yaml;

/**
 * @phpstan-type ParameterData array{name: string, in: string, type: string, required: bool, description: string, example: string}
 * @phpstan-type ResponseData array{description: string, example: ?string}
 * @phpstan-type ApiData array{info: array<string, mixed>, endpoints: array<string, array<int, Endpoint>>, tagIcons: array<string, string>, serverUrl: string}
 */
class OpenApiParser implements Contracts\OpenApiParserInterface
{
    /** @var array<string, mixed> */
    private array $spec = [];

    /** @var array<string, string> */
    private array $tagIconOverrides;

    /**
     * @param  array<string, string>  $tagIconOverrides  Custom tag-to-icon mappings that override defaults.
     */
    public function __construct(array $tagIconOverrides = [])
    {
        $this->tagIconOverrides = $tagIconOverrides;
    }

    /**
     * Parse an OpenAPI YAML file and return structured endpoint data.
     *
     * @return ApiData
     */
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [
                'info' => [],
                'endpoints' => [],
                'tagIcons' => [],
                'serverUrl' => '',
            ];
        }

        $this->spec = Yaml::parseFile($filePath);

        return [
            'info' => $this->extractInfo(),
            'endpoints' => $this->extractEndpoints(),
            'tagIcons' => $this->buildTagIcons(),
            'serverUrl' => $this->extractServerUrl(),
        ];
    }

    /**
     * Extract API info (title, description, version).
     *
     * @return array<string, mixed>
     */
    private function extractInfo(): array
    {
        $info = $this->spec['info'] ?? [];

        return [
            'title' => $info['title'] ?? 'API Reference',
            'description' => $info['description'] ?? '',
            'version' => $info['version'] ?? '1.0.0',
        ];
    }

    /**
     * Extract the primary server URL.
     */
    private function extractServerUrl(): string
    {
        $servers = $this->spec['servers'] ?? [];

        return $servers[0]['url'] ?? 'http://localhost:8000/api/v1';
    }

    /**
     * Extract all endpoints grouped by tag.
     *
     * @return array<string, array<int, Endpoint>>
     */
    private function extractEndpoints(): array
    {
        $paths = $this->spec['paths'] ?? [];
        $grouped = [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (! is_array($operation)) {
                    continue;
                }

                $tags = $operation['tags'] ?? ['General'];
                $endpoint = $this->buildEndpoint($path, $method, $operation);

                foreach ($tags as $tag) {
                    $grouped[$tag] ??= [];
                    $grouped[$tag][] = $endpoint;
                }
            }
        }

        return $grouped;
    }

    /**
     * Build a structured endpoint from an OpenAPI operation.
     *
     * @param  array<string, mixed>  $operation
     */
    private function buildEndpoint(string $path, string $method, array $operation): Endpoint
    {
        $allParameters = $this->extractParameters($operation);

        return new Endpoint(
            path: $path,
            method: strtoupper($method),
            operationId: $operation['operationId'] ?? $this->generateOperationId($path, $method),
            summary: $operation['summary'] ?? '',
            description: $operation['description'] ?? '',
            parameters: $allParameters,
            pathParameters: array_values(array_filter($allParameters, fn (array $p) => ($p['in'] ?? '') === 'path')),
            queryParameters: array_values(array_filter($allParameters, fn (array $p) => ($p['in'] ?? '') === 'query')),
            bodyParameters: array_values(array_filter($allParameters, fn (array $p) => ($p['in'] ?? '') === 'body')),
            responses: $this->extractResponses($operation),
            security: $this->extractSecurity($operation),
        );
    }

    /**
     * Generate a fallback operation ID from path and method.
     */
    private function generateOperationId(string $path, string $method): string
    {
        $slug = trim($path, '/');
        $slug = str_replace(['/', '{', '}'], ['-', '', ''], $slug);

        return $method.'-'.$slug;
    }

    /**
     * Extract request parameters (from both path params and request body).
     *
     * @param  array<string, mixed>  $operation
     * @return array<int, ParameterData>
     */
    private function extractParameters(array $operation): array
    {
        $params = [];

        // Path/query parameters
        foreach ($operation['parameters'] ?? [] as $param) {
            $params[] = [
                'name' => $param['name'] ?? '',
                'in' => $param['in'] ?? 'query',
                'type' => $param['schema']['type'] ?? 'string',
                'required' => $param['required'] ?? false,
                'description' => $param['description'] ?? '',
                'example' => $param['example'] ?? $param['schema']['example'] ?? '',
            ];
        }

        // Request body properties
        $requestBody = $operation['requestBody'] ?? null;
        if ($requestBody) {
            $content = $requestBody['content']['application/json'] ?? [];
            $schema = $content['schema'] ?? [];

            // Resolve $ref if present
            $schema = $this->resolveRef($schema);

            $required = $schema['required'] ?? [];
            $properties = $schema['properties'] ?? [];
            $example = $content['example'] ?? [];

            foreach ($properties as $name => $property) {
                $property = $this->resolveRef($property);
                $params[] = [
                    'name' => $name,
                    'in' => 'body',
                    'type' => $property['type'] ?? 'string',
                    'required' => in_array($name, $required),
                    'description' => $property['description'] ?? '',
                    'example' => (string) ($example[$name] ?? $property['example'] ?? ''),
                ];
            }
        }

        return $params;
    }

    /**
     * Extract response definitions.
     *
     * @param  array<string, mixed>  $operation
     * @return array<string, ResponseData>
     */
    private function extractResponses(array $operation): array
    {
        $responses = [];

        foreach ($operation['responses'] ?? [] as $code => $response) {
            $response = $this->resolveRef($response);
            $responses[(string) $code] = [
                'description' => $response['description'] ?? '',
                'example' => $this->extractResponseExample($response),
            ];
        }

        return $responses;
    }

    /**
     * Extract an example from a response definition.
     *
     * @param  array<string, mixed>  $response
     */
    private function extractResponseExample(array $response): ?string
    {
        $content = $response['content']['application/json'] ?? null;
        if (! $content) {
            return null;
        }

        $example = $content['example'] ?? null;
        if ($example) {
            return json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return null;
    }

    /**
     * Extract security requirements for an endpoint.
     *
     * @param  array<string, mixed>  $operation
     * @return array<int, string>
     */
    private function extractSecurity(array $operation): array
    {
        $security = $operation['security'] ?? [];
        $schemes = [];

        foreach ($security as $requirement) {
            foreach (array_keys($requirement) as $scheme) {
                $schemes[] = $scheme;
            }
        }

        return $schemes;
    }

    /**
     * Resolve a $ref reference to the actual schema definition.
     *
     * Follows nested `$ref` chains and recursively resolves references found
     * within `properties`, `items`, `allOf`, `oneOf`, `anyOf`, and
     * `additionalProperties`. A visited-set guard prevents infinite loops
     * caused by circular references.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, bool>  $visited  Tracks already-visited `$ref` paths to break circular chains.
     * @return array<string, mixed>
     */
    private function resolveRef(array $schema, array $visited = []): array
    {
        if (isset($schema['$ref'])) {
            $ref = $schema['$ref'];

            // Circular reference guard
            if (isset($visited[$ref])) {
                return [];
            }

            $visited[$ref] = true;

            // Parse #/components/schemas/RegisterRequest format
            $parts = explode('/', ltrim($ref, '#/'));

            $resolved = $this->spec;
            foreach ($parts as $part) {
                $resolved = $resolved[$part] ?? [];
            }

            $schema = is_array($resolved) ? $resolved : [];
        }

        return $this->resolveRefsRecursively($schema, $visited);
    }

    /**
     * Walk a schema tree and recursively resolve any nested `$ref` references.
     *
     * Handles `properties`, `items`, `allOf`/`oneOf`/`anyOf` compositions,
     * and `additionalProperties`.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, bool>  $visited  Tracks already-visited `$ref` paths to break circular chains.
     * @return array<string, mixed>
     */
    private function resolveRefsRecursively(array $schema, array $visited = []): array
    {
        // Resolve nested $ref at the current level (chained refs)
        if (isset($schema['$ref'])) {
            return $this->resolveRef($schema, $visited);
        }

        // properties
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $name => $property) {
                if (is_array($property)) {
                    $schema['properties'][$name] = $this->resolveRef($property, $visited);
                }
            }
        }

        // items (array schemas)
        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->resolveRef($schema['items'], $visited);
        }

        // allOf / oneOf / anyOf
        foreach (['allOf', 'oneOf', 'anyOf'] as $keyword) {
            if (isset($schema[$keyword]) && is_array($schema[$keyword])) {
                foreach ($schema[$keyword] as $i => $subSchema) {
                    if (is_array($subSchema)) {
                        $schema[$keyword][$i] = $this->resolveRef($subSchema, $visited);
                    }
                }
            }
        }

        // additionalProperties
        if (isset($schema['additionalProperties']) && is_array($schema['additionalProperties'])) {
            $schema['additionalProperties'] = $this->resolveRef($schema['additionalProperties'], $visited);
        }

        return $schema;
    }

    /**
     * Build tag-to-icon mapping for sidebar display.
     * Reads from config, with built-in defaults as fallback.
     *
     * @return array<string, string>
     */
    private function buildTagIcons(): array
    {
        $configIcons = $this->tagIconOverrides;

        $defaultIcons = [
            'Authentication' => 'lock',
            'User' => 'person',
            'Users' => 'group',
            'Booking' => 'calendar_month',
            'Bookings' => 'calendar_month',
            'Portfolio' => 'photo_library',
            'Admin' => 'admin_panel_settings',
            'General' => 'api',
        ];

        $iconMap = array_merge($defaultIcons, $configIcons);

        $tags = $this->spec['tags'] ?? [];
        $result = [];

        foreach ($tags as $tag) {
            $name = $tag['name'] ?? '';
            $result[$name] = $iconMap[$name] ?? 'api';
        }

        return $result;
    }
}
