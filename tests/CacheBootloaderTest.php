<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Ruvents\SpiralCache\Bootloader\CacheBootloader;
use Ruvents\SpiralCache\Config\CacheConfig;
use Spiral\Boot\Bootloader\CoreBootloader;
use Spiral\Boot\BootloadManager;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @internal
 * @covers \Ruvents\SpiralCache\Container\CacheInjector
 */
final class CacheBootloaderTest extends CacheTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->container->bindSingleton(
            CacheConfig::class,
            new CacheConfig([
                'pools' => [
                    'default' => new NullAdapter(),
                ],
            ])
        );
        (new BootloadManager($this->container))->bootload([CoreBootloader::class, CacheBootloader::class]);
    }

    public function testGetCacheItemPoolInterface(): void
    {
        $this->assertInstanceOf(NullAdapter::class, $this->container->get(CacheItemPoolInterface::class));
    }

    public function testGetCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->container->get(CacheInterface::class));
    }
}
