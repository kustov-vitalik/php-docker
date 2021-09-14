<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

$docker = \Docker\Docker::create();

$context = \Docker\Context\Context::builder()
    ->from('busybox:latest')
    ->run('echo 123')
    ->build();

$input = $context->isStreamed() ? $context->readStream() : $context->readString();

/** @var \Docker\Stream\BuildStream $buildStream */
try {
    $buildStream = $docker->imageBuild($input, fetch: \Docker\Docker::FETCH_OBJECT);

    $buildStream->onFrame(function (\Docker\API\Model\BuildInfo $buildInfo) {
        var_dump($buildInfo->getAux()?->getID());
    });

    $buildStream->wait();
} catch (\Docker\API\Exception\ImageBuildInternalServerErrorException $e) {
    var_dump($e->getErrorResponse());
}






