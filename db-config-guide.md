# Guía de Configuración de Base de Datos para InfinityFree

## ⚠️ Error Común: "No such file or directory"

Este error ocurre porque InfinityFree **NO permite conexiones MySQL con 'localhost'**. Debes usar el **host remoto de MySQL**.

## 📍 Cómo Obtener las Credenciales Correctas

### Paso 1: Accede al Panel de Control de InfinityFree

1. Ve a [InfinityFree Control Panel](https://members.infinityfree.com/)
2. Inicia sesión con tu cuenta
3. Selecciona tu sitio: `tuabogadoenlinea.free.nf`

### Paso 2: Ve a la Sección de MySQL

1. En el menú lateral, busca **"MySQL"** o **"Databases"**
2. Verás tu base de datos: `c2651511_distri`

### Paso 3: Encuentra el Host Remoto

InfinityFree te mostrará algo como:
- **Host remoto**: `sql305.infinityfree.com` (el número puede variar)
- **Usuario**: `c2651511_distri`
- **Contraseña**: `marowe35LO`
- **Base de datos**: `c2651511_distri`
- **Puerto**: `3306` (generalmente)

### Paso 4: Actualiza db.php

Actualiza el archivo `db.php` con el **host remoto** que encuentres:

```php
$host = 'sql305.infinityfree.com'; // El host remoto de tu panel
$db = 'c2651511_distri';
$user = 'c2651511_distri';
$pass = 'marowe35LO';
```

## 🔧 Alternativas si el Error Persiste

### Opción 1: Host con Puerto
```php
$host = 'sql305.infinityfree.com:3306';
```

### Opción 2: IP del Servidor MySQL
Si InfinityFree te proporciona una IP, úsala:
```php
$host = '185.27.134.10'; // Ejemplo, usa la IP que te den
```

### Opción 3: Verificar Credenciales
Asegúrate de que:
- El nombre de la base de datos sea exactamente `c2651511_distri`
- El usuario y contraseña sean correctos
- La base de datos esté activa en el panel

## ✅ Verificar la Conexión

Una vez actualizado, prueba:
```
http://tuabogadoenlinea.free.nf/apis/api/planes.php
```

Si sigue fallando, el archivo `db.php` mostrará un error más detallado con sugerencias.

## 📝 Nota Importante

**NO subas el archivo `db.php` con credenciales al repositorio público**. 

En el workflow de GitHub Actions, el archivo `db.php` está excluido. Debes:
1. Subirlo manualmente por FTP
2. O crear un `db.php` en el servidor directamente

## 🛠️ Crear db.php Directamente en el Servidor

1. Conéctate por FTP
2. Ve a `/htdocs/apis/`
3. Crea un archivo `db.php` con las credenciales correctas del panel de InfinityFree

