<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Exception;

final class PoolDoesNotExistException extends \RuntimeException
{
    public function __construct(string $name)
    {
        $this->message = sprintf('Cache pool "%s" does not exist.', $name);
    }
}
