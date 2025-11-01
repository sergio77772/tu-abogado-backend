<?php
// Versión directa sin includes para probar CORS
// Si este funciona, el problema está en los includes

// Headers CORS - PRIMERA LÍNEA DESPUÉS DE <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Max-Age: 86400");

// OPTIONS debe responderse ANTES de cualquier otra cosa
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Respuesta simple
header("Content-Type: application/json");
echo json_encode([
    'status' => 'success',
    'message' => 'CORS funciona',
    'method' => $_SERVER['REQUEST_METHOD']
]);
?>

