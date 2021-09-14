<?php

declare(strict_types=1);

namespace Docker\Endpoint;

use Docker\API\Endpoint\ContainerAttach as BaseEndpoint;
use Docker\API\Runtime\Client\Client;
use Docker\Stream\DockerRawStream;
use Jane\Component\OpenApiRuntime\Client\Exception\InvalidFetchModeException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ContainerAttach extends BaseEndpoint
{

    /**
     * @param ResponseInterface $response
     * @param SerializerInterface $serializer
     * @param string $fetchMode
     * @return DockerRawStream|ResponseInterface|null
     */
    public function parseResponse(
        ResponseInterface $response,
        SerializerInterface $serializer,
        string $fetchMode = Client::FETCH_OBJECT
    ) {
        if ($fetchMode === Client::FETCH_OBJECT) {
            $contentType = $response->hasHeader('Content-Type') ? current($response->getHeader('Content-Type')) : null;

            if ($contentType === DockerRawStream::HEADER && $response->getStatusCode() === 200) {
                return new DockerRawStream($response->getBody());
            }

            return $this->transformResponseBody(
                (string)$response->getBody(),
                $response->getStatusCode(),
                $serializer,
                $contentType
            );
        }
        if ($fetchMode === Client::FETCH_RESPONSE) {
            return $response;
        }
        throw new InvalidFetchModeException(sprintf('Fetch mode %s is not supported', $fetchMode));
    }
}
