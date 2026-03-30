<?php

/**
 * Application Bootstrap - Initialize security, session, env loading
 */

ini_set('display_errors', getenv('APP_DEBUG') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/error.log');

$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
        }
    }
}

if (!getenv('APP_ENV')) {
    putenv('APP_ENV=development');
    putenv('APP_DEBUG=true');
}

require_once __DIR__ . '/../helpers/SecurityHelper.php';

if (!session_id()) {
    SecurityHelper::configureSecureSession();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

SecurityHelper::setSecurityHeaders();

$csrf_token = SecurityHelper::generateCSRFToken();

if (getenv('APP_ENV') === 'production' && getenv('APP_DEBUG') === 'false') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}

date_default_timezone_set('Asia/Jakarta');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
?>
