<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ResponseNormalizer
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory
    ) {
    }

    public function normalize(ResponseInterface $response): CachedResponse
    {
        return new CachedResponse(
            $response->getStatusCode(),
            $response->getHeaders(),
            (string) $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    public function denormalize(CachedResponse $response): ResponseInterface
    {
        $result = $this->responseFactory
            ->createResponse()
            ->withProtocolVersion($response->getProtocolVersion())
        ;

        if (null !== $response->getReasonPhrase()) {
            $result = $result->withStatus($response->getStatusCode(), $response->getReasonPhrase());
        }

        if (null !== $response->getBody()) {
            $result = $result->withBody($this->streamFactory->createStream($response->getBody()));
        }

        foreach ($response->getHeaders() as $headerName => $headerValue) {
            $result = $result->withHeader($headerName, $headerValue);
        }

        return $result;
    }
}
