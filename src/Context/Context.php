<?php

declare(strict_types=1);

namespace Docker\Context;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use function fclose;
use function file_get_contents;
use function is_resource;
use function proc_close;
use function proc_open;

/**
 * Docker\Context\Context.
 */
class Context implements ContextInterface
{
    public const FORMAT_STREAM = 'stream';
    public const FORMAT_TAR = 'tar';

    /**
     * @var bool Whether to remove the context directory
     */
    private bool $cleanup = false;

    /**
     * @var string
     */
    private string $directory;

    /**
     * @var resource Tar process
     */
    private $process;

    /**
     * @var Filesystem
     */
    private Filesystem $fs;

    /**
     * @var resource Tar stream
     */
    private $stream;

    /**
     * @var string Format of the context (stream or tar)
     */
    private string $format;

    /**
     * @param string $directory Directory of context
     * @param string $format Format to use when sending the call (stream or tar: string)
     * @param Filesystem|null $fs filesystem object for cleaning the context directory on destruction
     */
    public function __construct(string $directory, string $format = self::FORMAT_STREAM, Filesystem $fs = null)
    {
        $this->directory = $directory;
        $this->format = $format;
        $this->fs = $fs ?? new Filesystem();
    }

    public static function builder(Filesystem $filesystem = null): ContextBuilderInterface
    {
        return new ContextBuilder($filesystem);
    }

    /**
     * @inheritDoc
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @inheritDoc
     */
    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }

    /**
     * @inheritDoc
     */
    public function getDockerfileContent(): string
    {
        return file_get_contents($this->directory.DIRECTORY_SEPARATOR.'Dockerfile');
    }

    public function readString(): string
    {
        return $this->toTar();
    }

    /**
     * Return the context as a tar archive.
     *
     * @return string Tar content
     * @throws ProcessFailedException
     *
     */
    public function toTar(): string
    {
        $process = Process::fromShellCommandline('/usr/bin/env tar c .', $this->directory);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * @inheritDoc
     */
    public function readStream()
    {
        return $this->asStream();
    }

    /**
     * Return a stream for this context.
     *
     * @return resource Stream resource in memory
     */
    public function asStream()
    {
        if (!is_resource($this->process)) {
            $this->process = proc_open(
                '/usr/bin/env tar c .',
                [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
                $pipes,
                $this->directory
            );
            $this->stream = $pipes[1];
        }

        return $this->stream;
    }

    /**
     * @inheritDoc
     */
    public function isStreamed(): bool
    {
        return self::FORMAT_STREAM === $this->format;
    }

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        if (is_resource($this->process)) {
            proc_close($this->process);
        }

        if ($this->cleanup) {
            $this->fs->remove($this->directory);
        }
    }

    /**
     * @inheritDoc
     */
    public function setCleanup(bool $value): void
    {
        $this->cleanup = $value;
    }
}
