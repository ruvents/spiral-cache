<?php

declare(strict_types=1);

namespace Ruvents\SpiralCache\Annotation;

use Ruvents\SpiralCache\KeyGenerator;
use Attribute;
use Spiral\Attributes\NamedArgumentConstructor;

#[Attribute(Attribute::TARGET_METHOD)]
#[NamedArgumentConstructor()]
final class Cached
{
    public function __construct(
        public string $expiresAt,
        public string|array $key = [KeyGenerator::class, 'request'],
        public array $if = [],
        public array $tags = []
    ) {
    }
}
