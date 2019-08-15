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
     * Hash for the script
     *
     * @var string
     */
    protected $hash;

    /**
     * The script
     *
     * @var Script
     */
    protected $script;

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
     * Creates a new Task instance
     *
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script = $script;
        $this->cache = kirby()->cache('lukaskleinschmidt.tasks');
        $this->hash = $script->hash();

        $prefix = $this->cache->options()['prefix'] ?? 'lukaskleinschmidt/tasks';
        $root = kirby()->root('cache') . '/' . $prefix . '/' . $this->hash;

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
            'script' => $this->script(),
            'pid'    => $this->pid()
        ]);
    }

    /**
     * Returns the script instance
     *
     * @return Script
     */
    public function script(): Script
    {
        return $this->script;
    }

    /**
     * Kill the currently running process
     *
     * @return self
     */
    public function kill(): self
    {
        $pid = $this->pid();

        // Nothing to kill if there is no pid
        if (is_null($pid) === true) {
            return $this;
        }

        Process::kill($pid);

        return $this;
    }

    /**
     * Returns the id for the currently running process
     *
     * @return int|null
     */
    public function pid(): ?int
    {
        $pid = $this->cache->get($this->hash);

        // Flush the pid so we cannot kill another process in the future with
        // the exact same pid
        if (is_null($pid) === false && Process::status($pid) === false) {
            // $this->cache->remove($this->hash);
            return null;
        }

        return $pid;
    }

    /**
     * Start the process
     *
     * @return self
     */
    public function run(): self
    {
        if ($this->status() === true) {
            throw new \Exception('Process is already running');
        }

        // Make sure the files exist
        F::write($this->stdout, '');
        F::write($this->stderr, '');

        $pid = Process::run($this->script, $this->stdout, $this->stderr, true);

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
    public function stderr(): string
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
