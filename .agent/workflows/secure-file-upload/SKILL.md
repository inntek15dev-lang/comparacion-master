---
name: secure-file-upload
description: Reglas y validación para subida segura de archivos en OvalControl
---


# Subida Segura de Archivos

## Arquitectura

```
config/file-upload.php      ← Configuración centralizada
app/Traits/ValidatesFileUpload.php  ← Trait reutilizable
```

---

## Configuración Centralizada

Archivo: `config/file-upload.php`

### Tipos Permitidos Globalmente

| Categoría | Extensiones | MIME Types |
|-----------|-------------|------------|
| Documentos | pdf | application/pdf |

### Contextos Disponibles

| Contexto | Carpeta | Tipos | Max Size |
|----------|---------|-------|----------|
| `requerimientos` | adjuntos | PDF | 30MB |
| `observaciones` | adjuntos_chat | PDF | 30MB |
| `pausas` | adjuntos_pausa | PDF | 30MB |
| `reportes` | reportes | PDF | 30MB |

---

## Uso del Trait

### 1. Importar el Trait

```php
use App\Traits\ValidatesFileUpload;

class MiComponente extends Component
{
    use WithFileUploads;
    use ValidatesFileUpload;
```

### 2. Obtener Regla de Validación

```php
protected function rules()
{
    // Por defecto ahora valida PDF y max 30MB
    return [
        'archivo' => $this->getFileValidationRule('requerimientos'), 
    ];
}
```

### 3. Validar y Guardar

```php
public function guardar()
{
    $this->validate();
    
    // Valida que sea PDF, <30MB, contra blacklist y guarda
    $path = $this->validateAndStoreFile($this->archivo, 'requerimientos');
    
    // Guardar en BD
    Modelo::create([
        'file_path' => $path,
        // ...
    ]);
}
```

---

## Agregar Nuevo Contexto

1. Editar `config/file-upload.php`:

```php
'contexts' => [
    // ... existentes ...
    
    'mi_nuevo_contexto' => [
        'disk' => 'public',
        'path' => 'mi_carpeta',
        'max_size' => 30720,  // 30MB en KB
        'allowed_mimes' => ['pdf'], 
        'allowed_mimetypes' => ['application/pdf'],
    ],
],
```

2. Usar en componente:

```php
$path = $this->validateAndStoreFile($archivo, 'mi_nuevo_contexto');
```

---

## Checklist Nuevo Módulo con Subida

- [ ] Agregar `use WithFileUploads` (Livewire)
- [ ] Agregar `use ValidatesFileUpload` (Trait)
- [ ] Definir contexto en `config/file-upload.php` si es necesario
- [ ] Usar `$this->getFileValidationRule('contexto')` en rules
- [ ] Usar `$this->validateAndStoreFile($archivo, 'contexto')` para guardar
- [ ] Mostrar errores con `@error('archivo')`

---

## Seguridad

### Blacklist (NUNCA permitidos)
```
php, phtml, phar, php3, php4, php5, php7, phps
exe, bat, sh, cmd, com, msi
htaccess, htpasswd, env
js, vbs, wsf
# Y cualquier otro que no sea PDF
```

### Validación Doble
1. **Extensión** - via `mimes:pdf`
2. **Contenido real** - via `mimetypes:application/pdf`

Esto asegura que SOLO se procesen archivos PDF legítimos hasta 30MB.
