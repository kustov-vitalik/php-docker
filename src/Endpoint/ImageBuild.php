<?php

declare(strict_types=1);

namespace Docker\Endpoint;

use Docker\API\Endpoint\ImageBuild as BaseEndpoint;
use Docker\API\Runtime\Client\Client;
use Docker\Stream\BuildStream;
use Docker\Stream\TarStream;
use Http\Message\StreamFactory;
use Jane\Component\OpenApiRuntime\Client\Exception\InvalidFetchModeException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;
use function is_resource;
use function sprintf;

class ImageBuild extends BaseEndpoint
{
    public function parseResponse(
        ResponseInterface $response,
        SerializerInterface $serializer,
        string $fetchMode = Client::FETCH_OBJECT
    ) {
        if (Client::FETCH_OBJECT === $fetchMode) {
            if (200 === $response->getStatusCode()) {
                return new BuildStream($response->getBody(), $serializer);
            }

            return $this->transformResponseBody((string)$response->getBody(), $response->getStatusCode(), $serializer);
        }

        if (Client::FETCH_RESPONSE === $fetchMode) {
            return $response;
        }

        throw new InvalidFetchModeException(sprintf('Fetch mode %s is not supported', $fetchMode));
    }

    public function getBody(SerializerInterface $serializer, $streamFactory = null): array
    {
        $body = $this->body;

        if (is_resource($body)) {
            $body = new TarStream($body);
        }

        return [[], $body];
    }
}
