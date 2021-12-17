<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Domain;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Ruvents\SpiralCache\Annotation\Cached;
use Ruvents\SpiralCache\Response\CachedResponse;
use Ruvents\SpiralCache\Response\ResponseNormalizer;
use Spiral\Attributes\ReaderInterface;
use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Symfony\Contracts\Cache\ItemInterface as SymfonyItemInterface;

class CacheInterceptor implements CoreInterceptorInterface
{
    public function __construct(
        private ReaderInterface $reader,
        private ResponseNormalizer $responseNormalizer,
        private CacheItemPoolInterface $cache,
        private ContainerInterface $container
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(string $controller, string $action, array $parameters, CoreInterface $core)
    {
        $method = new \ReflectionMethod($controller, $action);
        $metadatas = $this->reader->getFunctionMetadata($method, Cached::class);

        /** @var Cached $metadata */
        foreach ($metadatas as $metadata) {
            $context = [
                'controller' => $controller,
                'action' => $action,
                'parameters' => $parameters,
            ];

            if (
                false === empty($metadata->if)
                && false === $this->call($metadata->if, $context)
            ) {
                continue;
            }

            $key = $metadata->key;

            if (\is_array($key)) {
                $key = $this->call($key, $context);
            }

            $item = $this->cache->getItem($this->getCacheKey($controller, $action, $key));

            if (false === $item->isHit()) {
                $response = $core->callAction($controller, $action, $parameters);

                $item->expiresAt(new \DateTimeImmutable($metadata->expiresAt));
                $item->set($this->getNormalizedResponseResult($response));

                if (0 < \count($metadata->tags) && $item instanceof SymfonyItemInterface) {
                    $item->tag($metadata->tags);
                }

                $this->cache->save($item);
            }

            $response = $item->get();

            if ($response instanceof CachedResponse) {
                $response = $this->responseNormalizer->denormalize($item->get());
            }

            return $response;
        }

        return $core->callAction($controller, $action, $parameters);
    }

    private function getNormalizedResponseResult(mixed $response): mixed
    {
        if ($response instanceof ResponseInterface) {
            return $this->responseNormalizer->normalize($response);
        }

        return $response;
    }

    private function getCacheKey(string $controller, string $action, string $id): string
    {
        return sprintf('%s:%s:%s:%s', __CLASS__, $controller, $action, $id);
    }

    private function call(array $callable, array $context): mixed
    {
        $class = array_shift($callable);
        $method = array_shift($callable);
        $args = $callable ?? [];
        $args[] = $context;

        $service = $this->container->get($class);

        return $service->$method(...$args);
    }
}
