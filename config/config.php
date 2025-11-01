<?php
// Configuración general de la aplicación

// Configuración de CORS
define('CORS_ALLOWED_ORIGINS', '*'); // En producción, especificar dominios concretos
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization');

// Configuración de JWT (para autenticación)
define('JWT_SECRET', 'tu_clave_secreta_muy_segura_aqui_cambiar_en_produccion');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 86400); // 24 horas en segundos

// Configuración de paginación
define('DEFAULT_PAGE', 1);
define('DEFAULT_LIMIT', 10);

// Configuración de uploads
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Roles de usuario
define('ROLE_CLIENTE', 'cliente');
define('ROLE_ABOGADO', 'abogado');
define('ROLE_ADMIN', 'admin');

// Estados
define('ESTADO_CONSULTA_ABIERTA', 'abierta');
define('ESTADO_CONSULTA_RESPONDIDA', 'respondida');
define('ESTADO_CONSULTA_CERRADA', 'cerrada');

define('ESTADO_COMPRA_PENDIENTE', 'pendiente');
define('ESTADO_COMPRA_PAGADA', 'pagada');
define('ESTADO_COMPRA_CANCELADA', 'cancelada');
?>

