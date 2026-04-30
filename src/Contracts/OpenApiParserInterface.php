<?php

namespace LynkByte\DocsBuilder\Contracts;

interface OpenApiParserInterface
{
    /**
     * Parse an OpenAPI YAML file and return structured endpoint data.
     *
     * @return array{info: array<string, mixed>, endpoints: array<string, array<int, \LynkByte\DocsBuilder\Data\Endpoint>>, tagIcons: array<string, string>, serverUrl: string}
     */
    public function parse(string $filePath): array;
}
