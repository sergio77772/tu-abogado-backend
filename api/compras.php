<?php
// Headers CORS - DEBE IR PRIMERO, ANTES DE CUALQUIER COSA
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: false");
header("Access-Control-Max-Age: 86400");

// Manejar peticiones preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header("Content-Length: 0");
    exit(0);
}

/**
 * API de Compras
 * Endpoints: GET, POST /api/compras.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$user = requireAuth(); // Requiere autenticación

switch ($method) {
    case 'GET':
        if ($id) {
            // Obtener una compra específica
            $stmt = $pdo->prepare("
                SELECT c.*, p.nombre as plan_nombre, p.tipo as plan_tipo
                FROM compras c
                INNER JOIN planes p ON c.plan_id = p.id
                WHERE c.id = :id
            ");
            $stmt->execute(['id' => $id]);
            $compra = $stmt->fetch();
            
            if (!$compra) {
                jsonError('Compra no encontrada', 404);
            }
            
            // Verificar permisos (cliente solo ve sus compras, admin ve todas)
            if ($user['rol'] === ROLE_CLIENTE && $compra['usuario_id'] != $user['id']) {
                jsonError('No autorizado', 403);
            }
            
            jsonResponse($compra);
            
        } else {
            // Listar compras
            if ($user['rol'] === ROLE_ADMIN) {
                // Admin ve todas las compras
                $stmt = $pdo->prepare("
                    SELECT c.*, p.nombre as plan_nombre, u.nombre as usuario_nombre, u.email as usuario_email
                    FROM compras c
                    INNER JOIN planes p ON c.plan_id = p.id
                    INNER JOIN usuarios u ON c.usuario_id = u.id
                    ORDER BY c.fecha_compra DESC
                ");
                $stmt->execute();
            } else {
                // Cliente ve solo sus compras
                $stmt = $pdo->prepare("
                    SELECT c.*, p.nombre as plan_nombre, p.tipo as plan_tipo
                    FROM compras c
                    INNER JOIN planes p ON c.plan_id = p.id
                    WHERE c.usuario_id = :usuario_id
                    ORDER BY c.fecha_compra DESC
                ");
                $stmt->execute(['usuario_id' => $user['id']]);
            }
            
            $compras = $stmt->fetchAll();
            jsonResponse(['compras' => $compras]);
        }
        break;
        
    case 'POST':
        // Crear una compra (solo clientes)
        if ($user['rol'] !== ROLE_CLIENTE) {
            jsonError('Solo los clientes pueden comprar planes', 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        validateRequired($data, ['plan_id']);
        
        // Verificar que el plan existe y está activo
        $stmt = $pdo->prepare("SELECT * FROM planes WHERE id = :id AND activo = 1");
        $stmt->execute(['id' => $data['plan_id']]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            jsonError('Plan no encontrado o no disponible', 404);
        }
        
        // Calcular fecha de expiración si es suscripción
        $fechaExpiracion = null;
        if ($plan['tipo'] === 'suscripcion' && $plan['duracion_dias']) {
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime("+{$plan['duracion_dias']} days"));
        } elseif ($plan['tipo'] === 'paquete' && $plan['duracion_dias']) {
            $fechaExpiracion = date('Y-m-d H:i:s', strtotime("+{$plan['duracion_dias']} days"));
        }
        
        // Crear la compra con estado pendiente
        $stmt = $pdo->prepare("
            INSERT INTO compras (
                usuario_id, plan_id, estado, monto, consultas_totales, 
                consultas_disponibles, fecha_expiracion, metodo_pago, transaccion_id
            ) VALUES (
                :usuario_id, :plan_id, :estado, :monto, :consultas_totales,
                :consultas_disponibles, :fecha_expiracion, :metodo_pago, :transaccion_id
            )
        ");
        
        try {
            $stmt->execute([
                'usuario_id' => $user['id'],
                'plan_id' => $plan['id'],
                'estado' => $data['estado'] ?? ESTADO_COMPRA_PENDIENTE,
                'monto' => $plan['precio'],
                'consultas_totales' => $plan['cantidad_consultas'],
                'consultas_disponibles' => $plan['cantidad_consultas'],
                'fecha_expiracion' => $fechaExpiracion,
                'metodo_pago' => $data['metodo_pago'] ?? null,
                'transaccion_id' => $data['transaccion_id'] ?? null
            ]);
            
            $compraId = $pdo->lastInsertId();
            
            // Obtener la compra creada
            $stmt = $pdo->prepare("
                SELECT c.*, p.nombre as plan_nombre, p.tipo as plan_tipo
                FROM compras c
                INNER JOIN planes p ON c.plan_id = p.id
                WHERE c.id = :id
            ");
            $stmt->execute(['id' => $compraId]);
            $compra = $stmt->fetch();
            
            jsonResponse([
                'message' => 'Compra creada exitosamente',
                'compra' => $compra
            ], 201);
            
        } catch (PDOException $e) {
            jsonError('Error al crear compra: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        // Actualizar estado de compra (pago confirmado, etc.)
        if ($user['rol'] !== ROLE_ADMIN && $user['rol'] !== ROLE_ABOGADO) {
            // Los clientes no pueden actualizar compras directamente
            // Esto se hará mediante webhooks de Mercado Pago
            jsonError('No autorizado', 403);
        }
        
        if (!$id) {
            jsonError('ID de compra requerido', 400);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Verificar que la compra existe
        $stmt = $pdo->prepare("SELECT * FROM compras WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $compra = $stmt->fetch();
        
        if (!$compra) {
            jsonError('Compra no encontrada', 404);
        }
        
        // Actualizar campos permitidos
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['estado', 'fecha_pago', 'transaccion_id', 'metodo_pago'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            jsonError('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE compras SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Obtener la compra actualizada
        $stmt = $pdo->prepare("
            SELECT c.*, p.nombre as plan_nombre
            FROM compras c
            INNER JOIN planes p ON c.plan_id = p.id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $compra = $stmt->fetch();
        
        jsonResponse(['message' => 'Compra actualizada exitosamente', 'compra' => $compra]);
        break;
        
    default:
        jsonError('Método no soportado', 405);
        break;
}
?>

