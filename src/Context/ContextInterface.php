<?php

declare(strict_types=1);

namespace Docker\Context;

use Psr\Http\Message\StreamInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Docker\Context\ContextInterface.
 */
interface ContextInterface
{
    public static function builder(Filesystem $filesystem = null): ContextBuilderInterface;

    /**
     * Whether the Context should be streamed or not.
     *
     * @return bool
     */
    public function isStreamed(): bool;

    /**
     * If `isStreamed()` is `false`, then `readString()` should return return the plain content.
     *
     * @return string
     */
    public function readString(): string;

    /**
     * If `isStreamed()` is `true`, then `readResource()` should return a resource.
     *
     * @return resource
     */
    public function readStream();

    /**
     * @param bool $value whether to remove the context directory
     */
    public function setCleanup(bool $value): void;

    /**
     * Return content of Dockerfile of this context.
     *
     * @return string Content of dockerfile
     */
    public function getDockerfileContent(): string;

    /**
     * Set directory of Context.
     *
     * @param string $directory Targeted directory
     */
    public function setDirectory(string $directory): void;

    /**
     * Get directory of Context.
     *
     * @return string
     */
    public function getDirectory(): string;


}
