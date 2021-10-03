<?php

use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Http\Response;
use Kirby\Toolkit\I18n;
use Kirby\Exception\PermissionException;
use Kirby\Form\Form;

@include_once __DIR__ . '/vendor/autoload.php';

App::plugin('lukaskleinschmidt/terminal', [
    'options' => [
        'cache' => true,
        'endpoint' => 'terminal',
        'scripts' => [],
    ],
    'sections' => [
        'terminal' => [
            'mixins' => [
                'headline',
                'help',
            ],
            'props' => [
                'delay' => function ($delay = 1000) {
                    return $delay;
                },
                'dialog' => function ($dialog = null) {
                    $options = [];

                    // Disable the dialog
                    if (! $dialog) {
                        return false;
                    }

                    // Pass down any additional params
                    if (isset($dialog['text']) || isset($dialog['fields'])) {
                        $options = $dialog;
                    }

                    // Normalize options
                    if (is_array($dialog) === false) {
                        $options['text'] = $dialog;
                    }

                    // Localize text
                    if ($value = $dialog['text'] ?? null) {
                        $options['text'] = I18n::translate($value, $value);
                    }

                    // Localize button
                    if ($value = $dialog['button'] ?? null) {
                        $options['button'] = I18n::translate($value, $value);
                    }

                    return $options;
                },
                'start' => function ($start = null) {
                    return I18n::translate($start, $start);
                },
                'stop' => function ($stop = null) {
                    return I18n::translate($stop, $stop);
                },
                'theme' => function ($theme = null) {
                    return $theme;
                },
            ],
            'computed' => [
                'endpoint' => function () {
                    return option('lukaskleinschmidt.terminal.endpoint');
                },
                'gate' => function () {
                    $gate = option('lukaskleinschmidt.terminal.gate', true);

                    if ($gate !== true && $gate instanceof Closure) {
                        $user = $this->kirby()->auth()->user();
                        $gate = $gate->call($this, $user);
                    }

                    return $gate;
                },
                'status' => function () {
                    if ($this->gate === true) {
                        return terminal($this->script(), $this->model())->toArray();
                    }

                    return [
                        'status' => false,
                        'stdout' => '',
                    ];
                },
                'start' => function () {
                    return $this->start ?? t('lukaskleinschmidt.terminal.start');
                },
                'stop' => function () {
                    return $this->stop ?? t('lukaskleinschmidt.terminal.stop');
                },

                // The order in which computed props are registered is important
                // when you need them as a dependency
                'dialog' => function () {
                    if (! $this->dialog) {
                        return false;
                    }

                    return array_merge([
                        'button' => $this->start,
                        'icon'   => 'check',
                        'size'   => 'small',
                        'theme'  => 'positive',
                        'text'   => '',
                        'fields' => null,
                    ], $this->dialog);
                },
            ],
            'toArray' => function () {
                return [
                    'options' => [
                        'delay'    => $this->delay,
                        'dialog'   => $this->dialog,
                        'endpoint' => $this->endpoint,
                        'gate'     => $this->gate,
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

                $payload = get('payload', []);

                if ($kirby->request()->is('POST') && $dialog = $this->dialog()) {
                    $form = new Form([
                        'fields' => $dialog['fields'],
                        'input' => $payload,
                        'model' => $this->model(),
                    ]);

                    if ($form->isInvalid() === true) {
                        throw new InvalidArgumentException([
                            'fallback' => 'Invalid form with errors',
                            'details'  => $form->errors(),
                            'key'      => 'terminalDialog',
                        ]);
                    }
                }

                $terminal = terminal($this->script(), $this->model(), $payload);

                if ($this->gate() !== true) {
                    throw new PermissionException;
                }

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

                return Response::json($body, null, null, [
                    'Content-Length' => $size,
                ]);
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
        }
    ]
]);
