<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use Ruvents\SpiralCache\Bootloader\CacheBootloader;
use Ruvents\SpiralCache\Config\CacheConfig;
use Ruvents\SpiralCache\Container\CacheInjector;
use Ruvents\SpiralCache\Exception\PoolDoesNotExistException;
use Ruvents\SpiralCache\Exception\PoolDoesNotSupportThisInjectionException;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Spiral\Boot\BootloadManager;
use Spiral\Core\Container;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @internal
 * @covers \Ruvents\SpiralCache\Container\CacheInjector
 */
final class CacheInjectorTest extends TestCase
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
                    'array' => new ArrayAdapter(),
                ],
            ])
        );
        (new BootloadManager($this->container))->bootload([CacheBootloader::class]);
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
