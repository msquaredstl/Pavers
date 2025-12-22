<?php

declare(strict_types=1);

namespace Pavers;

class Autoloader
{
    /**
     * Register the autoloader.
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload classes within the Pavers namespace.
     */
    public static function autoload(string $class): void
    {
        $prefix = __NAMESPACE__ . '\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
        $file = PAVERS_PLUGIN_PATH . 'includes/' . $relativePath . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
