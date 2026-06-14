<?php

if (!function_exists('load_app_environment')) {
    function load_app_environment(): void
    {
        $envFile = dirname(__DIR__) . '/.env';
        if (!is_file($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $separator = strpos($line, '=');
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($line, 0, $separator));
            if ($name === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name)) {
                continue;
            }

            // Server-provided variables take precedence over the local file.
            if (getenv($name) !== false) {
                continue;
            }

            $value = trim(substr($line, $separator + 1));
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

load_app_environment();

if (!function_exists('configure_app_timezone')) {
    function configure_app_timezone(): void
    {
        $timezone = trim((string)(getenv('APP_TIMEZONE') ?: 'Asia/Kuala_Lumpur'));
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = 'Asia/Kuala_Lumpur';
        }

        date_default_timezone_set($timezone);
    }
}

configure_app_timezone();
