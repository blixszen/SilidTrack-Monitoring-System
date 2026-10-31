<?php
/**
 * SilidTrack - Base URL Auto-Detection
 *
 * Makes every internal link/asset/redirect work correctly whether you
 * deploy the app at the web root (http://localhost/) or in a subfolder
 * (http://localhost/silidtrack/) — no manual configuration needed.
 */

if (!function_exists('load_env_file')) {
    function load_env_file(): void
    {
        $envFile = __DIR__ . '/../.env';
        if (!is_file($envFile) || !is_readable($envFile)) {
            return;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = ltrim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (strlen($value) >= 2 && $value[0] === $value[strlen($value) - 1] && ($value[0] === '"' || $value[0] === "'")) {
                $value = substr($value, 1, -1);
            }
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
load_env_file();

if (!defined('APP_BASE_PATH')) {
    // Fixed so every link, asset and redirect stays on http://localhost/silidtrack
    // no matter which page you are on. To deploy somewhere else, set APP_BASE_PATH
    // in .env (use an empty value for the web root).
    $configured = getenv('APP_BASE_PATH');
    define('APP_BASE_PATH', $configured === false ? '/silidtrack' : rtrim($configured, '/'));
}

/**
 * Build an app-relative URL that respects the detected base path.
 * Usage: url('/admin/dashboard.php'), url('/api/login.php'), url('/assets/css/style.css')
 */
function url(string $path): string {
    return APP_BASE_PATH . $path;
}
