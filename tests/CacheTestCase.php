<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Boot\DirectoriesInterface;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Boot\FinalizerInterface;
use Spiral\Boot\KernelInterface;
use Spiral\Core\Container;

abstract class CacheTestCase extends TestCase
{
    protected ?Container $container = null;

    protected function setUp(): void
    {
        $this->container = new Container();
        foreach ([
            DirectoriesInterface::class, KernelInterface::class,
            EnvironmentInterface::class, FinalizerInterface::class,
        ] as $mockedClass) {
            $this->container->bindSingleton($mockedClass, $this->createMock($mockedClass));
        }
    }
}
