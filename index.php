<?php
// Headers CORS - DEBE IR PRIMERO, ANTES DE CUALQUIER COSA
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar peticiones preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * API Principal - Router
 * Endpoint principal que maneja todas las rutas
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/auth.php';

// Obtener la ruta solicitada
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remover query string y obtener el path
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);

// Rutas de la API
$routes = [
    'auth' => 'api/auth.php',
    'planes' => 'api/planes.php',
    'compras' => 'api/compras.php',
    'consultas' => 'api/consultas.php',
    'admin' => 'api/admin.php',
];

// Determinar qué endpoint se solicita
$endpoint = '';
if (preg_match('#/api/(\w+)#', $path, $matches)) {
    $endpoint = $matches[1];
} elseif (preg_match('#^/(\w+)#', $path, $matches)) {
    $endpoint = $matches[1];
}

// Si hay parámetros adicionales, pasarlos como query string
if (preg_match('#/api/\w+/(\d+)#', $path, $matches)) {
    $_GET['id'] = $matches[1];
}

// Incluir el archivo correspondiente
if (isset($routes[$endpoint])) {
    require_once __DIR__ . '/' . $routes[$endpoint];
} else {
    // Si no hay endpoint, mostrar información de la API
    if ($path === '/' || $path === '' || $path === '/api') {
        jsonResponse([
            'message' => 'API Tu Abogado en Línea',
            'version' => '1.0.0',
            'endpoints' => [
                'auth' => [
                    'POST /api/auth.php?action=register' => 'Registrar usuario (cliente o abogado)',
                    'POST /api/auth.php?action=login' => 'Iniciar sesión'
                ],
                'planes' => [
                    'GET /api/planes.php' => 'Listar planes',
                    'GET /api/planes.php?id=:id' => 'Obtener plan específico',
                    'POST /api/planes.php' => 'Crear plan (admin)',
                    'PUT /api/planes.php?id=:id' => 'Actualizar plan (admin)',
                    'DELETE /api/planes.php?id=:id' => 'Desactivar plan (admin)'
                ],
                'compras' => [
                    'GET /api/compras.php' => 'Listar compras del usuario',
                    'GET /api/compras.php?id=:id' => 'Obtener compra específica',
                    'POST /api/compras.php' => 'Crear compra (cliente)',
                    'PUT /api/compras.php?id=:id' => 'Actualizar compra (admin)'
                ],
                'consultas' => [
                    'GET /api/consultas.php' => 'Listar consultas del usuario',
                    'GET /api/consultas.php?action=disponibles' => 'Ver consultas disponibles (cliente)',
                    'GET /api/consultas.php?action=pendientes' => 'Ver consultas pendientes (abogado/admin)',
                    'GET /api/consultas.php?id=:id' => 'Obtener consulta específica',
                    'POST /api/consultas.php' => 'Crear consulta (cliente)',
                    'PUT /api/consultas.php?id=:id' => 'Responder/Actualizar consulta'
                ],
                'admin' => [
                    'GET /api/admin.php?action=stats' => 'Estadísticas generales',
                    'GET /api/admin.php?action=users' => 'Listar usuarios',
                    'GET /api/admin.php?action=abogados' => 'Listar abogados con estadísticas',
                    'GET /api/admin.php?action=compras' => 'Listar todas las compras',
                    'PUT /api/admin.php?id=:id' => 'Actualizar usuario'
                ]
            ]
        ]);
    } else {
        jsonError('Endpoint no encontrado', 404);
    }
}
?>

