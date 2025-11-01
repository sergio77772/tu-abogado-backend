# Verificación CORS - Pasos de Debug

## 🔍 El Error

"Response to preflight request doesn't pass access control check: No 'Access-Control-Allow-Origin' header is present"

Esto significa que la petición **OPTIONS** (preflight) no está recibiendo los headers CORS del servidor.

## ✅ Pasos para Verificar

### Paso 1: Verificar que los archivos estén en el servidor

1. Abre directamente en el navegador:
   ```
   http://tuabogadoenlinea.free.nf/apis/api/planes.php
   ```

2. Deberías ver un JSON (puede ser un error si no hay autenticación, pero debería tener headers CORS)

3. Abre DevTools (F12) → Network → Selecciona la petición → Headers
4. En "Response Headers" busca `Access-Control-Allow-Origin`

### Paso 2: Probar petición OPTIONS directamente

Abre la consola del navegador (F12) y ejecuta:

```javascript
fetch('http://tuabogadoenlinea.free.nf/apis/api/planes.php', {
  method: 'OPTIONS'
})
.then(r => {
  console.log('Status:', r.status);
  console.log('Headers:', [...r.headers.entries()]);
  return r.text();
})
.then(text => console.log('Body:', text))
.catch(e => console.error('Error:', e));
```

Deberías ver:
- Status: 200
- Headers incluyendo `Access-Control-Allow-Origin: *`

### Paso 3: Verificar archivo test-options.php

1. Sube `api/test-options.php` al servidor
2. Abre: `http://tuabogadoenlinea.free.nf/apis/api/test-options.php`
3. Prueba con axios desde tu frontend:

```javascript
axios.get('http://tuabogadoenlinea.free.nf/apis/api/test-options.php')
  .then(r => console.log('✅ Funciona:', r.data))
  .catch(e => console.error('❌ Error:', e));
```

## 🐛 Posibles Problemas

### 1. Archivos no subidos al servidor

**Solución:** Verifica que los archivos PHP actualizados estén en `/htdocs/apis/api/` en el servidor.

### 2. Output antes de headers (espacios, BOM)

**Solución:** Asegúrate de que NO haya:
- Espacios antes de `<?php`
- Líneas en blanco antes de `<?php`
- Caracteres BOM (Byte Order Mark)

El archivo debe comenzar EXACTAMENTE así:
```
<?php
// Headers CORS...
```

### 3. Servidor bloquea OPTIONS

**Solución:** Verifica el `.htaccess` - debería permitir OPTIONS:

```apache
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept, Origin"
</IfModule>
```

### 4. Error de PHP antes de headers

**Solución:** Si hay un error de PHP (warning, notice, etc.), puede impedir que se envíen headers. Verifica que no haya errores ejecutando el archivo directamente en el navegador.

## 🛠️ Solución Temporal: Proxy en Desarrollo

Si CORS sigue fallando, usa un proxy en desarrollo:

### Vite (vite.config.js)

```javascript
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://tuabogadoenlinea.free.nf/apis',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ''),
        configure: (proxy, options) => {
          proxy.on('proxyReq', (proxyReq, req, res) => {
            console.log('Proxying:', req.method, req.url);
          });
        }
      }
    }
  }
});
```

Luego en tu código:
```javascript
const API_URL = '/api'; // Usa el proxy en desarrollo
```

## ✅ Checklist Final

- [ ] Archivos PHP actualizados subidos al servidor
- [ ] No hay espacios/errores antes de `<?php`
- [ ] `.htaccess` permite OPTIONS
- [ ] Petición OPTIONS devuelve 200 desde consola
- [ ] Headers CORS visibles en Network Tab
- [ ] Si nada funciona, usar proxy en desarrollo

## 📞 Próximos Pasos

1. Ejecuta el test de OPTIONS desde la consola
2. Comparte el resultado (status, headers)
3. Verifica que los archivos estén subidos
4. Si todo falla, usa el proxy en desarrollo

