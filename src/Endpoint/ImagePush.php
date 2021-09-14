<?php

declare(strict_types=1);

namespace Docker\Endpoint;

use Docker\API\Endpoint\ImagePush as BaseEndpoint;
use Docker\API\Runtime\Client\Client;
use Docker\Stream\PushStream;
use Jane\Component\OpenApiRuntime\Client\Exception\InvalidFetchModeException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;
use function sprintf;
use function str_replace;
use function urlencode;

class ImagePush extends BaseEndpoint
{
    public function getUri(): string
    {
        return str_replace(['{name}'], [urlencode($this->name)], '/images/{name}/push');
    }

    public function parseResponse(
        ResponseInterface $response,
        SerializerInterface $serializer,
        string $fetchMode = Client::FETCH_OBJECT
    ) {
        if (Client::FETCH_OBJECT === $fetchMode) {
            if (200 === $response->getStatusCode()) {
                return new PushStream($response->getBody(), $serializer);
            }

            return $this->transformResponseBody((string) $response->getBody(), $response->getStatusCode(), $serializer);
        }

        if (Client::FETCH_RESPONSE === $fetchMode) {
            return $response;
        }

        throw new InvalidFetchModeException(sprintf('Fetch mode %s is not supported', $fetchMode));
    }

}
