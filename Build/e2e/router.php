<?php

/**
 * Router for PHP's built-in server.
 *
 * Without it every speaking URL ends in a 404: the server only looks for files
 * on disk and knows nothing about TYPO3's routing. Existing files are still
 * served directly so assets do not pass through the whole bootstrap.
 */

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . '/public' . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require __DIR__ . '/public/index.php';
