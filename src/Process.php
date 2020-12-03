<?php

namespace LukasKleinschmidt\Terminal;

use Exception;

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
     * Get enviroment variables
     *
     * @return array
     */
    protected static function env(): array
    {
        $env = [];

        foreach ($_SERVER as $key => $value) {
            if (is_string($value) && false !== $value = getenv($key)) {
                $env[$key] = $value;
            }
        }

        foreach ($_ENV as $key => $value) {
            if (is_string($value)) {
                $env[$key] = $value;
            }
        }

        return $env;
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
        }

        return shell_exec("kill -- -$(ps -o pgid= $pid | grep -o [0-9]*)");
    }

    /**
     * Run a platform agnostic background process
     *
     * @param  Script  $script
     * @param  string  $stdout
     * @param  string  $stderr
     * @param  array   $env
     * @return int
     */
    public static function run(
        Script $script,
        string $stdout = '/dev/null',
        ?string $stderr = null,
        array $env = []
    ): int {
        if (static::isWindows()) {
            return static::runWindows($script, $stdout, $stderr, $env);
        }

        return static::runUnix($script, $stdout, $stderr, $env);
    }

    /**
     * Run a background process on unix-like platforms
     *
     * @param  Script  $script
     * @param  string  $stdout
     * @param  string  $stderr
     * @param  array   $env
     * @return int
     */
    protected static function runUnix(
        Script $script,
        string $stdout,
        ?string $stderr = null,
        array $env = []
    ): int {
        // Write the actual pid to file and start a new session to make sure we
        // are able to kill any potential child processes as well
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        $cmd  = $script->cmd();
        $cmd  = "$cmd & echo $! > $path";

        static::spawn(
            $cmd,
            $script->cwd(),
            $stdout,
            $stderr,
            $env
        );

        $size = filesize($path);
        $pid  = fread($file, $size);

        fclose($file);

        return (int) $pid;
    }

    /**
     * Run a background process on windows platforms
     *
     * @param  Script  $script
     * @param  string  $stdout
     * @param  string  $stderr
     * @param  array   $env
     * @return int
     */
    protected static function runWindows(
        Script $script,
        string $stdout,
        ?string $stderr = null,
        array $env = []
    ): int {
        $cmd = $script->cmd();
        $cmd = "start /b $cmd";

        // On windows the pid is the process id of the spawned shell so we have
        // to do some more magic to get the real pid
        $ppid = static::spawn(
            $cmd,
            $script->cwd(),
            $stdout,
            $stderr,
            $env
        );

        // Find correct pid
        $output = shell_exec("wmic process get parentprocessid, processid | find \"$ppid\"");
        $result = array_filter(explode(' ', $output));

        array_pop($result);

        return (int) end($result);
    }

    /**
     * Spawn a new process
     *
     * @param  string   $cmd
     * @param  string   $cwd
     * @param  string   $stdout
     * @param  string   $stderr
     * @param  array    $env
     * @throws Exception
     * @return int
     */
    protected static function spawn(
        string $cmd,
        string $cwd,
        string $stdout,
        ?string $stderr = null,
        array $env = []
    ): int {
        if (is_null($stderr) === true) {
            $stderr = $stdout;
        }

        $env = array_merge(static::env(), $env);

        // Execute the script
        $process = proc_open($cmd, [
            ['pipe', 'r'],
            ['file', $stdout, 'a'],
            ['file', $stderr, 'a'],
        ], $pipes, $cwd, $env);

        if (is_resource($process) === false) {
            throw new Exception('Unable to spawn process');
        }

        $status = proc_get_status($process);

        proc_close($process);

        return (int) $status['pid'];
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
