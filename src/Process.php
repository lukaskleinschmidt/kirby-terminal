<?php

namespace LukasKleinschmidt\Tasks;

use resource;

class Process
{
    /**
     * Check if we are running on windows
     *
     * @return bool
     */
    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Kill a process by pid
     *
     * @param  int    $pid
     * @return string
     */
    public static function kill(int $pid): ?string
    {
        if (static::isWindows()) {
            return shell_exec("taskkill /pid $pid -t -f");
        } else {
            return shell_exec("kill -9 $pid");
        }
    }

    /**
     * Spawn a new process
     *
     * @param  string   $cmd
     * @param  string   $cwd
     * @param  string   $stdout
     * @param  string   $stderr
     * @return resource
     */
    protected static function spawn(string $cmd, string $cwd, string $stdout, string $stderr)
    {
        // Execute the script
        $process = proc_open($cmd, [
            ['pipe', 'r'],
            ['file', $stdout, 'w'],
            ['file', $stderr, 'w'],
        ], $pipes, $cwd);

        if (is_resource($process) === false) {
            throw new \Exception('Unable to spawn process');
        }

        return $process;
    }

    /**
     * Run a platform agnostic background process
     *
     * @param  Script  $script
     * @param  string  $stdout
     * @param  string  $stderr
     * @return int
     */
    public static function run(Script $script, string $stdout = '/dev/null', string $stderr = '/dev/null'): int
    {
        if (static::isWindows()) {
            return static::runWindows($script, $stdout, $stderr);
        }

        return static::runUnix($script, $stdout, $stderr);
    }

    /**
     * Run a background process on unix-like platforms
     *
     * @param  Script  $script
     * @param  string  $stdout
     * @param  string  $stderr
     * @return int
     */
    protected static function runUnix(Script $script, string $stdout, string $stderr): int
    {
        // Spawn a new process
        $process = static::spawn(
            $script->toUnix(),
            $script->cwd(),
            $stdout,
            $stderr
        );

        $status = proc_get_status($process);

        proc_close($process);

        return (int) $status['pid'];
    }

    /**
     * Run a background process on windows platforms
     *
     * @param  Script  $script
     * @param  string  $stdout
     * @param  string  $stderr
     * @return int
     */
    protected static function runWindows(Script $script, string $stdout, string $stderr): int
    {
        // Spawn a new process
        $process = static::spawn(
            $script->toWindows(),
            $script->cwd(),
            $stdout,
            $stderr
        );

        $status = proc_get_status($process);

        // On windows the pid is the process id of the spawned shell so we have
        // to do some more magic to get the real pid
        $ppid = $status['pid'];

        proc_close($process);

        // Find correct pid
        $output = shell_exec("wmic process get parentprocessid, processid | find \"$ppid\"");
        $result = array_filter(explode(' ', $output));

        array_pop($result);

        return (int) end($result);
    }

    /**
     * Check whether a process is still running
     *
     * @param  int  $pid
     * @return bool
     */
    public static function status(int $pid): bool
    {
        if (static::isWindows()) {
            $output = shell_exec("wmic process get processid | find \"$pid\"");
            $result = array_filter(explode(' ', $output));

            return count($result) > 0 && $pid == reset($result);
        }

        return posix_getsid($pid) !== false;
    }
}
