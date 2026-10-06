<?php

namespace App\Support\Proofmark;

/**
 * A freshly encoded image, ready to store.
 */
final readonly class ProcessedImage
{
    public function __construct(
        public string $contents,
        public string $mime,
        public string $extension,
        public int $width,
        public int $height,
    ) {}

    public function bytes(): int
    {
        return strlen($this->contents);
    }
}
