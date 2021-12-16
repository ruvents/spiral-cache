<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Container;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Ruvents\SpiralCache\Config\CacheConfig;
use Ruvents\SpiralCache\Exception\PoolDoesNotSupportThisInjectionException;
use Spiral\Core\Container\InjectorInterface;
use Symfony\Component\Cache\Psr16Cache;

final class CacheInjector implements InjectorInterface
{
    public function __construct(private CacheConfig $config)
    {
    }

    public function createInjection(\ReflectionClass $class, string $context = null)
    {
        if (null === $context || 1 === \count($this->config->getPools())) {
            $pool = $this->config->getDefaultPool();
        } else {
            $pool = $this->config->getPool($context);
        }

        if ($this->canUpgradeToPsr16($pool, $class)) {
            return new Psr16Cache($pool);
        }

        if (false === is_subclass_of($pool, $class->getName())) {
            throw new PoolDoesNotSupportThisInjectionException(
                $context ?? CacheConfig::DEFAULT_POOL,
                $class->getName()
            );
        }

        return $pool;
    }

    private function canUpgradeToPsr16(object $result, \ReflectionClass $class): bool
    {
        return $result instanceof CacheItemPoolInterface
            && $class->isInterface()
            && CacheInterface::class === $class->getName()
        ;
    }
}
