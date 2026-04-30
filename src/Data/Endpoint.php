<?php

namespace LynkByte\DocsBuilder\Data;

/**
 * Represents an API endpoint parsed from an OpenAPI specification.
 *
 * Implements \ArrayAccess for backward compatibility with code that
 * accesses endpoint data using array syntax (e.g. $endpoint['method']).
 *
 * @implements \ArrayAccess<string, mixed>
 */
final class Endpoint implements \ArrayAccess
{
    /**
     * @param  string  $path  The URL path (e.g. '/users/{id}').
     * @param  string  $method  The HTTP method (e.g. 'GET', 'POST').
     * @param  string  $operationId  Unique operation identifier.
     * @param  string  $summary  Short summary of the endpoint.
     * @param  string  $description  Detailed description.
     * @param  array<int, array<string, mixed>>  $parameters  All parameters.
     * @param  array<int, array<string, mixed>>  $pathParameters  Path parameters only.
     * @param  array<int, array<string, mixed>>  $queryParameters  Query parameters only.
     * @param  array<int, array<string, mixed>>  $bodyParameters  Body parameters only.
     * @param  array<string, array<string, mixed>>  $responses  Response definitions keyed by status code.
     * @param  array<int, string>  $security  Security scheme names.
     * @param  string  $url  The generated documentation URL for this endpoint.
     */
    public function __construct(
        public readonly string $path,
        public readonly string $method,
        public readonly string $operationId,
        public readonly string $summary = '',
        public readonly string $description = '',
        public readonly array $parameters = [],
        public readonly array $pathParameters = [],
        public readonly array $queryParameters = [],
        public readonly array $bodyParameters = [],
        public readonly array $responses = [],
        public readonly array $security = [],
        public readonly string $url = '',
    ) {}

    /**
     * Create a new instance with a different URL.
     */
    public function withUrl(string $url): self
    {
        return new self(
            path: $this->path,
            method: $this->method,
            operationId: $this->operationId,
            summary: $this->summary,
            description: $this->description,
            parameters: $this->parameters,
            pathParameters: $this->pathParameters,
            queryParameters: $this->queryParameters,
            bodyParameters: $this->bodyParameters,
            responses: $this->responses,
            security: $this->security,
            url: $url,
        );
    }

    /**
     * Convert the endpoint to an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'method' => $this->method,
            'operationId' => $this->operationId,
            'summary' => $this->summary,
            'description' => $this->description,
            'parameters' => $this->parameters,
            'pathParameters' => $this->pathParameters,
            'queryParameters' => $this->queryParameters,
            'bodyParameters' => $this->bodyParameters,
            'responses' => $this->responses,
            'security' => $this->security,
            'url' => $this->url,
        ];
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return property_exists($this, $offset) ? $this->{$offset} : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Endpoint is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Endpoint is immutable.');
    }
}
