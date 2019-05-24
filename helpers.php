<?php

use LukasKleinschmidt\Tasks\Command;
use LukasKleinschmidt\Tasks\Task;

/**
 * Creates a new Task instance
 *
 * @param  mixed  $name
 * @param  mixed  $model
 * @return Task
 */
function task($command, $model = null): Task
{
    // Try to find a registered command by name
    try {
        $commands = kirby()->option('lukaskleinschmidt.tasks.commands');
        $command = $commands[$command] ?? null;
    } catch (\Exception $e) {

    }

    // Passthru valid commands
    if (is_a($command, 'LukasKleinschmidt\Tasks\Command') === true) {
        return new Task($command);
    }

    // Create a new command object
    if (is_string($command) == true) {
        return new Task(command($command));
    }

    // Create a command with an closure
    if (is_callable($command) === true) {

        // Try to resolve the model where the task was executed from
        if (is_null($model) === true) {
            $model = site();
        } else if (is_string($model) === true) {
            $parts = explode('/', $model);

            // Find page or draft recursively
            foreach ($parts as $path) {
                $page = ($page ?? site())->findPageOrDraft($path);
            }

            $model = $page;
        }

        return new Task($command($model));
    }

    throw new \Exception('Task could not be created');
}

/**
 * Creates a new Command instance
 *
 * @param  string  $cmd
 * @param  string  $cwd
 * @return Command
 */
function command(string $cmd, string $cwd = null): Command
{
    return new Command($cmd, $cwd ?? kirby()->root('index'));
}
