<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use PHPUnit\Framework\TestCase;
use Ruvents\SpiralCache\Config\CacheConfig;
use Ruvents\SpiralCache\Exception\NoDefaultPoolException;
use Ruvents\SpiralCache\Exception\PoolDoesNotExistException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @internal
 * @covers \Ruvents\SpiralCache\Config\CacheConfig
 */
final class CacheConfigTest extends TestCase
{
    public function testGetDefaultPool(): void
    {
        $config = new CacheConfig([
            'pools' => [
                'default' => new NullAdapter(),
            ],
        ]);

        $this->assertInstanceOf(NullAdapter::class, $config->getDefaultPool());
    }

    public function testGetPool(): void
    {
        $config = new CacheConfig([
            'pools' => [
                'default' => new NullAdapter(),
                'foobar' => new ArrayAdapter(),
            ],
        ]);

        $this->assertInstanceOf(NullAdapter::class, $config->getPool('default'));
        $this->assertInstanceOf(ArrayAdapter::class, $config->getPool('foobar'));
    }

    public function testHasPool(): void
    {
        $config = new CacheConfig([
            'pools' => [
                'default' => new NullAdapter(),
            ],
        ]);

        $this->assertTrue($config->hasPool('default'));
        $this->assertFalse($config->hasPool('foobar'));
    }

    public function testGetPools(): void
    {
        $config = new CacheConfig([
            'pools' => [
                'default' => new NullAdapter(),
                'foobar' => new ArrayAdapter(),
            ],
        ]);

        $pools = $config->getPools();
        $this->assertSame(['default', 'foobar'], array_keys($pools));
        $this->assertInstanceOf(NullAdapter::class, $pools['default']);
        $this->assertInstanceOf(ArrayAdapter::class, $pools['foobar']);
    }

    public function testGetDefaultWithCustomName(): void
    {
        $config = new CacheConfig([
            'default' => 'custom-name',
            'pools' => [
                'custom-name' => new NullAdapter(),
            ],
        ]);

        $this->assertInstanceOf(NullAdapter::class, $config->getDefaultPool());
    }

    public function testGetControllerPool(): void
    {
        $config = new CacheConfig([
            'pools' => [
                'default' => new NullAdapter(),
                'secondary' => new ArrayAdapter(),
            ],
            'controllerPool' => 'secondary',
        ]);

        $this->assertInstanceOf(ArrayAdapter::class, $config->getControllerPool());
    }

    public function testExceptionForInvalidPool(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $config = new CacheConfig([
            'pools' => [
                'default' => 'foobar',
            ],
        ]);

        $config->getDefaultPool();
    }

    public function testExceptionForNotExistingPool(): void
    {
        $this->expectException(PoolDoesNotExistException::class);

        $config = new CacheConfig([
            'pools' => [
                'default' => new NullAdapter(),
            ],
        ]);

        $config->getPool('foobar');
    }

    public function testExceptionForNotExistingDefaultPool(): void
    {
        $this->expectException(NoDefaultPoolException::class);

        $config = new CacheConfig([
            'default' => 'foobar',
            'pools' => [
                'default' => new NullAdapter(),
            ],
        ]);

        $config->getDefaultPool();
    }

    public function testExceptionForEmptyPools(): void
    {
        $this->expectException(NoDefaultPoolException::class);

        $config = new CacheConfig([]);

        $config->getDefaultPool();
    }
}
