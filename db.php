<?php
// Configuración para InfinityFree
// IMPORTANTE: En InfinityFree NO uses 'localhost', usa el host remoto de MySQL

// Credenciales de InfinityFree
$host = 'sql309.infinityfree.com'; // Host remoto de MySQL
$db = 'if0_39887234_proyecto'; // Nombre de la base de datos
$user = 'if0_39887234'; // Usuario MySQL
$pass = 'Sergio12345RG'; // Contraseña MySQL
$port = '3306'; // Puerto MySQL (opcional)

try {
    // Construir DSN con puerto explícito
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Mensaje de error más descriptivo
    $errorMsg = $e->getMessage();
    die(json_encode([
        'error' => true,
        'message' => 'Error de conexión a la base de datos',
        'details' => $errorMsg,
        'host' => $host,
        'port' => $port,
        'db' => $db,
        'user' => $user
    ], JSON_UNESCAPED_UNICODE));
}
?>

