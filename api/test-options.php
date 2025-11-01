<?php
// Test simple para verificar que OPTIONS funciona
// Prueba: http://tuabogadoenlinea.free.nf/apis/api/test-options.php

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: false");
header("Access-Control-Max-Age: 86400");

// Manejar OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header("Content-Length: 0");
    exit(0);
}

// Respuesta para otras peticiones
header("Content-Type: application/json");
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'message' => 'OPTIONS debería funcionar',
    'headers_sent' => headers_sent(),
    'request_method' => $_SERVER['REQUEST_METHOD']
], JSON_PRETTY_PRINT);
?>

