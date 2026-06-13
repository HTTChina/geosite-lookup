<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class) {
    $prefix = 'GeoSitePhp\\';
    if (substr($class, 0, strlen($prefix)) !== $prefix) {
        return;
    }

    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
