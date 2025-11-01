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
 * Obtiene el token del header Authorization
 * Compatible con todos los servidores (Apache, Nginx, etc.)
 */
function getAuthToken() {
    // Primero intentar obtener directamente de $_SERVER (más seguro)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } else {
        // Intentar obtener desde headers usando función nativa si existe
        $auth = null;
        if (function_exists('getallheaders')) {
            $headers = @getallheaders();
            if ($headers) {
                foreach ($headers as $key => $value) {
                    if (strtolower($key) === 'authorization') {
                        $auth = $value;
                        break;
                    }
                }
            }
        }
        
        // Si aún no encontramos el header, buscar manualmente en $_SERVER
        if (!$auth) {
            foreach ($_SERVER as $name => $value) {
                if (strtoupper($name) === 'HTTP_AUTHORIZATION') {
                    $auth = $value;
                    break;
                }
            }
        }
    }
    
    // Extraer el token Bearer
    if ($auth && preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return trim($matches[1]);
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

