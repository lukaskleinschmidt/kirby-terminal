<?php

use LukasKleinschmidt\Terminal\Script;
use LukasKleinschmidt\Terminal\Terminal;

/**
 * Creates a new Scripts instance
 *
 * @param  mixed  $script
 * @param  mixed  $model
 * @param  array  $payload
 * @throws Exception
 * @return Terminal
 */
function terminal($script, $model = null, array $payload = []): Terminal
{
    // Try to find a registered script by name
    $scripts = kirby()->option('lukaskleinschmidt.terminal.scripts');
    $script  = $scripts[$script] ?? null;

    // Create a script with a closure
    if (is_callable($script) === true) {
        $script = $script->call($model, $payload);
    }

    // Create a new script object from string
    if (is_string($script) === true) {
        $script = script($script);
    }

    // Create a new Terminal
    if (is_a($script, 'LukasKleinschmidt\Terminal\Script') === true) {
        return new Terminal($script);
    }

    throw new Exception('Terminal could not be created');
}

/**
 * Creates a new Script instance
 *
 * @param  string  $cmd
 * @param  string  $cwd
 * @param  array  $bindings
 * @return Script
 */
function script(string $cmd, string $cwd = null, array $bindings = []): Script
{
    return new Script($cmd, $cwd ?? kirby()->root('index'), $bindings);
}
