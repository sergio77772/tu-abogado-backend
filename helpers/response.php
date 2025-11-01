<?php
/**
 * Funciones para manejar respuestas HTTP
 */

/**
 * Configura los headers CORS
 */
function setCorsHeaders() {
    // Permitir cualquier origen (en producción, especifica dominios concretos)
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
    
    // Si CORS_ALLOWED_ORIGINS es '*', usar el origin de la petición o '*'
    if (CORS_ALLOWED_ORIGINS === '*') {
        header('Access-Control-Allow-Origin: *');
    } else {
        // Validar el origen si está en la lista permitida
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
    
    header('Access-Control-Allow-Methods: ' . CORS_ALLOWED_METHODS);
    header('Access-Control-Allow-Headers: ' . CORS_ALLOWED_HEADERS);
    header('Access-Control-Max-Age: 86400'); // Cache preflight por 24 horas
    
    // Headers adicionales para evitar problemas comunes
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Maneja las peticiones OPTIONS (preflight)
 * IMPORTANTE: Debe llamarse ANTES de setCorsHeaders()
 */
function handleOptions() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        // Configurar CORS para la petición preflight
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
        
        if (CORS_ALLOWED_ORIGINS === '*') {
            header('Access-Control-Allow-Origin: *');
        } else {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
        
        header('Access-Control-Allow-Methods: ' . CORS_ALLOWED_METHODS);
        header('Access-Control-Allow-Headers: ' . CORS_ALLOWED_HEADERS);
        header('Access-Control-Max-Age: 86400');
        
        http_response_code(200);
        exit;
    }
}

/**
 * Inicializa CORS (llama handleOptions primero, luego setCorsHeaders)
 * Usa esta función en lugar de llamarlas por separado
 */
function initCors() {
    handleOptions(); // Debe ir primero para manejar OPTIONS
    setCorsHeaders(); // Luego configurar CORS para el resto de peticiones
}

/**
 * Envía una respuesta JSON exitosa
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envía una respuesta de error
 */
function jsonError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Valida que los campos requeridos estén presentes
 */
function validateRequired($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        jsonError('Campos requeridos faltantes: ' . implode(', ', $missing), 400);
    }
    return true;
}
?>

