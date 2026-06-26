<?php
header('Content-Type: application/json');
echo json_encode([
    'origin_header' => $_SERVER['HTTP_ORIGIN'] ?? 'NOT SET',
    'all_headers' => array_filter($_SERVER, function($key) {
        return strpos($key, 'HTTP_') === 0;
    }, ARRAY_FILTER_USE_KEY)
]);
