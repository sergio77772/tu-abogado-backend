# Solución de Problemas CORS

## 🔧 Cambios Realizados

1. ✅ Mejorada la función `setCorsHeaders()` en `helpers/response.php`
2. ✅ Agregado `Access-Control-Max-Age` para cachear preflight
3. ✅ Mejorado el manejo de peticiones OPTIONS (preflight)

## 🧪 Pruebas

### 1. Probar CORS directamente

Abre en tu navegador:
```
http://tuabogadoenlinea.free.nf/apis/cors-test.php
```

Deberías ver un JSON confirmando que CORS está configurado.

### 2. Probar desde la consola del navegador

Abre la consola de tu navegador (F12) y ejecuta:

```javascript
fetch('http://tuabogadoenlinea.free.nf/apis/api/planes.php')
  .then(response => response.json())
  .then(data => console.log('Success:', data))
  .catch(error => console.error('Error:', error));
```

### 3. Verificar Headers en Network Tab

1. Abre DevTools (F12)
2. Ve a la pestaña "Network"
3. Intenta cargar los planes desde tu frontend
4. Selecciona la petición fallida
5. Verifica en "Response Headers" si aparecen los headers CORS:
   - `Access-Control-Allow-Origin: *`
   - `Access-Control-Allow-Methods: ...`
   - `Access-Control-Allow-Headers: ...`

## 🐛 Problemas Comunes

### Error: "No 'Access-Control-Allow-Origin' header"

**Solución:**
1. Asegúrate de que `helpers/response.php` esté actualizado
2. Verifica que todos los archivos de API llamen a `setCorsHeaders()` al inicio
3. Verifica que no haya ningún `echo` o `print` antes de los headers

### Error: "Preflight request failed"

**Solución:**
1. Asegúrate de que `handleOptions()` se llame antes de cualquier otra lógica
2. Verifica que el servidor responda 200 a peticiones OPTIONS

### Error: "CORS policy blocked"

**Solución:**
1. Si tu frontend está en HTTPS y el backend en HTTP, puede haber problemas
2. Si el frontend está en un dominio diferente, verifica que CORS permita ese dominio

## ✅ Checklist

- [ ] Archivo `helpers/response.php` actualizado
- [ ] Todos los endpoints llaman `setCorsHeaders()` al inicio
- [ ] Todos los endpoints llaman `handleOptions()` después de `setCorsHeaders()`
- [ ] Archivo `.htaccess` tiene headers CORS configurados
- [ ] No hay `echo` o `print` antes de los headers en ningún archivo PHP

## 📝 Nota sobre Producción

En producción, cambia en `config/config.php`:
```php
define('CORS_ALLOWED_ORIGINS', 'https://tu-dominio-frontend.com');
```

En lugar de `'*'` para mayor seguridad.

