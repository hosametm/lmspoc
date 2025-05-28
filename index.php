<?php
require_once './routes.php';
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = str_replace('/lmspoc', '', $requestUri);
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");


if (array_key_exists($requestUri, $routes)) {
    $filePath = realpath(__DIR__ . '/' . $routes[$requestUri]);
    if ($filePath && strpos($filePath, __DIR__) === 0 && file_exists($filePath)) {
        include $filePath;
    } else {
        http_response_code(403);
        echo "Access denied.";
    }
} else {
    http_response_code(404);
    echo "404 Not Found";
}
?>