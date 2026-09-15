<?php

namespace App\Services\YandexMaps\DTO;

final class ParsedReview
{
    public function __construct(
        public readonly string $externalId,
        public readonly ?string $author,
        public readonly ?string $authorAvatarUrl,
        public readonly int $rating,
        public readonly ?string $text,
        public readonly ?\DateTimeImmutable $publishedAt,
    ) {}

    public function contentHash(): string
    {
        return hash('sha256', implode('|', [
            $this->author, $this->text, $this->rating,
            $this->publishedAt?->format('c'),
        ]));
    }
}
