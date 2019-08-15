<?php

use Kirby\Http\Response;
use Kirby\Toolkit\I18n;

@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/helpers.php';

Kirby::plugin('lukaskleinschmidt/tasks', [
    'options' => [
        'cache' => true,
        'endpoint' => 'task',
        'scripts' => [
            'deploy' => function () {
                $root = $this->kirby()->root('content');
                $path = $this->model()->diruri();

                // return script("rsync -avz --chown=www-data:www-data $root/$path root@46.101.99.252:/var/www/html/natucate/content/$path --delete");
                return script("rsync -avz --chown=www-data:www-data $root/$path $root/../content-save/$path --delete");
            },
            'npm' => script('npm run build', kirby()->root('index') . '/test'),
            'php' => script('php -f test.php', __DIR__),
        ]
    ],
    'sections' => [
        'task' => [
            'mixins' => [
                'headline',
                'help',
            ],
            'props' => [
                'delay' => function ($delay = 1000): int {
                    return $delay;
                }
            ],
            'computed' => [
                'endpoint' => function (): string {
                    return option('lukaskleinschmidt.tasks.endpoint');
                },
                'status' => function (): array {
                    return task($this->run(), $this->model())->toArray();
                }
            ]
        ]
    ],
    'api' => [
        'routes' => function ($kirby) {
            $task = function () use ($kirby) {
                $task = task($this->run(), $this->model());

                if ($kirby->request()->is('POST') && $action = get('action')) {
                    switch ($action) {
                        case 'kill':
                            $task->kill();
                            break;

                        case 'run':
                            $task->run();
                            break;
                    }
                }

                $body = json_encode($task->toArray());
                $size = strlen($body);

                echo Response::json($body, null, null, [
                    'Content-Length' => $size,
                ]);

                return true;
            };

            // Use the desired api endpoint
            $endpoint = $kirby->option('lukaskleinschmidt.tasks.endpoint');

            return [
                [
                    'pattern' => "(:all)/files/(:any)/$endpoint/(:any)",
                    'method'  => 'GET|POST',
                    'auth'    => true,
                    'action'  => function (string $path, string $filename, string $sectionName) use ($task) {
                        if ($section = $this->file($path, $filename)->blueprint()->section($sectionName)) {
                            return $task->call($section);
                        }
                    }
                ],
                [
                    'pattern' => "pages/(:any)/$endpoint/(:any)",
                    'method'  => 'GET|POST',
                    'auth'    => true,
                    'action'  => function (string $id, string $sectionName) use ($task) {
                        if ($section = $this->page($id)->blueprint()->section($sectionName)) {
                            return $task->call($section);
                        }
                    }
                ],
                [
                    'pattern' => "site/$endpoint/(:any)",
                    'method'  => 'GET|POST',
                    'auth'    => true,
                    'action'  => function (string $sectionName) use ($task) {
                        if ($section = $this->site()->blueprint()->section($sectionName)) {
                            return $task->call($section);
                        }
                    }
                ],
                [
                    'pattern' => "users/(:any)/$endpoint/(:any)",
                    'method'  => 'GET|POST',
                    'auth'    => true,
                    'action'  => function (string $id, string $sectionName) use ($task) {
                        if ($section = $this->user($id)->blueprint()->section($sectionName)) {
                            return $task->call($section);
                        }
                    }
                ]
            ];
        },
    ]
]);
