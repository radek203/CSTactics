<?php

declare(strict_types=1);

spl_autoload_register(function (string $name) {
    $name = str_replace(['CSApp\\', '\\'], ['', '/'], $name);
    $path = 'src/' . $name . '.php';

    require_once($path);
});

const ENABLE_DEBUG = TRUE;
const ENABLE_DEV = TRUE;

require_once('src/debug.php');

return ENABLE_DEV ? require_once('src/configDev.php') : require_once('src/configMain.php');
