<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Response;

final class CachedResponse
{
    public function __construct(
        private int $statusCode,
        private array $headers,
        private ?string $body,
        private string $protocolVersion,
        private ?string $reasonPhrase
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function getReasonPhrase(): ?string
    {
        return $this->reasonPhrase;
    }
}
