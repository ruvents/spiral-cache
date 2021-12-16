<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use PHPUnit\Framework\TestCase;
use Ruvents\SpiralCache\Bootloader\CacheBootloader;
use Spiral\Boot\BootloadManager;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Ruvents\SpiralCache\Config\CacheConfig;
use Spiral\Boot\Bootloader\CoreBootloader;
use Spiral\Boot\DirectoriesInterface;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Boot\FinalizerInterface;
use Spiral\Boot\KernelInterface;
use Spiral\Config\ConfigManager;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Core\Container;

abstract class CacheTestCase extends TestCase
{
    protected ?Container $container = null;

    protected function setUp(): void
    {
        $this->container = new Container();
        foreach ([
            DirectoriesInterface::class, KernelInterface::class,
            EnvironmentInterface::class, FinalizerInterface::class
        ] as $mockedClass) {
            $this->container->bindSingleton($mockedClass, $this->createMock($mockedClass));
        }
    }
}
