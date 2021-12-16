<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests\Fixtures;

use Ruvents\SpiralCache\Annotation\Cached;

final class TestController
{
    #[Cached('+3 hours')]
    public function cached(string $response): string
    {
        return $response;
    }

    #[Cached('+3 hours', key: 'test-key')]
    public function cachedWithStringKey(string $response): string
    {
        return $response;
    }

    #[Cached('+3 hours', key: [TestKeyGenerator::class, 'echo'])]
    public function cachedWithCallableArrayKey(string $response): string
    {
        return $response;
    }

    #[Cached('+3 hours', tags: ['foo'])]
    public function cachedWithTags(string $response): string
    {
        return $response;
    }

    #[Cached('+3 hours', if: [TestChecker::class, 'context'])]
    public function cachedWithCondition(string $response): string
    {
        return $response;
    }

    #[Cached('+1 hours', if: [TestChecker::class, 'false'])]
    #[Cached('+3 hours', if: [TestChecker::class, 'context'])]
    public function cachedWithSeveralConditions(string $response): string
    {
        return $response;
    }
}
