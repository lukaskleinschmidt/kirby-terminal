<?php

use LukasKleinschmidt\Terminal\Script;
use LukasKleinschmidt\Terminal\Terminal;

/**
 * Creates a new Scripts instance
 *
 * @param  mixed  $script
 * @param  mixed  $model
 * @return Scripts
 */
function terminal($script, $model = null): Terminal
{
    // Try to find a registered script by name
    $scripts = kirby()->option('lukaskleinschmidt.terminal.scripts');
    $script  = $scripts[$script] ?? $script;

    // Create a new script object
    if (is_string($script) == true) {
        $script = script($script);
        return new Terminal($script);
    }

    // Pass down valid scripts
    if (is_a($script, 'LukasKleinschmidt\Terminal\Script') === true) {
        return new Terminal($script);
    }

    // Create a script with a closure
    if (is_callable($script) === true) {
        return new Terminal($script->call($model));
    }

    throw new \Exception('Terminal could not be created');
}

/**
 * Creates a new Script instance
 *
 * @param  string  $cmd
 * @param  string  $cwd
 * @return Script
 */
function script(string $cmd, string $cwd = null): Script
{
    return new Script($cmd, $cwd ?? kirby()->root('index'));
}
