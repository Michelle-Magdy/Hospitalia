<?php
declare(strict_types=1);

/**
 * Load KEY=value pairs from a .env file into the environment.
 * Does not override variables already set in the real environment.
 */
function load_dotenv(string $path): bool {
    if (!is_readable($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return false;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            continue;
        }
        if (getenv($key) !== false) {
            continue;
        }
        $value = parse_env_value($value);
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
    return true;
}

function parse_env_value(string $raw): string {
    $v = trim($raw);
    $len = strlen($v);
    if ($len >= 2) {
        $first = $v[0];
        $last = $v[$len - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($v, 1, -1);
        }
    }
    return $v;
}

/** Read env var after load_dotenv(); falls back to $default if unset or empty. */
function env(string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v === false || $v === '') {
        return $default;
    }
    return $v;
}
