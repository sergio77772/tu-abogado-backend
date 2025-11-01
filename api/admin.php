<?php
/**
 * API de Administración
 * Endpoints: GET /api/admin.php?action=stats|users|abogados
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../db.php';

setCorsHeaders();
handleOptions();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = requireRole(ROLE_ADMIN); // Solo admin

switch ($method) {
    case 'GET':
        if ($action === 'stats') {
            // Estadísticas generales
            $stats = [];
            
            // Total de usuarios por rol
            $stmt = $pdo->query("
                SELECT rol, COUNT(*) as total 
                FROM usuarios 
                WHERE rol IN ('cliente', 'abogado')
                GROUP BY rol
            ");
            $stats['usuarios'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Total de planes activos
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM planes WHERE activo = 1");
            $stats['planes_activos'] = $stmt->fetch()['total'];
            
            // Total de compras por estado
            $stmt = $pdo->query("
                SELECT estado, COUNT(*) as total 
                FROM compras 
                GROUP BY estado
            ");
            $stats['compras'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Total de consultas por estado
            $stmt = $pdo->query("
                SELECT estado, COUNT(*) as total 
                FROM consultas 
                GROUP BY estado
            ");
            $stats['consultas'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Ingresos totales (compras pagadas)
            $stmt = $pdo->query("
                SELECT SUM(monto) as total 
                FROM compras 
                WHERE estado = 'pagada'
            ");
            $stats['ingresos_totales'] = (float)($stmt->fetch()['total'] ?? 0);
            
            jsonResponse($stats);
            
        } elseif ($action === 'users') {
            // Listar todos los usuarios
            $page = isset($_GET['page']) ? (int)$_GET['page'] : DEFAULT_PAGE;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : DEFAULT_LIMIT;
            $offset = ($page - 1) * $limit;
            $rol = $_GET['rol'] ?? '';
            
            $where = [];
            $params = [];
            
            if ($rol) {
                $where[] = "rol = :rol";
                $params['rol'] = $rol;
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Contar
            $countSql = "SELECT COUNT(*) as total FROM usuarios $whereClause";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];
            $totalPages = ceil($total / $limit);
            
            // Obtener usuarios (sin contraseña)
            $sql = "SELECT id, nombre, email, rol, fecha_registro, activo 
                    FROM usuarios 
                    $whereClause
                    ORDER BY fecha_registro DESC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $usuarios = $stmt->fetchAll();
            
            jsonResponse([
                'usuarios' => $usuarios,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ]);
            
        } elseif ($action === 'abogados') {
            // Listar abogados con estadísticas
            $stmt = $pdo->query("
                SELECT 
                    u.id,
                    u.nombre,
                    u.email,
                    u.fecha_registro,
                    COUNT(DISTINCT c.id) as total_consultas,
                    COUNT(DISTINCT CASE WHEN c.estado = 'respondida' THEN c.id END) as consultas_respondidas,
                    COUNT(DISTINCT CASE WHEN c.estado = 'cerrada' THEN c.id END) as consultas_cerradas
                FROM usuarios u
                LEFT JOIN consultas c ON u.id = c.abogado_id
                WHERE u.rol = 'abogado'
                GROUP BY u.id, u.nombre, u.email, u.fecha_registro
                ORDER BY u.fecha_registro DESC
            ");
            $abogados = $stmt->fetchAll();
            
            jsonResponse(['abogados' => $abogados]);
            
        } elseif ($action === 'compras') {
            // Listar todas las compras con detalles
            $page = isset($_GET['page']) ? (int)$_GET['page'] : DEFAULT_PAGE;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : DEFAULT_LIMIT;
            $offset = ($page - 1) * $limit;
            $estado = $_GET['estado'] ?? '';
            
            $where = [];
            $params = [];
            
            if ($estado) {
                $where[] = "c.estado = :estado";
                $params['estado'] = $estado;
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Contar
            $countSql = "SELECT COUNT(*) as total FROM compras c $whereClause";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];
            $totalPages = ceil($total / $limit);
            
            // Obtener compras
            $sql = "
                SELECT 
                    c.*,
                    u.nombre as usuario_nombre,
                    u.email as usuario_email,
                    p.nombre as plan_nombre,
                    p.tipo as plan_tipo
                FROM compras c
                INNER JOIN usuarios u ON c.usuario_id = u.id
                INNER JOIN planes p ON c.plan_id = p.id
                $whereClause
                ORDER BY c.fecha_compra DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $compras = $stmt->fetchAll();
            
            jsonResponse([
                'compras' => $compras,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ]);
            
        } else {
            jsonError('Acción no válida. Use: stats, users, abogados, compras', 400);
        }
        break;
        
    case 'PUT':
        // Actualizar usuario (activar/desactivar, cambiar rol)
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            jsonError('ID de usuario requerido', 400);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $fields = [];
        $params = ['id' => $userId];
        
        $allowedFields = ['activo', 'rol'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            jsonError('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $stmt = $pdo->prepare("SELECT id, nombre, email, rol, activo FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $usuario = $stmt->fetch();
        
        jsonResponse(['message' => 'Usuario actualizado exitosamente', 'usuario' => $usuario]);
        break;
        
    default:
        jsonError('Método no soportado', 405);
        break;
}
?>

