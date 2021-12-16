<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests\Fixtures;

final class TestKeyGenerator
{
    public function echo(array $context): string
    {
        return $context['parameters']['echo'] ?? 'echo';
    }
}
