<?php

namespace LynkByte\DocsBuilder\Contracts;

use LynkByte\DocsBuilder\Data\Endpoint;

interface OpenApiParserInterface
{
    /**
     * Parse an OpenAPI YAML file and return structured endpoint data.
     *
     * @return array{info: array<string, mixed>, endpoints: array<string, array<int, Endpoint>>, tagIcons: array<string, string>, serverUrl: string}
     */
    public function parse(string $filePath): array;
}
