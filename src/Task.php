<?php

namespace LukasKleinschmidt\Tasks;

use Kirby\Toolkit\F;

class Task
{
    /**
     * Cache instance
     *
     * @var Kirby\Cache\Cache
     */
    protected $cache;

    /**
     * The command
     *
     * @var Command
     */
    protected $command;

    /**
     * Hash for the command
     *
     * @var string
     */
    protected $hash;

    /**
     * The path for the output file
     *
     * @var string
     */
    protected $stdout;

    /**
     * The path for the error output file
     *
     * @var string
     */
    protected $stderr;

    /**
     * Creates a new Command instance
     *
     * @param Command $command
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
        $this->cache = kirby()->cache('lukaskleinschmidt.tasks');
        $this->hash = $command->hash();

        $root = kirby()->root('cache') . '/lukaskleinschmidt/tasks/' . $this->hash;

        $this->stdout = $root . '.stdout';
        $this->stderr = $root . '.stderr';
    }

    /**
     * Improved var_dump() output
     *
     * @return array
     */
    public function __debuginfo(): array
    {
        return array_merge($this->toArray(), [
            'command' => $this->command(),
            'pid'     => $this->pid(),
        ]);
    }

    /**
     * Returns the command instance
     *
     * @return Command
     */
    public function command(): Command
    {
        return $this->command;
    }

    /**
     * Kill the currently running process
     *
     * @return self
     */
    public function kill()
    {
        $pid = $this->pid();

        // Nothing to kill if there is no pid
        if (is_null($pid === true)) {
            return $this;
        }

        Process::kill($pid);

        return $this;
    }

    /**
     * Returns the id for the currently running process
     *
     * @return mixed
     */
    public function pid()
    {
        $pid = $this->cache->get($this->hash);

        // Flush the pid so we cannot kill another process in the future with
        // the exact same pid
        if (is_null($pid) === false && Process::status($pid) === false) {
            $this->cache->remove($this->hash);
            return null;
        }

        return $pid;
    }

    /**
     * Start the process
     *
     * @return self
     */
    public function run()
    {
        if ($this->status() === true) {
            throw new \Exception('Process is already running');
        }

        // Make sure the files exist
        F::write($this->stdout, '');
        F::write($this->stderr, '');

        $pid = Process::run($this->command, $this->stdout, $this->stderr);

        // Cache the processes id
        $this->cache->set($this->hash, $pid);

        return $this;
    }

    /**
     * Check whether a process is still running
     *
     * @return bool
     */
    public function status(): bool
    {
        $pid = $this->pid();

        // Nothing to check if there is no pid
        if (is_null($pid) === true) {
            return false;
        }

        return Process::status($pid);
    }

    /**
     * Returns the process output
     *
     * @return string
     */
    public function stdout(): string
    {
        return F::read($this->stdout) ?: '';
    }

    /**
     * Returns the process error output
     *
     * @return string
     */
    public function stderr()
    {
        return F::read($this->stderr) ?: '';
    }

    /**
     * Converts the object into a nicely readable array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status(),
            'stdout' => $this->stdout(),
            'stderr' => $this->stderr()
        ];
    }
}
