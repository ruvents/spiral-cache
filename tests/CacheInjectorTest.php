<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Ruvents\SpiralCache\CacheBootloader;
use Ruvents\SpiralCache\CacheConfig;
use Ruvents\SpiralCache\CacheInjector;
use Ruvents\SpiralCache\Exception\PoolDoesNotExistException;
use Ruvents\SpiralCache\Exception\PoolDoesNotSupportThisInjectionException;
use Spiral\Boot\Bootloader\CoreBootloader;
use Spiral\Boot\BootloadManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @internal
 * @covers \Ruvents\SpiralCache\Container\CacheInjector
 */
final class CacheInjectorTest extends CacheTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->container->bindSingleton(
            CacheConfig::class,
            new CacheConfig([
                'pools' => [
                    'default' => new NullAdapter(),
                    'array' => new ArrayAdapter(),
                ],
            ])
        );
        (new BootloadManager($this->container))->bootload([CoreBootloader::class, CacheBootloader::class]);
    }

    public function testInjection(): void
    {
        /** @var CacheInjector */
        $injector = $this->container->get(CacheInjector::class);

        $cache = $injector->createInjection(new \ReflectionClass(CacheItemPoolInterface::class), 'default');
        $this->assertInstanceOf(NullAdapter::class, $cache);

        $cache = $injector->createInjection(new \ReflectionClass(CacheItemPoolInterface::class), 'array');
        $this->assertInstanceOf(ArrayAdapter::class, $cache);
    }

    public function testInjectionUpgrade(): void
    {
        /** @var CacheInjector */
        $injector = $this->container->get(CacheInjector::class);

        $cache = $injector->createInjection(new \ReflectionClass(CacheInterface::class), 'default');
        $this->assertInstanceOf(CacheInterface::class, $cache);
        $this->assertInstanceOf(Psr16Cache::class, $cache);
    }

    public function testExceptionOnUnsupportedInjection(): void
    {
        $this->expectException(PoolDoesNotSupportThisInjectionException::class);

        /** @var CacheInjector */
        $injector = $this->container->get(CacheInjector::class);
        $injector->createInjection(new \ReflectionClass(TagAwareCacheInterface::class), 'default');
    }

    public function testInjectionWithOnePool(): void
    {
        $this->container->bindSingleton(
            CacheConfig::class,
            new CacheConfig([
                'pools' => [
                    'default' => new NullAdapter(),
                ],
            ])
        );

        /** @var CacheInjector */
        $injector = $this->container->get(CacheInjector::class);

        $cache = $injector->createInjection(new \ReflectionClass(CacheItemPoolInterface::class), 'default');
        $this->assertInstanceOf(NullAdapter::class, $cache);

        $cache = $injector->createInjection(new \ReflectionClass(CacheItemPoolInterface::class), 'any-name');
        $this->assertInstanceOf(NullAdapter::class, $cache);
    }

    public function testExceptionOnNonExistingPool(): void
    {
        $this->expectException(PoolDoesNotExistException::class);

        /** @var CacheInjector */
        $injector = $this->container->get(CacheInjector::class);

        $cache = $injector->createInjection(new \ReflectionClass(CacheItemPoolInterface::class), 'does-not-exist');
    }

    public function testInjectionWithoutContext(): void
    {
        /** @var CacheInjector */
        $injector = $this->container->get(CacheInjector::class);
        $cache = $injector->createInjection(new \ReflectionClass(CacheItemPoolInterface::class));

        $this->assertInstanceOf(NullAdapter::class, $cache);
    }
}
