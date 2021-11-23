<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests\Fixtures;

final class TestChecker
{
    public function false(array $context): bool
    {
        return false;
    }

    public function true(array $context): bool
    {
        return true;
    }

    public function context(array $context): bool
    {
        return $context['parameters']['pass'];
    }

    public function length(string $word, int $len, array $context): bool
    {
        return mb_strlen($word) === $len;
    }
}
