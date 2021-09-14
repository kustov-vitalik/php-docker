<?php

declare(strict_types=1);

namespace Docker\Context;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use function array_key_exists;
use function basename;
use function fclose;
use function fopen;
use function implode;
use function is_dir;
use function md5;
use function microtime;
use function realpath;
use function stream_copy_to_stream;
use function sys_get_temp_dir;
use function tempnam;

class ContextBuilder implements ContextBuilderInterface
{
    private array $commands = [];
    private array $files = [];
    private Filesystem $fs;
    private string $format;
    private string $command;
    private string $entrypoint;

    /**
     * @param Filesystem|null $fs
     */
    public function __construct(Filesystem $fs = null)
    {
        $this->fs = $fs ?? new Filesystem();
        $this->format = Context::FORMAT_STREAM;
    }

    /**
     * Sets the format of the Context output.
     *
     * @param string $format
     *
     * @return ContextBuilder
     */
    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Add a FROM instruction of Dockerfile.
     *
     * @param string $from From which image we start
     *
     * @return ContextBuilder
     */
    public function from(string $from): static
    {
        $this->commands[] = ['type' => 'FROM', 'image' => $from];

        return $this;
    }

    /**
     * Set the CMD instruction in the Dockerfile.
     *
     * @param string $command Command to execute
     *
     * @return ContextBuilder
     */
    public function command(string $command): static
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Set the ENTRYPOINT instruction in the Dockerfile.
     *
     * @param string $entrypoint The entrypoint
     *
     * @return ContextBuilder
     */
    public function entrypoint(string $entrypoint): static
    {
        $this->entrypoint = $entrypoint;

        return $this;
    }

    /**
     * Add an ADD instruction to Dockerfile.
     *
     * @param string $path Path wanted on the image
     * @param string $content Content of file
     *
     * @return ContextBuilder
     */
    public function add(string $path, string $content): static
    {
        $this->commands[] = ['type' => 'ADD', 'path' => $path, 'content' => $content];

        return $this;
    }

    /**
     * Add an ADD instruction to Dockerfile.
     *
     * @param string $path Path wanted on the image
     * @param resource $stream stream that contains file content
     *
     * @return ContextBuilder
     */
    public function addStream(string $path, $stream): static
    {
        $this->commands[] = ['type' => 'ADDSTREAM', 'path' => $path, 'stream' => $stream];

        return $this;
    }

    /**
     * Add an ADD instruction to Dockerfile.
     *
     * @param string $path Path wanted on the image
     * @param string $file Source file (or directory) name
     *
     * @return ContextBuilder
     */
    public function addFile(string $path, string $file): static
    {
        $this->commands[] = ['type' => 'ADDFILE', 'path' => $path, 'file' => $file];

        return $this;
    }

    /**
     * Add a RUN instruction to Dockerfile.
     *
     * @param string $command Command to run
     *
     * @return ContextBuilder
     */
    public function run(string $command): static
    {
        $this->commands[] = ['type' => 'RUN', 'command' => $command];

        return $this;
    }

    /**
     * Add a ENV instruction to Dockerfile.
     *
     * @param string $name Name of the environment variable
     * @param string $value Value of the environment variable
     *
     * @return ContextBuilder
     */
    public function env(string $name, string $value): static
    {
        $this->commands[] = ['type' => 'ENV', 'name' => $name, 'value' => $value];

        return $this;
    }

    /**
     * Add a COPY instruction to Dockerfile.
     *
     * @param string $from Path of folder or file to copy
     * @param string $to Path of destination
     *
     * @return ContextBuilder
     */
    public function copy(string $from, string $to): static
    {
        $this->commands[] = ['type' => 'COPY', 'from' => $from, 'to' => $to];

        return $this;
    }

    /**
     * Add a WORKDIR instruction to Dockerfile.
     *
     * @param string $workdir Working directory
     *
     * @return ContextBuilder
     */
    public function workdir(string $workdir): static
    {
        $this->commands[] = ['type' => 'WORKDIR', 'workdir' => $workdir];

        return $this;
    }

    /**
     * Add a EXPOSE instruction to Dockerfile.
     *
     * @param int $port Port to expose
     *
     * @return ContextBuilder
     */
    public function expose(int $port): static
    {
        $this->commands[] = ['type' => 'EXPOSE', 'port' => $port];

        return $this;
    }

