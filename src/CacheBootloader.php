<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Ruvents\SpiralCache\CacheConfig;
use Ruvents\SpiralCache\CacheResetCommand;
use Ruvents\SpiralCache\CacheInjector;
use Ruvents\SpiralCache\CacheInterceptor;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\ConsoleBootloader;
use Spiral\Core\Container;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\CacheInterface as SymfonyCacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CacheBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        ConsoleBootloader::class,
    ];

    public function boot(Container $container, CacheConfig $config, ConsoleBootloader $console): void
    {
        $console->addCommand(CacheResetCommand::class);
        $container->bindInjector(CacheItemPoolInterface::class, CacheInjector::class);

        // PSR-16 injection if installed.
        if (interface_exists(CacheInterface::class)) {
            $container->bindInjector(CacheInterface::class, CacheInjector::class);
        }

        // symfony/cache injection if installed.
        if (interface_exists(SymfonyCacheInterface::class)) {
            $container->bindInjector(SymfonyCacheInterface::class, CacheInjector::class);
        }
        if (interface_exists(TagAwareCacheInterface::class)) {
            $container->bindInjector(TagAwareCacheInterface::class, CacheInjector::class);
        }

        // FIXME: temporary declarations until this PR is merged:
        // https://github.com/spiral/framework/pull/444
        $pool = $config->getDefaultPool();
        $container->bindSingleton(CacheItemPoolInterface::class, $pool);
        $container->bindSingleton(CacheInterface::class, new Psr16Cache($pool));
        $container->bindSingleton(CacheInterceptor::class, static function () use ($container, $config) {
            return $container->make(CacheInterceptor::class, ['cache' => $config->getControllerPool()]);
        });
        if ($pool instanceof TagAwareCacheInterface) {
            $container->bindSingleton(TagAwareCacheInterface::class, $pool);
        }
        if ($pool instanceof SymfonyCacheInterface) {
            $container->bindSingleton(SymfonyCacheInterface::class, $pool);
        }
    }
}
