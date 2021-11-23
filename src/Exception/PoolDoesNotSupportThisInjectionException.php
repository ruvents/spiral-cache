<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Exception;

final class PoolDoesNotSupportThisInjectionException extends \RuntimeException
{
    public function __construct(string $pool, string $desiredClass)
    {
        $this->message = sprintf(
            'Pool "%s" does not support injection as "%s".',
            $pool,
            $desiredClass
        );
    }
}
