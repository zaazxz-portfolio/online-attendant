<?php
// router.php - Router for PHP built-in web server (php -S localhost:8000 router.php)

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Allow direct access to files in the public directory (assets, uploads, etc.)
if (str_starts_with($uri, '/public/')) {
    $filePath = __DIR__ . $uri;
    if (file_exists($filePath) && !is_dir($filePath)) {
        return false; // serve the file as-is
    }
}

// Allow direct access to vendor, manifest, and service worker
if (str_starts_with($uri, '/vendor/') || $uri === '/manifest.json' || $uri === '/service-worker.js') {
    $filePath = __DIR__ . $uri;
    if (file_exists($filePath)) {
        return false;
    }
}

// Prevent direct URL access to the apps folder (block e.g. /apps/auth/login.php)
if (str_starts_with($uri, '/apps/')) {
    header('HTTP/1.0 403 Forbidden');
    echo '403 Forbidden';
    exit();
}

// Route all other requests internally to apps/
$appsPath = __DIR__ . '/apps' . $uri;

if ($uri === '/' || $uri === '/index.php') {
    require_once __DIR__ . '/apps/index.php';
    exit();
}

if (file_exists($appsPath) && !is_dir($appsPath)) {
    // Set correct script name and filename for PHP $_SERVER globals
    $_SERVER['SCRIPT_NAME'] = '/apps' . $uri;
    $_SERVER['SCRIPT_FILENAME'] = $appsPath;
    require_once $appsPath;
    exit();
}

// Fallback 404
header("HTTP/1.0 404 Not Found");
echo "The requested resource $uri was not found on this server.";
exit();
?>
