# Backend - Tu Abogado en Línea

Backend API REST para la plataforma "Tu Abogado en Línea" desarrollado en PHP.

## Estructura del Proyecto

```
tu-abogado-backend/
├── api/
│   ├── auth.php          # Autenticación (registro/login)
│   ├── planes.php        # Gestión de planes
│   ├── compras.php       # Gestión de compras
│   ├── consultas.php     # Gestión de consultas
│   ├── admin.php         # Panel administrativo
│   └── webhook.php       # Webhooks para pagos
├── config/
│   └── config.php        # Configuración general
├── helpers/
│   ├── auth.php          # Funciones de autenticación
│   └── response.php      # Funciones para respuestas HTTP
├── database/
│   └── schema.sql        # Esquema de base de datos
├── db.php                # Conexión a base de datos
├── index.php             # Router principal
└── .htaccess             # Configuración Apache
```

## Instalación

### 1. Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, PDO_MySQL, mbstring

### 2. Configuración de Base de Datos

1. Crear la base de datos:
```sql
CREATE DATABASE c2651511_distri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Ejecutar el script de esquema:
```bash
mysql -u c2651511_distri -p c2651511_distri < database/schema.sql
```

O ejecutar manualmente el contenido de `database/schema.sql` en tu base de datos.

### 3. Configuración de la Aplicación

Editar `db.php` con tus credenciales de base de datos:
```php
$host = 'localhost';
$db = 'c2651511_distri';
$user = 'c2651511_distri';
$pass = 'tu_contraseña';
```

Editar `config/config.php` para configurar:
- CORS (orígenes permitidos)
- JWT_SECRET (clave secreta para tokens)
- Otras constantes según necesites

### 4. Permisos de Directorio

Asegúrate de que los directorios tengan permisos de escritura:
```bash
chmod 755 logs/
chmod 755 uploads/
```

## Uso de la API

### Base URL
Para servidores InfinityFree, la estructura es:
- Archivos deben estar en: `/htdocs/apis/`
- URLs accesibles: `http://tuabogadoenlinea.free.nf/apis/`

### Endpoints Base
```
http://tuabogadoenlinea.free.nf/apis/
```

### Autenticación

La mayoría de los endpoints requieren autenticación mediante JWT Bearer Token.

#### Registro
```http
POST /api/auth.php?action=register
Content-Type: application/json

{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "contraseña": "password123",
  "rol": "cliente"
}
```

#### Login
```http
POST /api/auth.php?action=login
Content-Type: application/json

{
  "email": "juan@example.com",
  "contraseña": "password123"
}
```

Respuesta:
```json
{
  "message": "Login exitoso",
  "user": {
    "id": 1,
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "rol": "cliente"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

Usar el token en headers:
```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Endpoints Principales

#### Planes
- `GET /api/planes.php` - Listar planes disponibles
- `GET /api/planes.php?id=1` - Obtener un plan específico
- `POST /api/planes.php` - Crear plan (admin)
- `PUT /api/planes.php?id=1` - Actualizar plan (admin)

#### Compras
- `GET /api/compras.php` - Listar compras del usuario
- `POST /api/compras.php` - Crear una compra (cliente)
- `PUT /api/compras.php?id=1` - Actualizar compra (admin)

#### Consultas
- `GET /api/consultas.php` - Listar consultas del usuario
- `GET /api/consultas.php?action=disponibles` - Ver consultas disponibles (cliente)
- `GET /api/consultas.php?action=pendientes` - Ver consultas pendientes (abogado)
- `POST /api/consultas.php` - Crear consulta (cliente)
- `PUT /api/consultas.php?id=1` - Responder/Actualizar consulta

#### Administración
- `GET /api/admin.php?action=stats` - Estadísticas generales
- `GET /api/admin.php?action=users` - Listar usuarios
- `GET /api/admin.php?action=abogados` - Listar abogados
- `GET /api/admin.php?action=compras` - Listar todas las compras

## Flujo de Uso

1. **Cliente se registra** → `POST /api/auth.php?action=register`
2. **Cliente compra un plan** → `POST /api/compras.php`
3. **Pago confirmado vía webhook** → `POST /api/webhook.php?provider=mercadopago`
4. **Cliente crea una consulta** → `POST /api/consultas.php`
5. **Abogado responde** → `PUT /api/consultas.php?id=1`
6. **Cliente ve la respuesta** → `GET /api/consultas.php?id=1`

## Webhooks de Pago

Configurar el webhook en Mercado Pago/PayPal/Stripe para que apunte a:
```
POST http://tu-dominio.com/api/webhook.php?provider=mercadopago
```

## Seguridad

- ✅ Contraseñas encriptadas con bcrypt
- ✅ JWT para autenticación
- ✅ Validación de roles y permisos
- ✅ Prevención de SQL Injection (PDO prepared statements)
- ⚠️ En producción, configurar HTTPS obligatorio
- ⚠️ Cambiar JWT_SECRET en `config/config.php`
- ⚠️ Configurar CORS con dominios específicos

## Notas de Desarrollo

- El sistema de JWT está simplificado. Para producción, considerar usar una librería como `firebase/php-jwt`
- Los webhooks necesitan validación adicional según el proveedor de pago
- Se recomienda agregar rate limiting para prevenir abusos
- Considerar implementar caché para consultas frecuentes

## Licencia

Propietario - Tu Abogado en Línea

