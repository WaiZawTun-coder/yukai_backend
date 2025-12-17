<?php
require_once __DIR__ . '/../bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routes = require __DIR__ . '/../routes/access.php';

print_r($routes);

echo $_SERVER['REQUEST_URI'];

function deny(int $code = 401)
{
    http_response_code($code);
    echo json_encode(['error' => 'Access denied']);
    exit;
}


// 1. Allow PUBLIC routes
if (in_array($uri, $routes['public'], true)) {
    return;
}


// 2. Block everything else unless logged in
if (!isset($_SESSION['user_id'])) {
    deny(401);
}


// 3. Extra protection for ADMIN routes
if (in_array($uri, $routes['admin'], true)) {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        deny(403);
    }
}