    /**
     * Adds an USER instruction to the Dockerfile.
     *
     * @param string $user User to switch to
     *
     * @return ContextBuilder
     */
    public function user(string $user): static
    {
        $this->commands[] = ['type' => 'USER', 'user' => $user];

        return $this;
    }

    /**
     * Adds a VOLUME instruction to the Dockerfile.
     *
     * @param string $volume Volume path to add
     *
     * @return ContextBuilder
     */
    public function volume(string $volume): static
    {
        $this->commands[] = ['type' => 'VOLUME', 'volume' => $volume];

        return $this;
    }

    /**
     * Create context given the state of builder.
     *
     * @return ContextInterface
     */
    public function build(): ContextInterface
    {
        $directory = sys_get_temp_dir().'/ctb-'.microtime();
        $this->fs->mkdir($directory);
        $this->write($directory);

        $result = new Context($directory, $this->format, $this->fs);
        $result->setCleanup(true);

        return $result;
    }

    /**
     * Write docker file and associated files in a directory.
     *
     * @param string $directory Target directory
     *
     * @void
     */
    private function write(string $directory): void
    {
        $dockerfile = [];
        // Insert a FROM instruction if the file does not start with one.
        if (empty($this->commands) || $this->commands[0]['type'] !== 'FROM') {
            $dockerfile[] = 'FROM base';
        }
        foreach ($this->commands as $command) {
            $dockerfile[] = match ($command['type']) {
                'FROM' => 'FROM '.$command['image'],
                'RUN' => 'RUN '.$command['command'],
                'ADD' => 'ADD '.$this->getFile($directory, $command['content']).' '.$command['path'],
                'ADDFILE' => 'ADD '.$this->getFileFromDisk($directory, $command['file']).' '.$command['path'],
                'ADDSTREAM' => 'ADD '.$this->getFileFromStream($directory, $command['stream']).' '.$command['path'],
                'COPY' => 'COPY '.$command['from'].' '.$command['to'],
                'ENV' => 'ENV '.$command['name'].' '.$command['value'],
                'WORKDIR' => 'WORKDIR '.$command['workdir'],
                'EXPOSE' => 'EXPOSE '.$command['port'],
                'VOLUME' => 'VOLUME '.$command['volume'],
                'USER' => 'USER '.$command['user'],
            };
        }

        if (!empty($this->entrypoint)) {
            $dockerfile[] = 'ENTRYPOINT '.$this->entrypoint;
        }

        if (!empty($this->command)) {
            $dockerfile[] = 'CMD '.$this->command;
        }

        $this->fs->dumpFile($directory.DIRECTORY_SEPARATOR.'Dockerfile', implode(PHP_EOL, $dockerfile));
    }

    /**
     * Generate a file in a directory.
     *
     * @param string $directory Targeted directory
     * @param string $content Content of file
     *
     * @return string Name of file generated
     */
    private function getFile(string $directory, string $content): string
    {
        $hash = md5($content);

        if (!array_key_exists($hash, $this->files)) {
            $file = tempnam($directory, '');
            $this->fs->dumpFile($file, $content);
            $this->files[$hash] = basename($file);
        }

        return $this->files[$hash];
    }

    /**
     * Generated a file in a directory from an existing file.
     *
     * @param string $directory Targeted directory
     * @param string $source Path to the source file
     *
     * @return string Name of file generated
     */
    private function getFileFromDisk(string $directory, string $source): string
    {
        $hash = 'DISK-'.md5(realpath($source));
        if (!array_key_exists($hash, $this->files)) {
            // Check if source is a directory or a file.
            if (is_dir($source)) {
                $this->fs->mirror($source, $directory.'/'.$hash, null, ['copy_on_windows' => true]);
            } else {
                $this->fs->copy($source, $directory.'/'.$hash);
            }

            $this->files[$hash] = $hash;
        }

        return $this->files[$hash];
    }

    /**
     * Generated a file in a directory from a stream.
     *
     * @param string $directory Targeted directory
     * @param resource $stream Stream containing file contents
     *
     * @return string Name of file generated
     */
    private function getFileFromStream(string $directory, $stream): string
    {
        $file = tempnam($directory, '');
        $target = fopen($file, 'w');
        if (0 === stream_copy_to_stream($stream, $target)) {
            throw new RuntimeException('Failed to write stream to file');
        }
        fclose($target);

        return basename($file);
    }
}
