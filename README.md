# WIP: Kirby Terminal

### Scripts
You can define almost any scripts you would normally run from a terminal. A script can be defined as a simple string. If you need more control you can define your script as a callback. The callback is expected to return either a `string` or a `Script` object. The closure of the callback is bound to the scripts section model.

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

            return "rsync -avz --relative $source $target --delete";
        }
    ]
];
```

### Gate
You may want to restrict access to some scripts. You can do this by adding a gate callback to your config file. The callback is expected to return either `true` or `false`. Within the callback you have access to the authenticated user. In addition the closure is bound to the section object allowing you to make more fine grained decisions. The following two examples will help you getting started.

```php
<?php

return [
    'lukaskleinschmidt.terminal.gate' => function ($user) {
        return in_array($this->email(), [
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

### Endpoint
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
confirm  |          | –       | Sets the confirmation dialog text
delay    | `int`    | `1000`  | Sets the polling delay
headline |          | –       | The headline for the section
help     |          | –       | Sets the help text
script   | `string` | –       | Sets the executable script
start    | `string` | `Start` | Sets the start button text
stop     | `string` | `Stop`  | Sets the stop button text
theme    | `string` | –       | Terminal color theme. Available theme: `dark`

### Confirmation dialog

```yml
# Basic confirmation dialog
confirm: Are you sure you are ready for this?

# Advanced confirmation dialog
confirm:
  button: So ready
  icon: wand
  size: large
  theme: negative
  text: Are you sure you are ready for this?
```
