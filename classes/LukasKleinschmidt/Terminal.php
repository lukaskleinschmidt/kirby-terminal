<?php

namespace LukasKleinschmidt\Terminal;

use Kirby\Toolkit\F;

class Terminal
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
     * Creates a new Terminal instance
     *
     * @param Script $script
     */
    public function __construct(Script $script)
    {
        $this->script = $script;
        $this->cache = kirby()->cache('lukaskleinschmidt.terminal');
        $this->hash = $script->hash();

        $prefix = $this->cache->options()['prefix'] ?? 'lukaskleinschmidt/terminal';
        $root = kirby()->root('cache') . '/' . $prefix . '/' . $this->hash;

        $this->stdout = $root . '.stdout';
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
     * Start the process
     *
     * @return self
     */
    public function start(): self
    {
        if ($this->status() === true) {
            throw new \Exception('Process is already running');
        }

        // Make sure the file exist
        F::write($this->stdout, '');

        $pid = Process::run($this->script, $this->stdout);

        // Cache the processes id
        $this->cache->set($this->hash, $pid);

        return $this;
    }

    /**
     * Stop the currently running process
     *
     * @return self
     */
    public function stop(): self
    {
        $pid = $this->pid();

        // Nothing to stop if there is no pid
        if (is_null($pid) === true) {
            return $this;
        }

        Process::kill($pid);

        // Flush the pid so we cannot kill another process in the future with
        // the exact same pid
        $this->cache->remove($this->hash);

        return $this;
    }

    /**
     * Returns the id for the currently running process
     *
     * @return int|null
     */
    public function pid(): ?int
    {
        return $this->cache->get($this->hash);
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
     * Converts the object into a nicely readable array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status(),
            'stdout' => $this->stdout()
        ];
    }
}
