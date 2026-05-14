# Documentación Técnica - Master Documentación Controlada

## 1. Visión General
Este proyecto es un sistema de gestión y control documental desarrollado en Laravel. Su objetivo principal es permitir a una empresa mandante definir requisitos documentales para sus contratistas, trabajadores y vehículos, y gestionar el flujo de carga, revisión y validación de estos documentos.

## 2. Stack Tecnológico

### Backend
- **Framework**: Laravel 12.x (Nota: Verificar versión exacta, `composer.json` indica `^12.0` lo cual es inusual, podría ser una versión de desarrollo o un typo, se asume compatibilidad con Laravel 11/12).
- **Lenguaje**: PHP 8.2+
- **Base de Datos**: MySQL (Infrerido por el uso estándar de Laravel, aunque no se verificó `.env`).

### Frontend
- **Framework**: Livewire 3.4 (Renderizado del lado del servidor con reactividad).
- **Estilos**: TailwindCSS.
- **Componentes**: Blade Templates + Componentes Livewire.

### Librerías Clave
- **Spatie Permission**: Gestión de roles y permisos (`ASEM_Admin`, `Mandante_Admin`, `Contratista_Admin`, etc.).
- **Spatie Activitylog**: Registro de auditoría de cambios en modelos.
- **Maatwebsite Excel**: Exportación e importación de datos.
- **Barryvdh DomPDF**: Generación de documentos PDF.
- **Laravel Breeze**: Scaffolding de autenticación.

## 3. Arquitectura del Sistema

El sistema sigue el patrón MVC (Modelo-Vista-Controlador) tradicional de Laravel, pero fuertemente orientado a componentes **Livewire** para la interactividad.

### Estructura de Directorios Clave
- `app/Models`: Modelos Eloquent que representan las entidades de negocio.
- `app/Livewire`: Componentes de lógica de presentación y negocio. Aquí reside la mayor parte de la lógica de la aplicación.
- `resources/views/livewire`: Vistas Blade asociadas a los componentes Livewire.
- `routes/web.php`: Definición de rutas y middleware de seguridad.

## 4. Modelo de Datos Principal

### Entidades Core
- **Mandante**: La empresa principal que define las reglas.
- **Contratista**: Empresa externa que presta servicios al Mandante.
- **Trabajador / Vehículo / Maquinaria**: Entidades "controlables" que requieren documentación.

### Motor de Reglas (`ReglaDocumental`)
El corazón del sistema es el modelo `ReglaDocumental`. Define:
- **Qué**: `nombre_documento_id` (Tipo de documento requerido).
- **A Quién**: `tipo_entidad_controlada_id` (Empresa, Persona, Vehículo, etc.).
- **Condiciones**: `aplica_empresa_condicion_id`, `aplica_persona_condicion_id` (Filtros para aplicar la regla solo a ciertos subconjuntos).
- **Validación**: Criterios de aceptación (`ReglaDocumentalCriterio`) y flujo de aprobación (`requiere_validacion_mandante`).

### Gestión de Documentos (`DocumentoCargado`)
Representa un documento subido al sistema.
- Almacena el archivo físico (`ruta_archivo`).
- Mantiene una "foto" (snapshot) de las reglas al momento de la carga para asegurar integridad histórica.
- Gestiona estados de validación: `Pendiente`, `Aprobado`, `Rechazado`.
- Calcula vencimientos y estados de vigencia.

## 5. Flujos de Información

### 5.1. Carga de Documentos
1. El **Contratista** accede a su portal.
2. El sistema consulta las `ReglasDocumentales` activas para sus entidades (Trabajadores, Vehículos).
3. Se genera una lista de "Pendientes".
4. El usuario sube el archivo -> Se crea un `DocumentoCargado`.

### 5.2. Validación
1. El documento entra en una cola de validación.
2. **Validación ASEM**: Un validador de ASEM revisa cumplimiento básico.
3. **Validación Mandante** (Opcional): Si la regla lo exige, pasa a una segunda revisión por parte del Mandante.
4. Si se aprueba -> El documento queda "Vigente".
5. Si se rechaza -> Se notifica al contratista con el motivo (`TextoRechazo`).

### 5.3. Gestión de Entidades (Panel de Operación)
El componente `PanelOperacion` es el núcleo de la gestión diaria. Implementa una lógica de "Contexto" jerárquico para filtrar la información visible.

#### Lógica de Filtrado por Perfil
1.  **ASEM_Admin (Superusuario)**:
    - Flujo: Selecciona Mandante -> Selecciona Contratista -> Selecciona Lugar -> Selecciona U.O.
    - Visibilidad: Ve todos los filtros.
2.  **Mandante_Admin**:
    - Flujo: Mandante fijo (el suyo) -> Selecciona Contratista -> Selecciona Lugar -> Selecciona U.O.
    - Visibilidad: Filtro de Mandante oculto (fijo). Ve filtro "Seleccione Contratista".
3.  **Contratista_Admin**:
    - Flujo: Contratista fijo (el suyo) -> Selecciona Mandante (Principal) -> Selecciona Lugar -> Selecciona U.O.
    - Visibilidad: Filtro de Contratista oculto (fijo). Ve filtro "Filtre por Principal".

#### Manejo de Estado (State Management)
- **Contexto**: Al cambiar un filtro superior (ej. Mandante), se resetean los inferiores (Contratista, Lugar) para mantener la consistencia.
- **Detección de Huérfanos**: Métodos privados (`detectarTrabajadoresHuerfanos`, etc.) escanean la base de datos en busca de entidades asignadas a lugares que ya no corresponden al contrato actual, alertando visualmente al usuario.

## 6. Seguridad y Roles

- **ASEM_Admin**: Superusuario. Configura el sistema global.
- **Mandante_Admin**: Configura reglas y ve reportes de su empresa.
- **Contratista_Admin**: Gestiona su empresa y carga documentos.
- **Validadores**: Roles específicos para la tarea de revisión documental.

## 7. Puntos de Atención para Mantenimiento
- **Migraciones**: No se encontraron migraciones estándar en `database/migrations`. Revisar carpeta de backup o dumps de esquema.
- **Versión de Laravel**: La dependencia `^12.0` en `composer.json` es atípica. Verificar compatibilidad al actualizar.
- **Lógica en Modelos**: Mucha lógica de negocio (como cálculo de vencimientos) reside en los Modelos (`DocumentoCargado`) y no en Servicios, lo cual es común en Laravel pero debe cuidarse para no "engordar" los modelos.
