<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache;

use Spiral\Http\Request\InputManager;

final class KeyGenerator
{
    public function __construct(private InputManager $inputManager)
    {
    }

    public function request(): string
    {
        return sprintf(
            '%s:%s',
            $this->inputManager->method(),
            (string) $this->inputManager->uri()
        );
    }
}
