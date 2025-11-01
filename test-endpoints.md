# Endpoints para Probar la API

Base URL: `http://tuabogadoenlinea.free.nf`

## Endpoints Públicos (Sin autenticación)

### 1. Información de la API
```http
GET http://tuabogadoenlinea.free.nf/api/
```

### 2. Listar Planes
```http
GET http://tuabogadoenlinea.free.nf/api/planes.php
```

### 3. Registrar Usuario
```http
POST http://tuabogadoenlinea.free.nf/api/auth.php?action=register
Content-Type: application/json

{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "contraseña": "password123",
  "rol": "cliente"
}
```

### 4. Login
```http
POST http://tuabogadoenlinea.free.nf/api/auth.php?action=login
Content-Type: application/json

{
  "email": "juan@example.com",
  "contraseña": "password123"
}
```

## Prueba Rápida desde Navegador

Simplemente abre en tu navegador:
```
http://tuabogadoenlinea.free.nf/api/
```

O para ver los planes:
```
http://tuabogadoenlinea.free.nf/api/planes.php
```

## Prueba con cURL

### Obtener información de la API:
```bash
curl http://tuabogadoenlinea.free.nf/api/
```

### Listar planes:
```bash
curl http://tuabogadoenlinea.free.nf/api/planes.php
```

### Registrar usuario:
```bash
curl -X POST "http://tuabogadoenlinea.free.nf/api/auth.php?action=register" \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Juan Pérez","email":"juan@example.com","contraseña":"password123","rol":"cliente"}'
```

### Login:
```bash
curl -X POST "http://tuabogadoenlinea.free.nf/api/auth.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"email":"juan@example.com","contraseña":"password123"}'
```

## Prueba con Postman

1. Importa la colección o crea una nueva request
2. URL: `http://tuabogadoenlinea.free.nf/api/planes.php`
3. Método: GET
4. Send

## Notas

- Asegúrate de que la base de datos esté configurada y el esquema ejecutado
- El usuario admin por defecto está en el schema.sql (email: admin@tuabogado.com, contraseña: admin123)
- Para endpoints que requieren autenticación, agrega el header: `Authorization: Bearer {token}`

