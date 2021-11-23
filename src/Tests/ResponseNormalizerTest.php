<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use Ruvents\SpiralCache\Response\CachedResponse;
use Ruvents\SpiralCache\Response\ResponseNormalizer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \Ruvents\SpiralCache\Response\ResponseNormalizer
 */
final class ResponseNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $response = $this->getResponseNormalizer()->normalize(
            new Response(
                200,
                [
                    'Authentication' => 'Bearer foobar',
                    'X-Forward-For' => '127.0.0.1',
                ],
                'Test body',
                '1.0',
                'ok'
            )
        );

        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'Authentication' => ['Bearer foobar'],
            'X-Forward-For' => ['127.0.0.1'],
        ], $response->getHeaders());
        $this->assertSame('Test body', (string) $response->getBody());
        $this->assertSame('ok', $response->getReasonPhrase());
    }

    public function testDenormalize(): void
    {
        $response = $this->getResponseNormalizer()->denormalize(
            new CachedResponse(
                200,
                [
                    'Authentication' => ['Bearer foobar'],
                    'X-Forward-For' => ['127.0.0.1'],
                ],
                'Test body',
                '1.0',
                'ok'
            )
        );

        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'Authentication' => ['Bearer foobar'],
            'X-Forward-For' => ['127.0.0.1'],
        ], $response->getHeaders());
        $this->assertSame('Test body', (string) $response->getBody());
        $this->assertSame('ok', $response->getReasonPhrase());
    }

    private function getResponseNormalizer(): ResponseNormalizer
    {
        return new ResponseNormalizer(new Psr17Factory(), new Psr17Factory());
    }
}
