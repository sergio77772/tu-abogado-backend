<?php
/**
 * Archivo de prueba simple
 * Prueba este archivo primero: http://tuabogadoenlinea.free.nf/test.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

echo json_encode([
    'status' => 'success',
    'message' => 'El servidor PHP está funcionando correctamente',
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconocido',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Desconocido',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Desconocido'
    ],
    'archivos_existentes' => [
        'db.php' => file_exists(__DIR__ . '/db.php'),
        'config/config.php' => file_exists(__DIR__ . '/config/config.php'),
        'api/planes.php' => file_exists(__DIR__ . '/api/planes.php'),
        'api/auth.php' => file_exists(__DIR__ . '/api/auth.php'),
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

