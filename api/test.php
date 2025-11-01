<?php
/**
 * Test directo del API
 * Prueba: http://tuabogadoenlinea.free.nf/api/test.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

echo json_encode([
    'status' => 'success',
    'message' => 'El endpoint /api/ está funcionando',
    'directorio_actual' => __DIR__,
    'archivos_en_api' => array_values(array_filter(scandir(__DIR__), function($file) {
        return $file !== '.' && $file !== '..';
    }))
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

