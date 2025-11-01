<?php
/**
 * API de Planes
 * Endpoints: GET, POST, PUT, DELETE /api/planes.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../db.php';

setCorsHeaders();
handleOptions();

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
                jsonError('Plan no encontrado', 404);
            }
            
            jsonResponse($plan);
            
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
            
            jsonResponse([
                'planes' => $planes,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ]);
        }
        break;
        
    case 'POST':
        // Crear plan (solo admin)
        requireRole(ROLE_ADMIN);
        
        $data = json_decode(file_get_contents('php://input'), true);
        validateRequired($data, ['nombre', 'tipo', 'cantidad_consultas', 'precio']);
        
        // Validar tipo
        if (!in_array($data['tipo'], ['paquete', 'suscripcion'])) {
            jsonError('Tipo no válido. Debe ser: paquete o suscripcion', 400);
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
            
            jsonResponse(['message' => 'Plan creado exitosamente', 'plan' => $plan], 201);
            
        } catch (PDOException $e) {
            jsonError('Error al crear plan: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        // Actualizar plan (solo admin)
        requireRole(ROLE_ADMIN);
        
        if (!$id) {
            jsonError('ID de plan requerido', 400);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Construir query dinámicamente
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['nombre', 'descripcion', 'tipo', 'cantidad_consultas', 'precio', 'duracion_dias', 'activo'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            jsonError('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE planes SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $stmt = $pdo->prepare("SELECT * FROM planes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $plan = $stmt->fetch();
        
        jsonResponse(['message' => 'Plan actualizado exitosamente', 'plan' => $plan]);
        break;
        
    case 'DELETE':
        // Eliminar plan (solo admin) - mejor desactivar
        requireRole(ROLE_ADMIN);
        
        if (!$id) {
            jsonError('ID de plan requerido', 400);
        }
        
        // En lugar de eliminar, desactivamos
        $stmt = $pdo->prepare("UPDATE planes SET activo = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        jsonResponse(['message' => 'Plan desactivado exitosamente']);
        break;
        
    default:
        jsonError('Método no soportado', 405);
        break;
}
?>

