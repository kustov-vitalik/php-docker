<?php

declare(strict_types=1);

namespace Docker\Stream;

use Docker\API\Endpoint\SystemEvents;
use Docker\API\Model\EventMessage;

/**
 * Represent a stream when pushing an image to a repository (with the push api endpoint of image).
 *
 * Callable(s) passed to this stream will take a Event object as the first argument
 */
class EventStream extends MultiJsonStream
{
    /**
     * [@inheritdoc}.
     */
    protected function getDecodeClass(): string
    {
        return EventMessage::class;
    }
}
