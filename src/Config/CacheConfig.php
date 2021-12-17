<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Config;

use Psr\Cache\CacheItemPoolInterface;
use Ruvents\SpiralCache\Exception\NoDefaultPoolException;
use Ruvents\SpiralCache\Exception\PoolDoesNotExistException;
use Spiral\Core\InjectableConfig;

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

        foreach ($config['pools'] as $poolName => $pool) {
            if (false === $pool instanceof CacheItemPoolInterface) {
                throw new \InvalidArgumentException(
                    "Cache pool \"${poolName}\" must be an instance of Psr\Cache\CacheItemPoolInterface"
                );
            }
        }

        $config['default'] = $config['default'] ?? self::DEFAULT_POOL;
        $config['controllerPool'] = $config['controllerPool'] ?? $config[self::DEFAULT_POOL];

        parent::__construct($config);
        [
            'default' => $this->defaultPool,
            'pools' => $this->pools,
            'controllerPool' => $this->controllerPool,
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
        return $this->getPool($this->controllerPool);
    }
}
