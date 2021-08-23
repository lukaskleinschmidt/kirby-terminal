<?php

namespace LukasKleinschmidt\Terminal;

use Kirby\Toolkit\Str;

class Script
{
    /**
     * Script bindings
     *
     * @var array
     */
    protected $bindings;

    /**
     * The command
     *
     * @var string
     */
    protected $cmd;

    /**
     * Anticipated working directory
     *
     * @var string
     */
    protected $cwd;

    /**
     * Creates a new Script instance
     *
     * @param string $cmd
     * @param string $cwd
     * @param array $bindings
     */
    public function __construct(string $cmd, string $cwd = null, array $bindings = [])
    {
        $this->cmd = $cmd;
        $this->cwd = $cwd ?? getcwd();
        $this->bindings = $bindings;
    }

    /**
     * Improved var_dump() output
     *
     * @return array
     */
    public function __debuginfo(): array
    {
        return array_merge($this->toArray(), [
            'hash' => $this->hash()
        ]);
    }

    /**
     * Returns the cmd
     *
     * @return string
     */
    public function cmd(): string
    {
        return Str::template($this->cmd, $this->bindings);
    }

    /**
     * Returns the anticipated working directory
     *
     * @return string
     */
    public function cwd(): string
    {
        return $this->cwd;
    }

    /**
     * Returns a hash for the script
     *
     * @return string
     */
    public function hash(): string
    {
        return md5(json_encode($this->toArray()));
    }

    /**
     * Returns the command
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->cmd;
    }

    /**
     * Converts the object into a nicely readable array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'cmd' => $this->cmd,
            'cwd' => $this->cwd
        ];
    }
}
