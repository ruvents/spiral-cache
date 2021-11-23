<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use Ruvents\SpiralCache\Bootloader\CacheBootloader;
use Ruvents\SpiralCache\Config\CacheConfig;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Spiral\Boot\BootloadManager;
use Spiral\Core\Container;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @internal
 * @covers \Ruvents\SpiralCache\Container\CacheInjector
 */
final class CacheBootloaderTest extends TestCase
{
    private ?Container $container = null;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->bindSingleton(
            CacheConfig::class,
            new CacheConfig([
                'pools' => [
                    'default' => new NullAdapter(),
                ],
            ])
        );
        (new BootloadManager($this->container))->bootload([CacheBootloader::class]);
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
