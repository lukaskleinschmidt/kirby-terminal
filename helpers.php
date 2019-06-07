<?php

use LukasKleinschmidt\Tasks\Script;
use LukasKleinschmidt\Tasks\Task;

/**
 * Creates a new Task instance
 *
 * @param  mixed  $script
 * @param  mixed  $model
 * @return Task
 */
function task($script, $model = null): Task
{
    // Try to find a registered script by name
    try {
        $scripts = kirby()->option('lukaskleinschmidt.tasks.scripts');
        $script = $scripts[$script] ?? null;
    } catch (\Exception $e) {

    }

    // Passthru valid scripts
    if (is_a($script, 'LukasKleinschmidt\Tasks\Script') === true) {
        return new Task($script);
    }

    // Create a new script object
    if (is_string($script) == true) {
        return new Task(script($script));
    }

    // Create a script with a closure
    if (is_callable($script) === true) {
        return new Task($script->call($model));
    }

    throw new \Exception('Task could not be created');
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
