<?php
/**
 * Funciones de autenticación y autorización
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../db.php';

/**
 * Encripta una contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifica una contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Genera un token JWT simple (versión simplificada)
 * En producción, usar una librería como firebase/php-jwt
 */
function generateToken($userId, $role) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]));
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'role' => $role,
        'iat' => time(),
        'exp' => time() + JWT_EXPIRATION
    ]));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$signature";
}

/**
 * Verifica y decodifica un token JWT
 */
function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    
    list($header, $payload, $signature) = $parts;
    
    $expectedSignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    
    if ($signature !== $expectedSignature) {
        return null;
    }
    
    $data = json_decode(base64_decode($payload), true);
    
    // Verificar expiración
    if (isset($data['exp']) && $data['exp'] < time()) {
        return null;
    }
    
    return $data;
}

/**
 * Función alternativa para obtener headers (compatible con todos los servidores)
 */
function getAllHeaders() {
    if (function_exists('getallheaders')) {
        return getallheaders();
    }
    
    // Alternativa para servidores que no tienen getallheaders()
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    
    // Agregar headers especiales
    if (isset($_SERVER['CONTENT_TYPE'])) {
        $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
    }
    if (isset($_SERVER['CONTENT_LENGTH'])) {
        $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
    }
    
    return $headers;
}

/**
 * Obtiene el token del header Authorization
 */
function getAuthToken() {
    $headers = getAllHeaders();
    
    // Buscar Authorization en diferentes formatos
    $auth = null;
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $auth = $headers['authorization'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }
    
    if ($auth && preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Obtiene el usuario autenticado desde el token
 */
function getAuthenticatedUser() {
    $token = getAuthToken();
    if (!$token) {
        return null;
    }
    
    $data = verifyToken($token);
    if (!$data) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $data['user_id']]);
    return $stmt->fetch();
}

/**
 * Verifica si el usuario tiene un rol específico
 */
function hasRole($requiredRole) {
    $user = getAuthenticatedUser();
    if (!$user) {
        return false;
    }
    return $user['rol'] === $requiredRole;
}

/**
 * Requiere autenticación
 */
function requireAuth() {
    $user = getAuthenticatedUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }
    return $user;
}

/**
 * Requiere un rol específico
 */
function requireRole($role) {
    $user = requireAuth();
    if ($user['rol'] !== $role) {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    return $user;
}
?>

