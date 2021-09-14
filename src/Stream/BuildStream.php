<?php

declare(strict_types=1);

namespace Docker\Stream;

use Docker\API\Model\BuildInfo;

/**
 * Represent a stream when building a dockerfile.
 *
 * Callable(s) passed to this stream will take a BuildInfo object as the first argument
 */
class BuildStream extends MultiJsonStream
{
    /**
     * [@inheritdoc}.
     */
    protected function getDecodeClass(): string
    {
        return BuildInfo::class;
    }
}
