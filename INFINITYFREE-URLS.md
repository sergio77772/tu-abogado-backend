# URLs Correctas para InfinityFree

## ⚠️ Importante sobre InfinityFree

En InfinityFree, los archivos deben estar dentro de `/htdocs/` para ser accesibles por web.

Según tu estructura actual, los archivos están en: `/htdocs/apis/`

## 📍 URLs Correctas

### Base URL
```
http://tuabogadoenlinea.free.nf/apis/
```

### Endpoints de Prueba

#### 1. Test del servidor
```
http://tuabogadoenlinea.free.nf/apis/test.php
```

#### 2. Test del API
```
http://tuabogadoenlinea.free.nf/apis/api/test.php
```

#### 3. Información de la API
```
http://tuabogadoenlinea.free.nf/apis/index.php
```

#### 4. Listar Planes
```
http://tuabogadoenlinea.free.nf/apis/api/planes.php
```

#### 5. Autenticación - Registro
```
POST http://tuabogadoenlinea.free.nf/apis/api/auth.php?action=register
Content-Type: application/json

{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "contraseña": "password123",
  "rol": "cliente"
}
```

#### 6. Autenticación - Login
```
POST http://tuabogadoenlinea.free.nf/apis/api/auth.php?action=login
Content-Type: application/json

{
  "email": "juan@example.com",
  "contraseña": "password123"
}
```

## 🧪 Prueba Rápida desde el Navegador

Abre esta URL en tu navegador:
```
http://tuabogadoenlinea.free.nf/apis/api/test.php
```

O para ver los planes:
```
http://tuabogadoenlinea.free.nf/apis/api/planes.php
```

## 📝 Nota sobre .htaccess

El archivo `.htaccess` debe estar en:
- `/htdocs/apis/.htaccess` (para que afecte solo a los archivos dentro de `/apis/`)
- O `/htdocs/.htaccess` (para afectar todo el sitio)

## 🔧 Configuración del Workflow

Asegúrate de que el workflow suba los archivos a `htdocs/apis/`:

```yaml
server-dir: htdocs/apis/
```

## ✅ Checklist

- [ ] Archivos subidos a `/htdocs/apis/`
- [ ] `.htaccess` en la ubicación correcta
- [ ] Base de datos configurada
- [ ] Esquema SQL ejecutado
- [ ] Probar `http://tuabogadoenlinea.free.nf/apis/test.php` primero

