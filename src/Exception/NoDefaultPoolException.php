<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Exception;

final class NoDefaultPoolException extends \RuntimeException
{
    public function __construct(string $defaultPool)
    {
        $this->message = sprintf(
            'Default "%s" pool must be specified in "cache" configuration.',
            $defaultPool
        );
    }
}
