<?php

$output = "                        ``                        \n                     .+dMMdo-                     \n                 `:smMms:-odMNy/`                 \n              .+hNMh+.      .+hNMdo-              \n           :smMNy/`             :smMms:`          \n       `/yNMdo-                    .+hMMh+.       \n     odMNy/`                          `:yNMms`    \n    `MM/                                  -MM-    \n    `MM.                                  `NM-    \n    `MM.        .                .        `NM-    \n    `MM.        mmy/`        `:smM`       `NM-    \n    `MM.        mMMMMds:` -odMMMMM`       `NM-    \n    `MM.        sNMMMMMMMNMMMMMMNh`       `NM-    \n    `MM.          .+yNMMMMMMNh+-          `NM-    \n    `MM.        hddddmMMMMMMmddddd`       `NM-    \n    `MM.        mMMMMMMMMMMMMMMMMM`       `NM-    \n    `MM.        ++++++++++++++++++        `NM-    \n    `MM:                                  .MM-    \n    `smMms:                            -odMNy.    \n       .odMNh+.                    `/yNMdo-       \n          `/yNMmo-              -odMNy/`          \n              -odMNy/`      `:yNMms:              \n                 `/yNMdo-.+hMMh+.                 \n                     :smMMms:`                    \n                        ..                        \n";

for ($i = 0; $i < strlen($output); $i++) {
    echo substr($output, $i, 1);
    usleep(10000);
}
