<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Tests;

use Ruvents\SpiralCache\Bootloader\CacheBootloader;
use Ruvents\SpiralCache\Config\CacheConfig;
use Ruvents\SpiralCache\Domain\CacheInterceptor;
use Ruvents\SpiralCache\Response\ResponseNormalizer;
use Ruvents\SpiralCache\Tests\Fixtures\TestController;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\ReaderInterface;
use Spiral\Boot\BootloadManager;
use Spiral\Core\Container;
use Spiral\Core\Core;
use Spiral\Core\CoreInterface;
use Spiral\Core\InterceptableCore;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @internal
 * @covers \Ruvents\SpiralCache\Domain\CacheInterceptor
 */
class CacheInterceptorTest extends TestCase
{
    private ?Container $container = null;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->bindSingleton(
            ResponseNormalizer::class,
            new ResponseNormalizer(new Psr17Factory(), new Psr17Factory())
        );
        $this->container->bindSingleton(
            CacheConfig::class,
            new CacheConfig([
                'pools' => [
                    'default' => new TagAwareAdapter(new ArrayAdapter()),
                    'secondary' => new TagAwareAdapter(new ArrayAdapter()),
                ],
                'controllerAdapter' => 'secondary',
            ])
        );
        $this->container->bindSingleton(
            ServerRequestInterface::class,
            (new Psr17Factory())->createServerRequest('GET', '/test')
        );
        (new BootloadManager($this->container))->bootload([CacheBootloader::class]);
        $this->container->bindSingleton(ReaderInterface::class, new AttributeReader());
    }

    protected function tearDown(): void
    {
        /** @var CacheItemPoolInterface */
        $cache = $this->getCacheConfig()->getControllerPool();
        $cache->clear();
    }

    public function testCacheMissAndHit(): void
    {
        $core = $this->getCore();

        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cached', ['response' => 'ok'])
        );
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cached', ['response' => 'something new'])
        );
    }

    public function testCacheWithStringKey(): void
    {
        $core = $this->getCore();
        $cache = $this->getCacheConfig()->getControllerPool();

        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithStringKey', ['response' => 'ok'])
        );
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithStringKey', ['response' => 'something new'])
        );

        $item = $cache->getItem(
            sprintf(
                '%s:%s:%s:%s',
                CacheInterceptor::class,
                TestController::class,
                'cachedWithStringKey',
                'test-key'
            )
        );
        $this->assertSame('ok', $item->get());
    }

    public function testCacheWithCallableArrayKey(): void
    {
        $core = $this->getCore();
        $cache = $this->getCacheConfig()->getControllerPool();

        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithCallableArrayKey', ['response' => 'ok'])
        );
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithCallableArrayKey', ['response' => 'something new'])
        );

        $item = $cache->getItem(
            sprintf(
                '%s:%s:%s:%s',
                CacheInterceptor::class,
                TestController::class,
                'cachedWithCallableArrayKey',
                'echo'
            )
        );
        $this->assertSame('ok', $item->get());

        // Генерация ключа из параметра прерывателя.
        $this->assertSame(
            'ok2',
            $core->callAction(TestController::class, 'cachedWithCallableArrayKey', ['response' => 'ok2', 'echo' => 'foobar'])
        );
        $this->assertSame(
            'ok2',
            $core->callAction(TestController::class, 'cachedWithCallableArrayKey', ['response' => 'something new', 'echo' => 'foobar'])
        );

        $item = $cache->getItem(
            sprintf(
                '%s:%s:%s:%s',
                CacheInterceptor::class,
                TestController::class,
                'cachedWithCallableArrayKey',
                'foobar'
            )
        );
        $this->assertSame('ok2', $item->get());
    }

    public function testCacheTags(): void
    {
        $core = $this->getCore();

        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithTags', ['response' => 'ok'])
        );
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithTags', ['response' => 'something new'])
        );

        /** @var TagAwareCacheInterface */
        $taggedCache = $this->getCacheConfig()->getControllerPool();
        $taggedCache->invalidateTags(['foo']);

        $this->assertSame(
            'something new',
            $core->callAction(TestController::class, 'cachedWithTags', ['response' => 'something new'])
        );
    }

    public function testCacheCondition(): void
    {
        $core = $this->getCore();

        // Условие не проходит, поэтоу не кэшируем ответ.
        $pass = false;
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithCondition', ['response' => 'ok', 'pass' => $pass])
        );
        $this->assertSame(
            'something new',
            $core->callAction(TestController::class, 'cachedWithCondition', ['response' => 'something new', 'pass' => $pass])
        );

        // Условие проходит, поэтоу кэшируем ответ.
        $pass = true;
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithCondition', ['response' => 'ok', 'pass' => $pass])
        );
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithCondition', ['response' => 'something new', 'pass' => $pass])
        );
    }

    public function testCacheWithSeveralConditions(): void
    {
        $core = $this->getCore();

        // Оба условия не срабатывают, поэтому ответ не кэшируется.
        $pass = false;
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithSeveralConditions', ['response' => 'ok', 'pass' => $pass])
        );
        $this->assertSame(
            'something new',
            $core->callAction(TestController::class, 'cachedWithSeveralConditions', ['response' => 'something new', 'pass' => $pass])
        );

        // Срабатывает второе условие, поэтому ответ кэшируется.
        $pass = true;
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithSeveralConditions', ['response' => 'ok', 'pass' => $pass])
        );
        $this->assertSame(
            'ok',
            $core->callAction(TestController::class, 'cachedWithSeveralConditions', ['response' => 'something new', 'pass' => $pass])
        );
    }

    private function getCore(): CoreInterface
    {
        $core = new InterceptableCore(new Core($this->container));
        $core->addInterceptor(
            $this->container->make(CacheInterceptor::class)
        );

        return $core;
    }

    private function getCacheConfig(): CacheConfig
    {
        /** @psalm-ignore-nullable-return  */
        return $this->container->get(CacheConfig::class);
    }
}
