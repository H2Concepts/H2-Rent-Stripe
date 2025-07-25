<?php
// Datei: /includes/webhook/entry.php

// Beende HTTP-Verbindung sofort
ignore_user_abort(true);
http_response_code(200);
header('Content-Type: application/json');
header('Connection: close');
echo json_encode(['status' => 'received']);
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    flush();
}

// Weiterverarbeitung in separater Datei
require_once __DIR__ . '/handler.php';
