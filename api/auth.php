<?php
/**
 * API de Autenticación
 * Endpoints: POST /api/auth.php?action=register|login
 */

// Manejar CORS ANTES de cualquier otra cosa (incluyendo includes)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../db.php';

initCors();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'register') {
        // Registro de usuario
        validateRequired($data, ['nombre', 'email', 'contraseña', 'rol']);
        
        // Validar rol
        $rolesPermitidos = [ROLE_CLIENTE, ROLE_ABOGADO];
        if (!in_array($data['rol'], $rolesPermitidos)) {
            jsonError('Rol no válido. Solo se permiten: cliente, abogado', 400);
        }
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $data['email']]);
        if ($stmt->fetch()) {
            jsonError('El email ya está registrado', 400);
        }
        
        // Validar formato de email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            jsonError('Email no válido', 400);
        }
        
        // Validar contraseña (mínimo 6 caracteres)
        if (strlen($data['contraseña']) < 6) {
            jsonError('La contraseña debe tener al menos 6 caracteres', 400);
        }
        
        // Crear usuario
        $hashedPassword = hashPassword($data['contraseña']);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, contraseña, rol) 
            VALUES (:nombre, :email, :contraseña, :rol)
        ");
        
        try {
            $stmt->execute([
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'contraseña' => $hashedPassword,
                'rol' => $data['rol']
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Obtener el usuario creado
            $stmt = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            
            // Generar token
            $token = generateToken($userId, $data['rol']);
            
            jsonResponse([
                'message' => 'Usuario registrado exitosamente',
                'user' => $user,
                'token' => $token
            ], 201);
            
        } catch (PDOException $e) {
            jsonError('Error al registrar usuario: ' . $e->getMessage(), 500);
        }
        
    } elseif ($action === 'login') {
        // Login de usuario
        validateRequired($data, ['email', 'contraseña']);
        
        $stmt = $pdo->prepare("SELECT id, nombre, email, contraseña, rol FROM usuarios WHERE email = :email AND activo = 1");
        $stmt->execute(['email' => $data['email']]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($data['contraseña'], $user['contraseña'])) {
            jsonError('Credenciales inválidas', 401);
        }
        
        // Eliminar contraseña de la respuesta
        unset($user['contraseña']);
        
        // Generar token
        $token = generateToken($user['id'], $user['rol']);
        
        jsonResponse([
            'message' => 'Login exitoso',
            'user' => $user,
            'token' => $token
        ]);
        
    } else {
        jsonError('Acción no válida. Use: register o login', 400);
    }
    
} else {
    jsonError('Método no permitido', 405);
}
?>

