# WIP: Kirby Terminal


```php
<?php

return [
    'lukaskleinschmidt.terminal.scripts' => [
        'deploy' => function () {
            $root = $this->kirby()->root('content');
            $path = $this->model()->diruri();

            return script("rsync -avz $root/$path ssh_user@ssh_host:$path --delete");
        },
    ]
]
```

```yml
sections:
  terminal:
    headline: Terminal
    type: terminal
    script: deploy
```

## Confirm dialog
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

## All available options


Property | Type | Default | Description
:-- | :-- | :-- | :--
confirm | | – | ....
delay | `int` | 1000 | ....
headline | | – | The headline for the section
help | | – | Sets the help text
script | `string` | – | ....
start | `string` | `Start` | ....
stop | `string` | `Stop` | ....
theme | `string` | – | `dark`
