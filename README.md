# Spiral cache

This package integrates PSR-6 and PSR-16 compatible cache implementations
(mostly Symfony one) into Spiral application. As a bonus it provides convenient
caching solution for HTTP responses.


## Installation

```sh
composer require ruvents/spiral-cache symfony/cache:^5.0 psr/simple-cache
```

`psr/simple-cache` is optional, you can only use PSR-6 compliant cache.

`symfony/cache` is optional, you can use any PSR-6 implementation of your
choice.

Then add `CacheBootloader` to your `App.php`:

```php
use Ruvents\SpiralCache\CacheBootloader;

class App extends Kernel {
    protected const LOAD = [
        ...
        CacheBootloader::class,
    ]
}
```

## Configuration

Put the following code into file `app/config/cache.php`:

```php
<?php

declare(strict_types=1);

return [
    // Array of named cache pools.
    'pools' => [
        'localCache' => new ArrayAdapter(), // can be any object that implements CacheItemPoolInterface
    ],
    // Optional, default value is 'default'. Item with specified key must be
    // present in 'pools' array.
    'default' => 'localCache',
     // Optional, will use default cache pool if omitted.
    'controllerPool' => *instantiated CacheItemPoolInterface object*,
];
```

Default pool must be created in order for package to function properly.


## Use

### Manually

After configuration you should be able to inject created cache pool into your
code by its name:

```php
use Psr\Cache\CacheItemPoolInterface;

/**
 * @Route('/list', methods="GET")
 */
public function list(CacheItemPoolInterface $localCache): ResponseInterface {
    if ($localCache->hasItem('list')) {
        return $localCache->getItem('list')->get();
    }

    $response = ...;
    $item = $localCache->getItem('list');
    $item->set($response);
    $localCache->save($item);

    return $response;
}
```

If `symfony/cache` is installed you can upgrade PSR-6 cache implementation to
PSR-16 by specifying `CacheInterface`:

```php
use Psr\SimpleCache\CacheInterface;

/**
 * @Route('/list', methods="GET")
 */
public function list(CacheInterface $localCache): ResponseInterface {
    if ($localCache->has('list')) {
        return $localCache->get('list');
    }

    $response = ...;
    $localCache->set('list', $response);

    return $response;
}
```

If `symfony/cache` is installed you can inject
[its implementation of cache](https://symfony.com/doc/5.4/components/cache.html):

```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Route('/list', methods="GET")
 */
public function list(CacheInterface $localCache): ResponseInterface {
    return $localCache->get('list', static function (ItemInterface $item) {
        $item->expiresAfter(3600);
        $response = ...;

        return $response;
    });
}
```

If `symfony/cache` is installed you can inject tag-aware cache:

```php
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Route('/list', methods="GET")
 */
public function list(TagAwareCacheInterface $localCache): ResponseInterface {
    if ($localCache->hasItem('list')) {
        return $localCache->getItem('list')->get();
    }

    $response = ...;
    $item = $localCache->getItem('list');
    $item->set($response);
    $item->tag(['api.list']);
    $localCache->save($item);

    return $response;
}
```

### With #[Cached] attribute

You can use `#[Cached]` attribute to remove caching code from HTTP action. You'll
need to install `Ruvents\SpiralCache\CacheInterceptor` in order to have this
attribute recognized. See
[related documentation](https://spiral.dev/docs/cookbook-domain-core#core-interceptors)
for installation details.

Here is an example on how to cache HTTP response for a given action:

```php
use Ruvents\SpiralCache\Annotation\Cached;

/**
 * @Route('/list', methods="GET")
 */
#[Cached('+4 hours')]
public function list(): ResponseInterface {
    $response = ...;
    return $response;
}
```

Method's class name, method's name and request URI are all used to automatically
generate cache key. To implement custom key generation logic specify `key` in
attribute:

```php
use Ruvents\SpiralCache\Annotation\Cached;

/**
 * @Route('/list', methods="GET")
 */
#[Cached('+4 hours', key: [self::class, 'keyGenerator'])]
public function list(): ResponseInterface {
    $response = ...;
    return $response;
}

public static function keyGenerator(array $context): string {
    $key = ...; // any logic here
    return $key;
}
```

If you want to apply caching conditionally specify callable array in `if` key:

```php
use Ruvents\SpiralCache\Annotation\Cached;

#[Cached('+4 hours', if: [self::class, 'cacheCondition'])]
public function list(): ResponseInterface {
    $response = ...;
    return $response;
}

public static function cacheCondition(array $context): string {
    $key = ...; // any logic here
    return $key;
}
```

If `symfony/cache` is installed and `TagAwareCacheInterface` is used as
`controllerPool` you can selectively clear groups of cache items by specifying
`tags`:

```php
use Ruvents\SpiralCache\Annotation\Cached;

#[Cached('+4 hours', tags: ['api.list'])]
public function list(): ResponseInterface {
    $response = ...;
    return $response;
}

... somewhere else in code ...

public function clearCache(TagAwareCacheInterface $localCache): string {
    $localCache->invalidateTags(['api.list']);
}
```
