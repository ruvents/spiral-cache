<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Config;

use Ruvents\SpiralCache\Exception\NoDefaultPoolException;
use Ruvents\SpiralCache\Exception\PoolDoesNotExistException;
use Psr\Cache\CacheItemPoolInterface;
use Spiral\Core\InjectableConfig;
use Webmozart\Assert\Assert;

final class CacheConfig extends InjectableConfig
{
    public const CONFIG = 'cache';
    public const DEFAULT_POOL = 'default';

    private string $defaultPool;

    /** @var array<CacheItemPoolInterface> */
    private array $pools;

    private string $controllerAdapter;

    public function __construct(array $config)
    {
        $config['pools'] = $config['pools'] ?? [];
        Assert::allIsInstanceOf($config['pools'], CacheItemPoolInterface::class);
        $config['default'] = $config['default'] ?? self::DEFAULT_POOL;
        $config['controllerAdapter'] = $config['controllerAdapter'] ?? $config['default'];

        parent::__construct($config);
        [
            'default' => $this->defaultPool,
            'pools' => $this->pools,
            'controllerAdapter' => $this->controllerAdapter,
        ] = $config;
    }

    /** @return array<CacheItemPoolInterface> */
    public function getPools(): array
    {
        return $this->pools;
    }

    public function getPool(string $name): CacheItemPoolInterface
    {
        if (false === $this->hasPool($name)) {
            throw new PoolDoesNotExistException($name);
        }

        return $this->pools[$name];
    }

    public function getDefaultPool(): CacheItemPoolInterface
    {
        if (false === $this->hasPool($this->defaultPool)) {
            throw new NoDefaultPoolException($this->defaultPool);
        }

        return $this->pools[$this->defaultPool];
    }

    public function hasPool(string $name): bool
    {
        return \array_key_exists($name, $this->pools);
    }

    public function getControllerPool(): CacheItemPoolInterface
    {
        return $this->getPool($this->controllerAdapter);
    }
}
