<?php

namespace LynkByte\DocsBuilder\Contracts;

interface MarkdownParserInterface
{
    /**
     * Parse a markdown file and return structured data.
     *
     * @return array{html: string, headings: array<int, array{id: string, text: string, level: int}>, description: string, plainText: string}
     */
    public function parse(string $filePath): array;

    /**
     * Parse raw markdown string (not from a file).
     *
     * @return array{html: string, headings: array<int, array{id: string, text: string, level: int}>, description: string, plainText: string}
     */
    public function parseString(string $markdown): array;
}
