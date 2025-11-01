# Solución CORS para Axios

## 🔍 Problema

Axios envía headers adicionales por defecto que hacen que el navegador envíe una petición **preflight (OPTIONS)** antes de la petición real. Si el servidor no responde correctamente a OPTIONS, CORS falla.

## ✅ Solución Implementada

Se agregaron headers adicionales necesarios para Axios:

```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: false");
header("Access-Control-Max-Age: 86400");
```

### Headers Importantes:

- `X-Requested-With`: Header que Axios envía por defecto
- `Accept`: Header que Axios envía para indicar qué tipos de respuesta acepta
- `Origin`: Header que el navegador envía automáticamente
- `Access-Control-Max-Age`: Cachea la respuesta preflight por 24 horas

## 📝 Configuración en Axios (Frontend)

Tu configuración actual está bien, pero puedes mejorarla:

```javascript
// utils/api.js
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://tuabogadoenlinea.free.nf/apis';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  // Asegurar que no se envíen credenciales si usas '*'
  withCredentials: false,
});

// Resto de tu código...
```

## 🧪 Verificación

### 1. Desde la Consola del Navegador

Abre DevTools (F12) → Console y ejecuta:

```javascript
fetch('http://tuabogadoenlinea.free.nf/apis/api/planes.php?page=1&limit=10')
  .then(r => r.json())
  .then(d => console.log('✅ Funciona:', d))
  .catch(e => console.error('❌ Error:', e));
```

### 2. Verificar Network Tab

1. Abre DevTools (F12) → Network
2. Filtra por "XHR" o "Fetch"
3. Intenta cargar planes desde tu frontend
4. Verifica que:
   - Primero hay una petición **OPTIONS** (preflight)
   - Luego la petición **GET** real
   - Ambas deben tener status 200
   - Ambas deben tener `Access-Control-Allow-Origin: *` en Response Headers

### 3. Si aún falla

Verifica en Network Tab:
- ¿La petición OPTIONS tiene status 200?
- ¿La petición OPTIONS tiene los headers CORS?
- ¿Cuál es el error exacto en la consola?

## 🔧 Alternativa: Proxy en Desarrollo

Si CORS sigue fallando, puedes usar un proxy en desarrollo:

### Vite (vite.config.js)

```javascript
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

Luego en tu código:
```javascript
const API_URL = '/api'; // En desarrollo usa el proxy
```

### React (package.json)

Si usas Create React App, agrega:
```json
{
  "proxy": "http://tuabogadoenlinea.free.nf/apis"
}
```

## ⚠️ Notas Importantes

1. **No uses `withCredentials: true`** si `Access-Control-Allow-Origin` es `*`
2. Los headers deben enviarse **antes** de cualquier output (incluso espacios)
3. La petición OPTIONS debe responder con 200 y sin body

## ✅ Checklist

- [ ] Headers CORS agregados en todos los archivos PHP
- [ ] Headers incluyen `X-Requested-With, Accept, Origin`
- [ ] OPTIONS responde con 200 y `Content-Length: 0`
- [ ] Archivos subidos al servidor
- [ ] Prueba desde Network Tab para verificar preflight

