<?php

namespace LukasKleinschmidt\Terminal;

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
        }

        return shell_exec("kill -TERM -$pid");
    }

    /**
     * Run a platform agnostic background process
     *
     * @param  Script  $script
     * @param  string  $stdout
     * @param  string  $stderr
     * @return int
     */
    public static function run(Script $script, string $stdout = '/dev/null', ?string $stderr = null): int
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
    protected static function runUnix(Script $script, string $stdout, ?string $stderr = null): int
    {
        // Write the actual pid to file and start a new session to make sure we
        // are able to kill any potential child processes as well
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        $cmd  = $script->cmd();
        $cmd  = "setsid $cmd & echo $! > $path";

        static::spawn(
            $cmd,
            $script->cwd(),
            $stdout,
            $stderr
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
     * @return int
     */
    protected static function runWindows(Script $script, string $stdout, ?string $stderr = null): int
    {
        $cmd = $script->cmd();
        $cmd = "start /b $cmd";

        // On windows the pid is the process id of the spawned shell so we have
        // to do some more magic to get the real pid
        $ppid = static::spawn(
            $cmd,
            $script->cwd(),
            $stdout,
            $stderr
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
     * @return int
     */
    protected static function spawn(string $cmd, string $cwd, string $stdout, ?string $stderr = null): int
    {
        if (is_null($stderr) === true) {
            $stderr = $stdout;
        }

        // Execute the script
        $process = proc_open($cmd, [
            ['pipe', 'r'],
            ['file', $stdout, 'a'],
            ['file', $stderr, 'a'],
        ], $pipes, $cwd);

        if (is_resource($process) === false) {
            throw new \Exception('Unable to spawn process');
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
