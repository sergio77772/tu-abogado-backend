# Documentación Swagger/OpenAPI

## 📚 Archivos Creados

1. **`swagger.yaml`** - Especificación OpenAPI 3.0 completa de la API
2. **`swagger-simple.html`** - Interfaz Swagger UI simple (recomendado)
3. **`swagger-ui.html`** - Interfaz Swagger UI alternativa

## 🚀 Cómo Usar

### Opción 1: Swagger Editor Online (Recomendado)

1. Ve a [Swagger Editor](https://editor.swagger.io/)
2. Copia el contenido de `swagger.yaml`
3. Pégalo en el editor
4. Podrás ver la documentación interactiva y probar los endpoints

### Opción 2: Servidor Local

1. Sube `swagger.yaml` y `swagger-simple.html` al servidor en `/htdocs/apis/`
2. Abre en tu navegador: `http://tuabogadoenlinea.free.nf/apis/swagger-simple.html`

### Opción 3: Usar Swagger UI desde CDN

El archivo `swagger-simple.html` ya está configurado para cargar desde un CDN. Solo necesitas:

1. Subir `swagger.yaml` al servidor
2. Actualizar la URL en `swagger-simple.html` (línea 16) si es necesario:
   ```javascript
   url: 'http://tuabogadoenlinea.free.nf/apis/swagger.yaml',
   ```
3. Abrir `swagger-simple.html` en el navegador

### Opción 4: Postman Import

1. Abre Postman
2. Ve a Import
3. Selecciona "File" o "Link"
4. Importa el archivo `swagger.yaml`
5. Todos los endpoints quedarán configurados automáticamente

## 🔑 Autenticación

Para probar endpoints que requieren autenticación:

1. Primero ejecuta el endpoint de **Login** (`/api/auth.php?action=login`)
2. Copia el `token` de la respuesta
3. Haz clic en el botón **"Authorize"** en la parte superior de Swagger UI
4. Pega el token en el campo "Value"
5. Haz clic en "Authorize" y luego "Close"
6. Ahora todos los endpoints autenticados usarán ese token automáticamente

## 📝 Notas

- La documentación incluye todos los endpoints de la API
- Cada endpoint tiene ejemplos de request/response
- Los parámetros están documentados con descripciones
- Incluye códigos de respuesta HTTP esperados

## 🛠️ Endpoints Documentados

- ✅ Autenticación (register/login)
- ✅ Planes (CRUD completo)
- ✅ Compras (crear, listar)
- ✅ Consultas (crear, responder, listar)
- ✅ Panel Administrativo (stats, users, abogados, compras)

## 🔄 Actualizar la Documentación

Si agregas nuevos endpoints o modificas existentes, actualiza el archivo `swagger.yaml` siguiendo el mismo formato.

