# Guía de Despliegue: Regularización de Migraciones

## ⚠️ Situación Crítica
Acabamos de crear archivos de migración para tablas que **YA EXISTEN** en Pre-Producción y Producción.
Si ejecutas `php artisan migrate` directamente en esos servidores, Laravel intentará crear las tablas de nuevo y fallará con error: `Table already exists`.

## 🚀 Estrategia de Despliegue

El objetivo es decirle a Laravel en Pre-Prod/Prod: *"Estas migraciones ya están listas, no las ejecutes, solo regístralas"*.

### Paso 1: Preparación (En Local)
Asegúrate de que todos los archivos nuevos en `database/migrations` estén confirmados en Git.

```bash
git add database/migrations
git commit -m "Regularización de migraciones: Ingeniería inversa de BD"
git push origin main
```

### Paso 2: Despliegue en Pre-Producción / Producción

Conéctate a tu servidor y sigue estos pasos:

1.  **Respaldo de Base de Datos** (¡OBLIGATORIO!):
    Antes de tocar nada, exporta tu base de datos actual.
    ```bash
    mysqldump -u usuario -p nombre_bd > respaldo_antes_de_migrar.sql
    ```

2.  **Actualizar Código**:
    ```bash
    cd /ruta/a/tu/proyecto
    git pull origin main
    ```

3.  **Sincronizar Migraciones (El Truco)**:
    En lugar de `php artisan migrate`, ejecutaremos un script para marcar todo como "hecho".
    Abre la consola interactiva de Laravel:
    ```bash
    php artisan tinker
    ```

    Pega el siguiente bloque de código dentro de Tinker:

    ```php
    // Obtener todos los archivos de migración
    $files = glob(database_path('migrations/*.php'));
    foreach ($files as $file) {
        $migrationName = basename($file, '.php');
        // Verificar si ya existe en la tabla migrations
        $exists = \Illuminate\Support\Facades\DB::table('migrations')
            ->where('migration', $migrationName)
            ->exists();
        
        if (!$exists) {
            \Illuminate\Support\Facades\DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => 1
            ]);
            echo "Marcada como lista: $migrationName\n";
        }
    }
    ```
    *Esto llenará la tabla `migrations` con los nombres de los archivos que acabamos de generar, sin intentar crear las tablas físicas.*

4.  **Verificación**:
    Sal de Tinker (`exit`) y ejecuta:
    ```bash
    php artisan migrate:status
    ```
    Deberías ver todas las migraciones con estado "Ran" (Ejecutada).

### Paso 3: Futuros Despliegues
A partir de ahora, para **nuevos cambios** (ej. agregar una columna), crea una migración normal (`php artisan make:migration ...`) y despliega usando el comando estándar:
```bash
php artisan migrate --force
```
Ya no necesitarás el script de Tinker, porque el sistema de migraciones estará 100% sincronizado.

## ✅ Checklist de Seguridad
- [ ] ¿Hiciste backup de la BD?
- [ ] ¿Probaste esto primero en Pre-Producción?
- [ ] ¿Verificaste que `php artisan migrate:status` no muestre pendientes?
