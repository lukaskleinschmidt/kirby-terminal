<?php

use Kirby\Http\Response;
use Kirby\Toolkit\I18n;
use Kirby\Exception\PermissionException;

@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/helpers.php';

Kirby::plugin('lukaskleinschmidt/terminal', [
    'options' => [
        'cache' => true,
        'endpoint' => 'terminal',
        'scripts' => [
            'deploy' => function () {
                $root = $this->kirby()->root('content');
                $path = $this->model()->diruri();

                return script("rsync -avz --chown=www-data:www-data $root/$path $root/../content-copy/$path --delete");
            },
            'php' => script('php -f test.php', __DIR__),
            'npm' => script('npm run serve', kirby()->root('index') . '/test'),
        ],
        // 'gate' => function ($user) {
        //     return in_array($user->email(), [
        //         //
        //     ]);
        // }
    ],
    'sections' => [
        'terminal' => [
            'mixins' => [
                'headline',
                'help',
            ],
            'props' => [
                'delay' => function (int $delay = 1000) {
                    return $delay;
                },
                'start' => function ($start = null) {
                    return I18n::translate($start, $start);
                },
                'stop' => function ($stop = null) {
                    return I18n::translate($stop, $stop);
                },
                'theme' => function (string $theme = null) {
                    return $theme;
                }
            ],
            'computed' => [
                'endpoint' => function () {
                    return option('lukaskleinschmidt.terminal.endpoint');
                },
                'status' => function () {
                    return terminal($this->script(), $this->model())->toArray();
                }
            ],
            'toArray' => function () {
                return [
                    'options' => [
                        'delay'    => $this->delay,
                        'endpoint' => $this->endpoint,
                        'headline' => $this->headline,
                        'help'     => $this->help,
                        'start'    => $this->start,
                        'stop'     => $this->stop,
                        'theme'    => $this->theme,
                    ],
                    'terminal' => $this->status,
                ];
            }
        ]
    ],
    'translations' => [
        'en' => [
            'lukaskleinschmidt.terminal.start' => 'Start',
            'lukaskleinschmidt.terminal.stop'  => 'Stop',
        ],
        'de' => [
            'lukaskleinschmidt.terminal.start' => 'Start',
            'lukaskleinschmidt.terminal.stop'  => 'Stop',
        ]
    ],
    'api' => [
        'routes' => function ($kirby) {
            $terminal = function () use ($kirby) {
                $terminal = terminal($this->script(), $this->model());

                if ($kirby->request()->is('POST') && $action = get('action')) {
                    switch ($action) {
                        case 'stop':
                            $terminal->stop();
                            break;

                        case 'start':
                            $terminal->start();
                            break;
                    }
                }

                $body = json_encode($terminal->toArray());
                $size = strlen($body);

                echo Response::json($body, null, null, [
                    'Content-Length' => $size,
                ]);

                return true;
            };

            // Use the desired api endpoint
            $endpoint = $kirby->option('lukaskleinschmidt.terminal.endpoint');

            return [
                [
                    'pattern' => "(:all)/files/(:any)/$endpoint/(:any)",
                    'method'  => 'GET|POST',
                    'auth'    => true,
                    'action'  => function (string $path, string $filename, string $sectionName) use ($terminal) {
                        if ($section = $this->file($path, $filename)->blueprint()->section($sectionName)) {
                            return $terminal->call($section);
                        }
                    }
                ],
                [
                    'pattern' => "pages/(:any)/$endpoint/(:any)",
                    'method'  => 'GET|POST',
                    'auth'    => true,
                    'action'  => function (string $id, string $sectionName) use ($terminal) {
                        if ($section = $this->page($id)->blueprint()->section($sectionName)) {
                            return $terminal->call($section);
                        }
                    }
                ],
                [
                    'pattern' => "site/$endpoint/(:any)",
                    'method'  => 'GET|POST',
                    'auth'    => true,
                    'action'  => function (string $sectionName) use ($terminal) {
                        if ($section = $this->site()->blueprint()->section($sectionName)) {
                            return $terminal->call($section);
                        }
                    }
                ],
                [
                    'pattern' => "users/(:any)/$endpoint/(:any)",
                    'method'  => 'GET|POST',
                    'auth'    => true,
                    'action'  => function (string $id, string $sectionName) use ($terminal) {
                        if ($section = $this->user($id)->blueprint()->section($sectionName)) {
                            return $terminal->call($section);
                        }
                    }
                ]
            ];
        },
    ]
]);
