<?php

namespace LukasKleinschmidt\Tasks;

class Process
{
    /**
     * Check if we are running on windows
     *
     * @return bool
     */
    public static function isWindows(): bool
    {
        return substr(strtoupper(PHP_OS), 0, 3) === 'WIN';
    }

    /**
     * Kill a process by pid
     *
     * @param  int    $pid
     * @return string
     */
    public static function kill(int $pid)
    {
        if (static::isWindows()) {
            return shell_exec("taskkill /pid $pid -t -f");
        } else {
            return shell_exec("kill -9 $pid");
        }
    }

    /**
     * Run a platform agnostic background process
     *
     * @param  Command $command
     * @param  string  $stdout
     * @param  string  $stderr
     * @return int
     */
    public static function run(Command $command, string $stdout = '/dev/null', string $stderr = '/dev/null'): int
    {
        if (static::isWindows()) {
            return static::runWindows($command, $stdout, $stderr);
        } else {
            return static::runNix($command, $stdout, $stderr);
        }
    }

    /**
     * Run a background process on unix-like platforms
     *
     * @param  Command $command
     * @param  string  $stdout
     * @param  string  $stderr
     * @return int
     */
    public static function runNix(Command $command, string $stdout, string $stderr): int
    {
        // Change working directory
        chdir($command->cwd());

        // Execute the command
        return (int) shell_exec("$command > $stdout 2> $stderr & echo $!");
    }

    /**
     * Run a background process on windows platforms
     *
     * @param  Command $command
     * @param  string  $stdout
     * @param  string  $stderr
     * @return int
     */
    public static function runWindows(Command $command, string $stdout, string $stderr): int
    {
        // Execute the command
        $process = proc_open("start /b $command", [
            ['pipe', 'r'],
            ['file', $stdout, 'w'],
            ['file', $stderr, 'w'],
        ], $pipes, $command->cwd());

        if (is_resource($process) === false) {
            throw new \Exception('Unable to launch a background process');
        }

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
        } else {
            return posix_getsid($pid) !== false;
        }
    }
}
