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
 * API de Consultas
 * Endpoints: GET, POST, PUT /api/consultas.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../db.php';

initCors();

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';
$user = requireAuth();

switch ($method) {
    case 'GET':
        if ($id) {
            // Obtener una consulta específica
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       cli.nombre as cliente_nombre, cli.email as cliente_email,
                       abog.nombre as abogado_nombre, abog.email as abogado_email,
                       comp.consultas_disponibles, p.nombre as plan_nombre
                FROM consultas c
                INNER JOIN usuarios cli ON c.cliente_id = cli.id
                LEFT JOIN usuarios abog ON c.abogado_id = abog.id
                INNER JOIN compras comp ON c.compra_id = comp.id
                INNER JOIN planes p ON comp.plan_id = p.id
                WHERE c.id = :id
            ");
            $stmt->execute(['id' => $id]);
            $consulta = $stmt->fetch();
            
            if (!$consulta) {
                jsonError('Consulta no encontrada', 404);
            }
            
            // Verificar permisos
            if ($user['rol'] === ROLE_CLIENTE && $consulta['cliente_id'] != $user['id']) {
                jsonError('No autorizado', 403);
            }
            if ($user['rol'] === ROLE_ABOGADO && $consulta['abogado_id'] != $user['id'] && $consulta['estado'] === 'abierta') {
                // Los abogados pueden ver consultas abiertas (para asignarse) o las suyas propias
            }
            
            jsonResponse($consulta);
            
        } elseif ($action === 'disponibles') {
            // Consultas disponibles para el cliente (consultas que puede hacer)
            if ($user['rol'] !== ROLE_CLIENTE) {
                jsonError('Solo los clientes pueden ver consultas disponibles', 403);
            }
            
            // Obtener compras activas del cliente con consultas disponibles
            $stmt = $pdo->prepare("
                SELECT 
                    comp.id as compra_id,
                    comp.consultas_disponibles,
                    comp.consultas_totales,
                    comp.consultas_usadas,
                    p.nombre as plan_nombre,
                    p.tipo as plan_tipo,
                    comp.fecha_expiracion
                FROM compras comp
                INNER JOIN planes p ON comp.plan_id = p.id
                WHERE comp.usuario_id = :usuario_id
                  AND comp.estado = 'pagada'
                  AND comp.consultas_disponibles > 0
                  AND (comp.fecha_expiracion IS NULL OR comp.fecha_expiracion > NOW())
                ORDER BY comp.fecha_compra DESC
            ");
            $stmt->execute(['usuario_id' => $user['id']]);
            $compras = $stmt->fetchAll();
            
            // Contar consultas totales disponibles
            $totalDisponibles = array_sum(array_column($compras, 'consultas_disponibles'));
            
            jsonResponse([
                'consultas_disponibles' => $totalDisponibles,
                'compras' => $compras
            ]);
            
        } elseif ($action === 'pendientes') {
            // Consultas pendientes (para abogados o admin)
            if ($user['rol'] === ROLE_CLIENTE) {
                jsonError('Acción no permitida para clientes', 403);
            }
            
            $estado = $_GET['estado'] ?? 'abierta';
            
            if ($user['rol'] === ROLE_ABOGADO) {
                // Abogado ve consultas abiertas o las suyas
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           cli.nombre as cliente_nombre, cli.email as cliente_email,
                           p.nombre as plan_nombre
                    FROM consultas c
                    INNER JOIN usuarios cli ON c.cliente_id = cli.id
                    INNER JOIN compras comp ON c.compra_id = comp.id
                    INNER JOIN planes p ON comp.plan_id = p.id
                    WHERE (c.estado = :estado OR (c.abogado_id = :abogado_id AND c.estado != 'cerrada'))
                    ORDER BY c.fecha_creacion DESC
                ");
                $stmt->execute([
                    'estado' => $estado,
                    'abogado_id' => $user['id']
                ]);
            } else {
                // Admin ve todas
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           cli.nombre as cliente_nombre, cli.email as cliente_email,
                           abog.nombre as abogado_nombre,
                           p.nombre as plan_nombre
                    FROM consultas c
                    INNER JOIN usuarios cli ON c.cliente_id = cli.id
                    LEFT JOIN usuarios abog ON c.abogado_id = abog.id
                    INNER JOIN compras comp ON c.compra_id = comp.id
                    INNER JOIN planes p ON comp.plan_id = p.id
                    WHERE c.estado = :estado
                    ORDER BY c.fecha_creacion DESC
                ");
                $stmt->execute(['estado' => $estado]);
            }
            
            $consultas = $stmt->fetchAll();
            jsonResponse(['consultas' => $consultas]);
            
        } else {
            // Listar todas las consultas del usuario
            if ($user['rol'] === ROLE_CLIENTE) {
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           abog.nombre as abogado_nombre,
                           p.nombre as plan_nombre
                    FROM consultas c
                    LEFT JOIN usuarios abog ON c.abogado_id = abog.id
                    INNER JOIN compras comp ON c.compra_id = comp.id
                    INNER JOIN planes p ON comp.plan_id = p.id
                    WHERE c.cliente_id = :cliente_id
                    ORDER BY c.fecha_creacion DESC
                ");
                $stmt->execute(['cliente_id' => $user['id']]);
            } elseif ($user['rol'] === ROLE_ABOGADO) {
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           cli.nombre as cliente_nombre, cli.email as cliente_email,
                           p.nombre as plan_nombre
                    FROM consultas c
                    INNER JOIN usuarios cli ON c.cliente_id = cli.id
                    INNER JOIN compras comp ON c.compra_id = comp.id
                    INNER JOIN planes p ON comp.plan_id = p.id
                    WHERE c.abogado_id = :abogado_id
                    ORDER BY c.fecha_creacion DESC
                ");
                $stmt->execute(['abogado_id' => $user['id']]);
            } else {
                // Admin
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           cli.nombre as cliente_nombre, cli.email as cliente_email,
                           abog.nombre as abogado_nombre,
                           p.nombre as plan_nombre
                    FROM consultas c
                    INNER JOIN usuarios cli ON c.cliente_id = cli.id
                    LEFT JOIN usuarios abog ON c.abogado_id = abog.id
                    INNER JOIN compras comp ON c.compra_id = comp.id
                    INNER JOIN planes p ON comp.plan_id = p.id
                    ORDER BY c.fecha_creacion DESC
                ");
                $stmt->execute();
            }
            
            $consultas = $stmt->fetchAll();
            jsonResponse(['consultas' => $consultas]);
        }
        break;
        
    case 'POST':
        // Crear una nueva consulta (solo clientes)
        if ($user['rol'] !== ROLE_CLIENTE) {
            jsonError('Solo los clientes pueden crear consultas', 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        validateRequired($data, ['compra_id', 'asunto', 'mensaje_inicial']);
        
        // Verificar que la compra existe, es del cliente y tiene consultas disponibles
        $stmt = $pdo->prepare("
            SELECT comp.*, p.nombre as plan_nombre
            FROM compras comp
            INNER JOIN planes p ON comp.plan_id = p.id
            WHERE comp.id = :compra_id 
              AND comp.usuario_id = :usuario_id
              AND comp.estado = 'pagada'
              AND comp.consultas_disponibles > 0
              AND (comp.fecha_expiracion IS NULL OR comp.fecha_expiracion > NOW())
        ");
        $stmt->execute([
            'compra_id' => $data['compra_id'],
            'usuario_id' => $user['id']
        ]);
        $compra = $stmt->fetch();
        
        if (!$compra) {
            jsonError('Compra no válida o sin consultas disponibles', 400);
        }
        
        // Crear la consulta
        $stmt = $pdo->prepare("
            INSERT INTO consultas (cliente_id, compra_id, asunto, mensaje_inicial, estado)
            VALUES (:cliente_id, :compra_id, :asunto, :mensaje_inicial, 'abierta')
        ");
        
        try {
            $stmt->execute([
                'cliente_id' => $user['id'],
                'compra_id' => $data['compra_id'],
                'asunto' => $data['asunto'],
                'mensaje_inicial' => $data['mensaje_inicial']
            ]);
            
            $consultaId = $pdo->lastInsertId();
            
            // Descontar una consulta disponible
            $stmt = $pdo->prepare("
                UPDATE compras 
                SET consultas_disponibles = consultas_disponibles - 1,
                    consultas_usadas = consultas_usadas + 1
                WHERE id = :id
            ");
            $stmt->execute(['id' => $data['compra_id']]);
            
            // Obtener la consulta creada
            $stmt = $pdo->prepare("
                SELECT c.*, p.nombre as plan_nombre
                FROM consultas c
                INNER JOIN compras comp ON c.compra_id = comp.id
                INNER JOIN planes p ON comp.plan_id = p.id
                WHERE c.id = :id
            ");
            $stmt->execute(['id' => $consultaId]);
            $consulta = $stmt->fetch();
            
            jsonResponse([
                'message' => 'Consulta creada exitosamente',
                'consulta' => $consulta
            ], 201);
            
        } catch (PDOException $e) {
            jsonError('Error al crear consulta: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        // Actualizar consulta (responder, cerrar, asignar abogado)
        if (!$id) {
            jsonError('ID de consulta requerido', 400);
        }
        
        // Verificar que la consulta existe
        $stmt = $pdo->prepare("SELECT * FROM consultas WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $consulta = $stmt->fetch();
        
        if (!$consulta) {
            jsonError('Consulta no encontrada', 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Responder a una consulta (abogado)
        if ($user['rol'] === ROLE_ABOGADO) {
            if (!isset($data['respuesta'])) {
                jsonError('Se requiere una respuesta', 400);
            }
            
            // Si la consulta no tiene abogado asignado, asignarse
            $abogadoId = $consulta['abogado_id'] ?? $user['id'];
            if ($consulta['abogado_id'] && $consulta['abogado_id'] != $user['id']) {
                jsonError('Esta consulta está asignada a otro abogado', 403);
            }
            
            $stmt = $pdo->prepare("
                UPDATE consultas 
                SET abogado_id = :abogado_id,
                    respuesta = :respuesta,
                    estado = :estado,
                    fecha_respuesta = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $id,
                'abogado_id' => $abogadoId,
                'respuesta' => $data['respuesta'],
                'estado' => $data['cerrar'] ?? false ? ESTADO_CONSULTA_CERRADA : ESTADO_CONSULTA_RESPONDIDA
            ]);
            
        } elseif ($user['rol'] === ROLE_CLIENTE) {
            // Cliente solo puede cerrar su consulta
            if (isset($data['cerrar']) && $data['cerrar'] && $consulta['cliente_id'] == $user['id']) {
                $stmt = $pdo->prepare("
                    UPDATE consultas 
                    SET estado = :estado,
                        fecha_cierre = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $id,
                    'estado' => ESTADO_CONSULTA_CERRADA
                ]);
            } else {
                jsonError('No autorizado', 403);
            }
            
        } elseif ($user['rol'] === ROLE_ADMIN) {
            // Admin puede hacer cualquier actualización
            $fields = [];
            $params = ['id' => $id];
            
            $allowedFields = ['abogado_id', 'estado', 'respuesta', 'asunto', 'mensaje_inicial'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }
            
            if (isset($data['respuesta'])) {
                $fields[] = "fecha_respuesta = NOW()";
            }
            if (isset($data['cerrar']) && $data['cerrar']) {
                $fields[] = "estado = :estado_cerrado";
                $fields[] = "fecha_cierre = NOW()";
                $params['estado_cerrado'] = ESTADO_CONSULTA_CERRADA;
            }
            
            if (empty($fields)) {
                jsonError('No hay campos para actualizar', 400);
            }
            
            $sql = "UPDATE consultas SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
        } else {
            jsonError('No autorizado', 403);
        }
        
        // Obtener la consulta actualizada
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   cli.nombre as cliente_nombre,
                   abog.nombre as abogado_nombre,
                   p.nombre as plan_nombre
            FROM consultas c
            INNER JOIN usuarios cli ON c.cliente_id = cli.id
            LEFT JOIN usuarios abog ON c.abogado_id = abog.id
            INNER JOIN compras comp ON c.compra_id = comp.id
            INNER JOIN planes p ON comp.plan_id = p.id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $consulta = $stmt->fetch();
        
        jsonResponse(['message' => 'Consulta actualizada exitosamente', 'consulta' => $consulta]);
        break;
        
    default:
        jsonError('Método no soportado', 405);
        break;
}
?>

