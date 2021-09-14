<?php

declare(strict_types=1);

namespace Docker\Endpoint;

use Docker\API\Endpoint\ContainerAttachWebsocket as BaseEndpoint;
use Docker\API\Runtime\Client\Client;
use Docker\Stream\AttachWebsocketStream;
use Jane\Component\OpenApiRuntime\Client\Exception\InvalidFetchModeException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;
use function array_merge;
use function base64_encode;
use function uniqid;

class ContainerAttachWebsocket extends BaseEndpoint
{
    public function getExtraHeaders(): array
    {
        return array_merge(parent::getExtraHeaders(), [
            'Host' => 'localhost',
            'Origin' => 'php://docker-php',
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Version' => '13',
            'Sec-WebSocket-Key' => base64_encode(uniqid('', true)),
        ]);
    }

    public function parseResponse(
        ResponseInterface $response,
        SerializerInterface $serializer,
        string $fetchMode = Client::FETCH_OBJECT
    ) {
        if ($fetchMode === Client::FETCH_OBJECT) {

            if ($response->getStatusCode() === 101) {
                return new AttachWebsocketStream($response->getBody());
            }

            $contentType = $response->hasHeader('Content-Type') ? current($response->getHeader('Content-Type')) : null;

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
        throw new InvalidFetchModeException(
            sprintf('Fetch mode %s is not supported', $fetchMode)
        );
    }

}
