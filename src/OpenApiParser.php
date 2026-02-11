<?php

namespace LynkByte\DocsBuilder;

use Symfony\Component\Yaml\Yaml;

class OpenApiParser
{
    /** @var array<string, mixed> */
    private array $spec = [];

    /**
     * Parse an OpenAPI YAML file and return structured endpoint data.
     *
     * @return array{info: array<string, mixed>, endpoints: array<string, array<int, array<string, mixed>>>, tagIcons: array<string, string>, serverUrl: string}
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
     * @return array<string, array<int, array<string, mixed>>>
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
     * @return array<string, mixed>
     */
    private function buildEndpoint(string $path, string $method, array $operation): array
    {
        return [
            'path' => $path,
            'method' => strtoupper($method),
            'operationId' => $operation['operationId'] ?? $this->generateOperationId($path, $method),
            'summary' => $operation['summary'] ?? '',
            'description' => $operation['description'] ?? '',
            'parameters' => $this->extractParameters($operation),
            'responses' => $this->extractResponses($operation),
            'security' => $this->extractSecurity($operation),
            'url' => '', // Will be set by DocsBuilder
        ];
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
     * @return array<int, array<string, mixed>>
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
     * @return array<string, array<string, mixed>>
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
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function resolveRef(array $schema): array
    {
        if (! isset($schema['$ref'])) {
            return $schema;
        }

        $ref = $schema['$ref'];
        // Parse #/components/schemas/RegisterRequest format
        $parts = explode('/', ltrim($ref, '#/'));

        $resolved = $this->spec;
        foreach ($parts as $part) {
            $resolved = $resolved[$part] ?? [];
        }

        return is_array($resolved) ? $resolved : [];
    }

    /**
     * Build tag-to-icon mapping for sidebar display.
     * Reads from config, with built-in defaults as fallback.
     *
     * @return array<string, string>
     */
    private function buildTagIcons(): array
    {
        $configIcons = config('docs-builder.api_tag_icons', []);

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
