# WIP: Kirby Terminal

## Configuration

```php
<?php

return [
    'lukaskleinschmidt.terminal.endpoint' => 'terminal',
    'lukaskleinschmidt.terminal.scripts' => [
        'deploy' => function () {
            $root = $this->kirby()->root('content');
            $path = $this->diruri();

            return script("rsync -avz $root/$path user@remote.com:/var/www/html/content/$path --delete");
        }
    ],
    'lukaskleinschmidt.terminal.gate' => function ($user) {
        return in_array($user->email(), [
            //
        ]);
    }
]
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
confirm  |          | –       | ...
delay    | `int`    | `1000`  | ...
headline |          | –       | The headline for the section
help     |          | –       | Sets the help text
script   | `string` | –       | ...
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
