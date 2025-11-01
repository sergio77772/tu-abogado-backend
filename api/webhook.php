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
 * Webhook para recibir notificaciones de pagos
 * Integración con Mercado Pago, PayPal, Stripe
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../db.php';

// Log para debugging (crear carpeta logs si no existe)
$logFile = __DIR__ . '/../logs/webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logWebhook($message, $data = []) {
    global $logFile;
    $logMessage = date('Y-m-d H:i:s') . " - $message" . (!empty($data) ? " - " . json_encode($data) : "") . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    logWebhook('Webhook recibido', ['method' => $method, 'data' => $data]);
    
    // Detectar el proveedor de pago según los datos recibidos
    $provider = $_GET['provider'] ?? 'mercadopago'; // mercadopago, paypal, stripe
    
    try {
        if ($provider === 'mercadopago') {
            // Webhook de Mercado Pago
            // Los datos vienen en formato: { "data": { "id": "payment_id" } }
            // Necesitas hacer una consulta a la API de Mercado Pago para obtener los detalles
            
            if (isset($data['data']['id'])) {
                $paymentId = $data['data']['id'];
                
                // Aquí deberías consultar la API de Mercado Pago para obtener los detalles del pago
                // Por ahora, asumimos que el transaccion_id está en los datos del webhook
                
                // Buscar la compra por transaccion_id
                $transaccionId = $paymentId;
                $stmt = $pdo->prepare("SELECT * FROM compras WHERE transaccion_id = :transaccion_id");
                $stmt->execute(['transaccion_id' => $transaccionId]);
                $compra = $stmt->fetch();
                
                if ($compra) {
                    // Actualizar el estado de la compra a pagada
                    $stmt = $pdo->prepare("
                        UPDATE compras 
                        SET estado = :estado, 
                            fecha_pago = NOW(),
                            metodo_pago = 'mercadopago'
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'id' => $compra['id'],
                        'estado' => ESTADO_COMPRA_PAGADA
                    ]);
                    
                    logWebhook('Compra actualizada a pagada', ['compra_id' => $compra['id']]);
                    jsonResponse(['message' => 'Webhook procesado correctamente']);
                } else {
                    logWebhook('Compra no encontrada', ['transaccion_id' => $transaccionId]);
                    jsonError('Compra no encontrada', 404);
                }
            } else {
                logWebhook('Formato de webhook inválido');
                jsonError('Formato de webhook inválido', 400);
            }
            
        } elseif ($provider === 'paypal') {
            // Webhook de PayPal
            // Implementar lógica similar para PayPal
            logWebhook('Webhook PayPal recibido');
            jsonResponse(['message' => 'Webhook PayPal procesado']);
            
        } elseif ($provider === 'stripe') {
            // Webhook de Stripe
            // Implementar lógica similar para Stripe
            logWebhook('Webhook Stripe recibido');
            jsonResponse(['message' => 'Webhook Stripe procesado']);
            
        } else {
            jsonError('Proveedor de pago no válido', 400);
        }
        
    } catch (Exception $e) {
        logWebhook('Error procesando webhook', ['error' => $e->getMessage()]);
        jsonError('Error procesando webhook: ' . $e->getMessage(), 500);
    }
    
} else {
    jsonError('Método no permitido', 405);
}
?>

