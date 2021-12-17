<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache;

use Psr\Cache\CacheItemPoolInterface;
use Spiral\Console\Command;

final class CacheResetCommand extends Command
{
    public const NAME = 'cache:reset';
    public const DESCRIPTION = 'Reset PSR6-compatible application cache';

    protected function perform(CacheConfig $cache): int
    {
        /** @var CacheItemPoolInterface $pool */
        foreach ($cache->getPools() as $pool) {
            $pool->clear();
        }

        return self::SUCCESS;
    }
}
