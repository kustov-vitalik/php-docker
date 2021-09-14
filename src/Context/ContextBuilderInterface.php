<?php

declare(strict_types=1);


namespace Docker\Context;


interface ContextBuilderInterface
{
    /**
     * Builds context.
     *
     * @return ContextInterface
     */
    public function build(): ContextInterface;

    /**
     * Sets the format of the Context output.
     *
     * @param string $format
     * @return ContextBuilderInterface
     */
    public function setFormat(string $format): ContextBuilderInterface;

    /**
     * Add a FROM instruction of Dockerfile.
     *
     * @param string $from From which image we start
     *
     * @return ContextBuilderInterface
     */
    public function from(string $from): ContextBuilderInterface;

    /**
     * Set the CMD instruction in the Dockerfile.
     *
     * @param string $command Command to execute
     *
     * @return ContextBuilderInterface
     */
    public function command(string $command): ContextBuilderInterface;

    /**
     * Set the ENTRYPOINT instruction in the Dockerfile.
     *
     * @param string $entrypoint The entrypoint
     *
     * @return ContextBuilderInterface
     */
    public function entrypoint(string $entrypoint): ContextBuilderInterface;

    /**
     * Add an ADD instruction to Dockerfile.
     *
     * @param string $path Path wanted on the image
     * @param string $content Content of file
     *
     * @return ContextBuilderInterface
     */
    public function add(string $path, string $content): ContextBuilderInterface;

    /**
     * Add an ADD instruction to Dockerfile.
     *
     * @param string $path Path wanted on the image
     * @param resource $stream stream that contains file content
     *
     * @return ContextBuilderInterface
     */
    public function addStream(string $path, $stream): ContextBuilderInterface;

    /**
     * Add an ADD instruction to Dockerfile.
     *
     * @param string $path Path wanted on the image
     * @param string $file Source file (or directory) name
     *
     * @return ContextBuilderInterface
     */
    public function addFile(string $path, string $file): ContextBuilderInterface;

    /**
     * Add a RUN instruction to Dockerfile.
     *
     * @param string $command Command to run
     *
     * @return ContextBuilderInterface
     */
    public function run(string $command): ContextBuilderInterface;

    /**
     * Add a ENV instruction to Dockerfile.
     *
     * @param string $name Name of the environment variable
     * @param string $value Value of the environment variable
     *
     * @return ContextBuilderInterface
     */
    public function env(string $name, string $value): ContextBuilderInterface;

    /**
     * Add a COPY instruction to Dockerfile.
     *
     * @param string $from Path of folder or file to copy
     * @param string $to Path of destination
     *
     * @return ContextBuilderInterface
     */
    public function copy(string $from, string $to): ContextBuilderInterface;

    /**
     * Add a WORKDIR instruction to Dockerfile.
     *
     * @param string $workdir Working directory
     *
     * @return ContextBuilderInterface
     */
    public function workdir(string $workdir): ContextBuilderInterface;

    /**
     * Add a EXPOSE instruction to Dockerfile.
     *
     * @param int $port Port to expose
     *
     * @return ContextBuilderInterface
     */
    public function expose(int $port): ContextBuilderInterface;

    /**
     * Adds an USER instruction to the Dockerfile.
     *
     * @param string $user User to switch to
     *
     * @return ContextBuilderInterface
     */
    public function user(string $user): ContextBuilderInterface;

    /**
     * Adds a VOLUME instruction to the Dockerfile.
     *
     * @param string $volume Volume path to add
     *
     * @return ContextBuilderInterface
     */
    public function volume(string $volume): ContextBuilderInterface;
}