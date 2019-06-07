<?php

use Kirby\Toolkit\I18n;

@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/helpers.php';

Kirby::plugin('lukaskleinschmidt/tasks', [
    'options' => [
        'cache' => true,
        'endpoint' => 'task',
        'commands' => [
            // 'deploy' => function ($model) {
            //     $root = kirby()->root('content');
            //     $path = $model->diruri();
            //
            //     return command("rsync -avz --chown=www-data:www-data $root/$path root@46.101.99.252:/var/www/html/natucate/content/$path --delete");
            // },
            // 'test-string' => 'npm -v',
            // 'test-closure' => function ($model) {
            //     return command('npm -v');
            // },
            // 'test-command' => command('npm -v'),

            'npm' => command('npm run build --color=always', kirby()->root('index') . '/test'),
            'php' => command('php -f test.php', __DIR__),
        ]
    ],
    'sections' => [
        'task' => [
            'props' => [
                'command' => function (string $command = null): string {
                    return $command;
                },
                'delay' => function ($delay = 1000): int {
                    return $delay;
                },
                'text' => function ($text = null): string {
                    return I18n::translate($text, $text);
                },
            ],
            'computed' => [
                'endpoint' => function (): string {

                    // Until https://github.com/getkirby/kirby/issues/1791 is
                    // fixed we have to define the default value here
                    // return option('lukaskleinschmidt.tasks.endpoint');
                    return option('lukaskleinschmidt.tasks.endpoint', 'task');
                },
                'path' => function (): string {
                    return $this->model()->id() ?? '';
                },
                'status' => function (): array {
                    return task($this->command(), $this->model())->toArray();
                }
            ]
        ]
    ],
    'api' => [
        'routes' => function ($kirby) {

            // Until https://github.com/getkirby/kirby/issues/1791 is fixed we
            // have to define the default value here
            // $endpoint = $kirby->option('lukaskleinschmidt.tasks.endpoint');
            $endpoint = $kirby->option('lukaskleinschmidt.tasks.endpoint', 'task');
            $pattern = $endpoint . '/(:any)/(:all?)';

            return [
                [
                    'pattern' => $pattern,
                    'method'  => 'DELETE',
                    'auth'    => true,
                    'action'  => function ($command, $path = null): array {
                        return task($command, $path)->kill()->toArray();
                    }
                ],
                [
                    'pattern' => $pattern,
                    'method'  => 'GET',
                    'auth'    => true,
                    'action'  => function ($command, $path = null): array {
                        return task($command, $path)->toArray();
                    }
                ],
                [
                    'pattern' => $pattern,
                    'method'  => 'POST',
                    'auth'    => true,
                    'action'  => function ($command, $path = null): array {
                        return task($command, $path)->run()->toArray();
                    }
                ]
            ];
        },
    ]
]);
