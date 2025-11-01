# Debug de CORS - Guía de Solución

## 🔍 Diagnóstico

Si sigues teniendo problemas de CORS después de subir los archivos, sigue estos pasos:

### Paso 1: Probar el archivo de prueba simple

1. Sube `api/test-cors.php` al servidor
2. Abre en tu navegador: `http://tuabogadoenlinea.free.nf/apis/api/test-cors.php`
3. Deberías ver un JSON con `"cors_headers_set": true`

Si esto funciona, el problema está en los otros archivos.

### Paso 2: Verificar que los archivos se subieron correctamente

Verifica que estos archivos estén en el servidor en `/htdocs/apis/`:

- ✅ `api/planes.php` (debe tener el código OPTIONS al inicio)
- ✅ `api/auth.php`
- ✅ `api/compras.php`
- ✅ `api/consultas.php`
- ✅ `api/admin.php`
- ✅ `api/test-cors.php` (nuevo)

### Paso 3: Verificar desde la consola del navegador

Abre la consola (F12) y ejecuta:

```javascript
// Probar el archivo de prueba
fetch('http://tuabogadoenlinea.free.nf/apis/api/test-cors.php', {
  method: 'GET'
})
.then(r => r.json())
.then(d => console.log('CORS funciona:', d))
.catch(e => console.error('Error:', e));
```

### Paso 4: Verificar headers en Network Tab

1. Abre DevTools (F12) → Network
2. Intenta hacer la petición desde tu frontend
3. Selecciona la petición fallida
4. Ve a "Headers"
5. En "Response Headers", verifica si aparecen:
   - `Access-Control-Allow-Origin: *`
   - `Access-Control-Allow-Methods: ...`

### Paso 5: Verificar si hay errores de PHP

Abre directamente en el navegador:
```
http://tuabogadoenlinea.free.nf/apis/api/planes.php
```

Si ves errores de PHP, esos errores pueden estar causando que los headers no se envíen.

## 🛠️ Soluciones Alternativas

### Opción 1: Usar proxy en desarrollo

Si estás en desarrollo local, puedes usar un proxy en tu `vite.config.js` o `package.json`:

```javascript
// vite.config.js
export default {
  server: {
    proxy: {
      '/api': {
        target: 'http://tuabogadoenlinea.free.nf/apis',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '')
      }
    }
  }
}
```

### Opción 2: Configurar CORS en el servidor (si tienes acceso)

Si tienes acceso al `.htaccess` del servidor, asegúrate de que tenga:

```apache
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>
```

El `always` es importante para que se aplique incluso en errores.

### Opción 3: Verificar que no haya output antes de headers

Asegúrate de que NO haya:
- Espacios antes de `<?php`
- Caracteres BOM (Byte Order Mark)
- `echo` o `print` antes de los headers
- Errores de PHP que generen output

## ✅ Checklist Final

- [ ] Archivos subidos correctamente al servidor
- [ ] `test-cors.php` funciona cuando se abre directamente
- [ ] No hay errores de PHP en los archivos
- [ ] `.htaccess` tiene headers CORS configurados
- [ ] Los archivos PHP tienen `<?php` como primer carácter (sin espacios)

