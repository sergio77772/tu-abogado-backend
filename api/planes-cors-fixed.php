<?php
/**
 * API de Planes - Versión con CORS mejorado
 * Endpoints: GET, POST, PUT, DELETE /api/planes.php
 */

// CRÍTICO: Manejar OPTIONS ANTES de cualquier cosa (sin espacios antes de <?php)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    header('Content-Length: 0');
    http_response_code(200);
    exit(0);
}

// Headers CORS para todas las demás peticiones
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Ahora cargar las dependencias
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : DEFAULT_PAGE;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : DEFAULT_LIMIT;
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($id) {
            // Obtener un plan específico
            $stmt = $pdo->prepare("SELECT * FROM planes WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $plan = $stmt->fetch();
            
            if (!$plan) {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['error' => 'Plan no encontrado'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            header('Content-Type: application/json');
            echo json_encode($plan, JSON_UNESCAPED_UNICODE);
            exit;
            
        } else {
            // Listar planes (solo activos para clientes, todos para admin)
            $user = getAuthenticatedUser();
            $onlyActive = $user && $user['rol'] !== ROLE_ADMIN ? "WHERE activo = 1" : "";
            
            // Contar total
            $countSql = "SELECT COUNT(*) as total FROM planes $onlyActive";
            $total = $pdo->query($countSql)->fetch()['total'];
            $totalPages = ceil($total / $limit);
            
            // Obtener planes
            $sql = "SELECT * FROM planes $onlyActive ORDER BY precio ASC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $planes = $stmt->fetchAll();
            
            header('Content-Type: application/json');
            echo json_encode([
                'planes' => $planes,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        break;
        
    case 'POST':
        // Crear plan (solo admin)
        requireRole(ROLE_ADMIN);
        
        $data = json_decode(file_get_contents('php://input'), true);
        validateRequired($data, ['nombre', 'tipo', 'cantidad_consultas', 'precio']);
        
        if (!in_array($data['tipo'], ['paquete', 'suscripcion'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Tipo no válido. Debe ser: paquete o suscripcion'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO planes (nombre, descripcion, tipo, cantidad_consultas, precio, duracion_dias, activo) 
            VALUES (:nombre, :descripcion, :tipo, :cantidad_consultas, :precio, :duracion_dias, :activo)
        ");
        
        try {
            $stmt->execute([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'tipo' => $data['tipo'],
                'cantidad_consultas' => $data['cantidad_consultas'],
                'precio' => $data['precio'],
                'duracion_dias' => $data['duracion_dias'] ?? null,
                'activo' => $data['activo'] ?? 1
            ]);
            
            $planId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM planes WHERE id = :id");
            $stmt->execute(['id' => $planId]);
            $plan = $stmt->fetch();
            
            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode(['message' => 'Plan creado exitosamente', 'plan' => $plan], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear plan: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
        break;
        
    default:
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['error' => 'Método no soportado'], JSON_UNESCAPED_UNICODE);
        exit;
}
?>

