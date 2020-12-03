# Kirby Terminal
Manage background processes from the panel. Define scripts like you might already be familiar with from `npm`. Start or stop predefined scripts and monitor the output directly in the panel.

## Commercial Usage
This plugin is free. Please consider to [make a donation](https://www.paypal.me/lukaskleinschmidt) if you use it in a commercial project.

![Terminal Preview](http://github.kleinschmidt.at/kirby-terminal/preview.gif)

## Installation

### Download
Download and copy this repository to `/site/plugins/terminal`.

### Git submodule
```
git submodule add https://github.com/lukaskleinschmidt/kirby-terminal.git site/plugins/terminal
```

### Composer
```
composer require lukaskleinschmidt/kirby-terminal
```

## Define Scripts
You are able to run almost any scripts or commands you would normally run from a terminal. A script can be defined as a simple string or if you need more control you can define your script as a callback. The callback is expected to return either a `string` or a `Script` object. The closure of the callback is bound to the scripts section model.

```php
<?php

return [
    'lukaskleinschmidt.terminal.scripts' => [
        'hello-world' => 'echo "Hello World!"',
        'list-index' => function () {
            return 'ls';
        },
        'list-content' => function () {
            $cwd = $this->kirby()->root('content') . '/' . $this->diruri();

            // Set the current working directory for the script
            return script('ls', $cwd);
        },
    ]
];
```

To get a better understanding of what is possible you can add those three scripts to your config and simply throw them into the `site` or a `page` blueprint.

```yml
sections:
  hello-world:
    type: terminal
    script: hello-world

  list-index:
    type: terminal
    script: list-index

  list-content:
    type: terminal
    script: list-content
```

The example `deploy` script works for the `site` or a `page` blueprint. Using it in the `site` blueprint will deploy the whole content folder. Using it in a `page` blueprint will only deploy the page and the corresponding subtree.

```php
<?php

return [
    'lukaskleinschmidt.terminal.scripts' => [
        'deploy' => function () {
            $source = $this->kirby()->root('content') . '/\./' . $this->diruri();
            $target = 'shh_user@example.com:/var/www/html/content';

            // If you are not 100% certain you have the right path on the remote
            // server then test this command without the --delete flag first
            return "rsync --delete --relative -avz $source $target";
        }
    ]
];
```

## Permissions
You may want to restrict access to some scripts. You can do this by adding a gate callback to your config file. The callback is expected to return either `true` or `false`. Within the callback you have access to the authenticated user. In addition the closure is bound to the section object allowing you to make more fine grained decisions. The following two examples will help you getting started.

```php
<?php

return [
    'lukaskleinschmidt.terminal.gate' => function ($user) {
        return in_array($user->email(), [
            'user@example.com'
        ]);
    }
];
```

```php
<?php

return [
    'lukaskleinschmidt.terminal.gate' => function ($user) {
        $permissions = [
            'user@example.com' => ['deploy']
        ];

        return in_array($this->script(), $permissions[$user->email()] ?? []);
    }
];
```

If you want to disable all scripts for a specific environment set the gate to `false`.

```php
<?php
// config.example.com.php

return [
    'lukaskleinschmidt.terminal.gate' => false,
];
```

## Endpoint
You can change the used API endpoint if you run into any conflicts.

```php
<?php

return [
    'lukaskleinschmidt.terminal.endpoint' => 'custom-terminal-endpoint'
];
```

## Blueprint
```yml
sections:
  terminal:
    headline: Terminal
    type: terminal
    script: deploy
```

### Available options
Property | Type     | Default | Description
:--      | :--      | :--     | :--
confirm  |          | –       | The confirmation text
delay    | `int`    | `1000`  | The polling delay
headline |          | –       | The headline for the section
help     |          | –       | The help text
script   | `string` | –       | The executable script
start    |          | `Start` | The start button text
stop     |          | `Stop`  | The stop button text
theme    | `string` | `light` | The theme of the terminal. Available themes are: `light`, `dark`

### Confirmation dialog
```yml
# Basic confirmation dialog
confirm: Are you sure you are ready for this?

# Advanced confirmation dialog
confirm:
  button: So ready
  icon: wand
  size: medium
  theme: positive
  text: Are you sure you are ready for this?
```

Property | Type     | Default | Description
:--      | :--      | :--     | :--
button   |          | –       | The text for the submit button. Inherits from the `start` property by default
icon     | `string` | `check` | The icon type for the submit button
size     | `string` | `small` | The dialog size. Available sizes are: `small`, `medium`, `large`
text     |          | –       | The confirmation text
theme    |          | –       | The theme of the submit button. Available options: `positive`, `negative`

### Multiple languages
You can provide translations for multiple languages for the `headline`, `help`, `start`, `stop` and `confirm` property. If you are using the advanced confirmation dialog you can also provide translations for the `button` and `text` property.
```yml
confirm:
  en: Are you sure you are ready for this?
  de: Bist du sicher, dass du bereit bist?
```

## License

MIT

## Credits

- [Lukas Kleinschmidt](https://github.com/lukaskleinschmidt)
