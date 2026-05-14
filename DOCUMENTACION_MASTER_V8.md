# DOCUMENTACIÓN MAESTRA - SISTEMA WEB DE VALIDACIÓN DOCUMENTAL (V8.0)

## 1. VISIÓN GENERAL DEL PROYECTO

### 1.1. Objetivo
Plataforma web centralizada, robusta y automatizada para la gestión, validación y control de documentación de empresas contratistas, trabajadores y vehículos. Su fin es asegurar el cumplimiento normativo, minimizar riesgos laborales y legales, y digitalizar los flujos de trabajo entre Mandantes y Contratistas.

### 1.2. Actores Principales
*   **ASEM (Administrador y Validador):**
    *   **ASEM_Admin:** Superusuario con control total. Configura el sistema, gestiona reglas documentales, realiza acciones masivas (asignación, revalidación, modificación de vencimientos) y supervisa la operación global.
    *   **ASEM_Validator:** Rol operativo encargado de la revisión técnica y aprobación/rechazo de documentos.
*   **Empresa Contratista (Contratista_Admin):** Gestiona los datos de su empresa y sus activos (trabajadores, vehículos, maquinaria). Es responsable de la carga de documentación y de subsanar rechazos.
*   **Empresa Mandante (Mandante_Admin):** Define las necesidades documentales y supervisa el cumplimiento de sus contratistas. Puede tener validadores internos y acceder a paneles de supervisión estratégica.

## 2. ARQUITECTURA TECNOLÓGICA

### 2.1. Stack Principal
*   **Backend:** Laravel v12.x (PHP 8.2+)
*   **Frontend:** Livewire v3.x (Renderizado reactivo del lado del servidor)
*   **Base de Datos:** MySQL
*   **Estilos:** Tailwind CSS
*   **Autenticación & Roles:** Laravel Breeze + Spatie/laravel-permission

### 2.2. Principios de Desarrollo (PCD)
*   **Cero Suposiciones:** Toda implementación debe basarse en requerimientos explícitos.
*   **El Código Completo:** Se entrega siempre el código íntegro de los archivos modificados.
*   **Claridad Visual Absoluta:** La interfaz debe ser explícita (texto sobre íconos), de alto contraste y sin ambigüedades, priorizando la accesibilidad para usuarios con discapacidad visual ("Maestro").

## 3. MODELOS CORE Y CICLO DE VIDA DOCUMENTAL

### 3.1. DocumentoCargado (`App\Models\DocumentoCargado`)
Representa la instancia de un documento subido al sistema.
*   **Estados de Validación (`estado_validacion`):**
    *   `Pendiente`: Recién cargado, espera revisión.
    *   `Asignado`: En cola de un validador específico.
    *   `Aprobado` / `Rechazado`: Resultado final.
    *   `Archivado-Revalidado`: Documento histórico que fue reemplazado por un proceso de revalidación.
    *   `Revisado-Revalidado`: Nuevo estado para documentos que provienen de una revalidación y han sido procesados.
*   **Gestión de Vencimientos:**
    *   `fecha_vencimiento`: Fecha límite de validez.
    *   **Modificación de Vencimiento:** Funcionalidad para ASEM_Admin que permite alterar la fecha original.
        *   `es_vencimiento_modificado` (boolean): Flag de auditoría.
        *   `motivo_modificacion_vencimiento`: Justificación obligatoria.
        *   `ruta_justificativo_modificacion`: Evidencia opcional (archivo).
        *   **Visualización:** El estado de vigencia muestra el sufijo `-Modificado` (ej. `Vigente-Modificado`).
*   **Snapshots:** Al momento de la carga/revalidación, se guardan copias de las reglas y criterios vigentes (`*_snapshot`) para asegurar la integridad histórica de la validación.
*   **Lógica Perseguidor / Por Vinculación (`trabajador_vinculacion_id`):**
    *   Campo nullable introducido en **V8.41**. Controla si un documento de trabajador es compartido entre vinculaciones o es exclusivo de una.
    *   **`trabajador_vinculacion_id = NULL` + `es_perseguidor = true`**: El documento es de la **persona** (ej: Cédula, Certificado Médico). Un único documento aprobado satisface a **todas** las vinculaciones del trabajador.
    *   **`trabajador_vinculacion_id = ID` + `es_perseguidor = false`**: El documento es del **puesto/contrato** (ej: Contrato de Trabajo con cargo específico). Cada vinculación requiere su propio documento **independiente**.
    *   La palanca de control es el flag `es_perseguidor` del módulo **Criticidad General** (`/gestion/criticidad-general`).
    *   **Fallback seguro**: Documentos sin `trabajador_vinculacion_id` (históricos) se comportan como perseguidores. No se requiere migración de datos.
    *   **Alcance**: Solo aplica a entidades tipo `Trabajador`. Vehículos, Maquinarias, Embarcaciones y Empresas no se ven afectados.

### 3.2. Contratista (`App\Models\Contratista`)
Entidad central que agrupa recursos.
*   **Servicios por VinculaciÃƒÂ³n:** Los servicios de **AcreditaciÃƒÂ³n** y **VerificaciÃƒÂ³n** se gestionan de forma independiente para cada Unidad Organizacional asignada al contratista (ver tabla pivote infra).
*   **Estado:** `is_active` determina el acceso global a la plataforma del contratista y sus usuarios administradores.
*   **JerarquÃƒÂ­a:** Soporta estructura de `Contratista Principal` y `Sub-Contratistas` mediante relaciones recursivas (`subContratistasAprobados`).
*   **Jerarquía:** Soporta estructura de `Contratista Principal` y `Sub-Contratistas` mediante relaciones recursivas (`subContratistasAprobados`).
*   **Vinculación:** Relación muchos-a-muchos con `Mandante` a través de `ContratistaUnidadOrganizacional`, gestionada vía Solicitudes.

### 3.3. Ciclo de Vida y Transiciones a "Archivado"
El sistema gestiona el reemplazo de documentos para mantener la trazabilidad histórica. Un documento pasa al estado `Archivado` (o sus variantes) en los siguientes momentos:

1.  **Carga de Nueva Versión (Por Contratista):**
    *   **Si reemplaza a un documento "Pendiente":** El anterior se elimina físicamente (Hard Delete).
    *   **Si reemplaza a un documento "Rechazado" o "Vencido":** El anterior pasa inmediatamente a `Archivado`.
    *   **Si reemplaza a un documento "Vigente/Aprobado":** El anterior **permanece vigente** mientras el nuevo está en revisión. El nuevo documento se vincula mediante `reemplaza_a_id`. Solo cuando el nuevo documento es **Aprobado** por ASEM, el anterior pasa automáticamente a `Archivado`.

2.  **Procesos de Revalidación (Por ASEM):**
    *   Al iniciar una revalidación (individual o masiva), el documento original pasa inmediatamente a `Archivado-Revalidado` y se crea un clon en estado `Asignar-Revalidar`.

3.  **Archivado Manual/Automático:**
    *   Documentos de onboarding completados pueden pasar a `Archivado` mediante procesos de limpieza.

### 3.4. Contratista Unidad Organizacional (Pivote) (`App\Models\ContratistaUnidadOrganizacional`)
Representa la vinculación operativa entre un contratista y un lugar de trabajo o unidad organizacional de un mandante. Es el nivel donde se define el servicio real.

**Campos Clave:**
### 3.5. Regla Documental e Ãƒï¿½ndice Mensual de Carga (IMC) (`App\Models\ReglaDocumental`)
Define la exigibilidad de un documento y su peso administrativo.
- **Filtros de Aplicabilidad Extendidos**: Las reglas pueden filtrarse por Empresa, Persona (Cargo, Nacionalidad, Permanencia), VehÃƒÂ­culo (Tipo, Sub-Tipo, **Condiciones**), Maquinaria, EmbarcaciÃƒÂ³n y Tenencia.
- **LÃƒÂ³gica de VehÃƒÂ­culos en Reserva**: El sistema permite que un recurso (Trabajador/VehÃƒÂ­culo) exista en estado de **"Reserva"** (sin UO ni Lugar de Trabajo asignado). En este estado, el mandante se deduce a travÃƒÂ©s del Sub-Tipo de VehÃƒÂ­culo.
- **LÃƒÂ³gica Unificada de SelecciÃƒÂ³n**:
    - **SIN SELECCIÃƒâ€œN = APLICA A TODOS**: Si un filtro multi-selector queda vacÃƒÂ­o, la regla aplica de forma universal a todos los elementos de esa categorÃƒÂ­a.
    - **Con SelecciÃƒÂ³n**: Filtra estrictamente por los elementos marcados.
- **`imc_meses_estimados`**: Campo manual (float) para definir la vigencia en meses cuando no es estÃƒÂ¡ndar.
- **Accessor `imc` (`getImcAttribute`)**: Calcula la carga mensual prorrateada.
    - **FÃƒÂ³rmula**: `1 / meses_de_vigencia`.
    - **CÃƒÂ¡lculo AutomÃƒÂ¡tico**: Si tiene `dias_validez_documento`, se calcula como `1 / (dias / 30.44)`.
    - **CÃƒÂ¡lculo Manual**: Prioriza `imc_meses_estimados` si estÃƒÂ¡ presente.
    - **Ajuste RÃƒÂ¡pido**: IncorporaciÃƒÂ³n de botones interactivos (+/-) directamente en la tabla de gestiÃƒÂ³n para modificar la vigencia estimada sin entrar al modal.
- **VisualizaciÃƒÂ³n por Colores**:
    - Ã°Å¸â€Â´ **Rojo** (Ã¢â€°Â¥ 0.5): Alta carga (ej: mensual).
    - Ã°Å¸Å¸Â¡ **Ãƒï¿½mbar** (0.1 - 0.5): Carga media (ej: semestral).
    - Ã°Å¸Å¸Â¢ **Verde** (< 0.1): Baja carga (ej: anual).
- **Interfaz "Fuerza Bruta" (Readability)**: Las aclaraciones crÃƒÂ­ticas sobre la lÃƒÂ³gica de selecciÃƒÂ³n se muestran en **Rojo, Negrita y TamaÃƒÂ±o XL** para evitar errores operativos.

## 4. LÃƒâ€œGICA DE NEGOCIO Y REGLAS

### 4.1. Reglas Documentales
Definen *quÃƒÂ©* documento se requiere. Se han desacoplado de la lÃƒÂ³gica de criticidad.
*   **Servicio:** `DocumentoRequeridoService` determina la lista exacta de documentos exigibles para una entidad en un contexto dado.

### 4.2. Sistema de Criticidad DinÃƒÂ¡mica
Define el impacto del incumplimiento en el acceso a faena.
*   **Servicio:** `CriticidadDocumentoService`.
*   **Niveles de ConfiguraciÃƒÂ³n (Prioridad Descendente):**
    1.  **ExcepciÃƒÂ³n EspecÃƒÂ­fica (Activo):** ConfiguraciÃƒÂ³n para un trabajador/vehÃƒÂ­culo concreto.
    2.  **ExcepciÃƒÂ³n Contratista:** ConfiguraciÃƒÂ³n para toda una empresa.
    3.  **ConfiguraciÃƒÂ³n General:** Default del sistema (`documento_configuraciones_criticidad`).
*   **CÃƒÂ¡lculo de Cumplimiento:** Porcentaje ponderado basado en la criticidad de los documentos faltantes/vencidos.
*   **Interruptor de Control de Acceso (Override Manual):**
    *   Permite a `Mandante_Admin` forzar el acceso (Habilitar/Bloquear) de un recurso independientemente de su cumplimiento documental.
    *   Se registra en `documento_excepciones_criticidad` con un ID especial (`99999`).
    *   Requiere justificaciÃƒÂ³n y fecha de expiraciÃƒÂ³n opcional.

## 5. MÃƒâ€œDULOS OPERATIVOS

### 5.1. ASEM: GestiÃƒÂ³n General (`GestionGeneralDocumentos`)
El "Centro de Control" para ASEM_Admin.
*   **Filtros Avanzados:** Filtrado dual por `estado_validacion` (enfoque y exclusiÃƒÂ³n).
*   **Acciones Masivas:**
    *   **RevalidaciÃƒÂ³n Masiva:** SelecciÃƒÂ³n mÃƒÂºltiple -> Motivo ÃƒÂºnico -> GeneraciÃƒÂ³n de clones `Asignar-Revalidar` -> Archivado de originales.
    *   **ModificaciÃƒÂ³n de Vencimiento:** Ajuste de fechas en lote con justificaciÃƒÂ³n.
*   **Columna "Acciones Flash":** Contextual (`Validar`, `Auditar`, `Ver`) segÃƒÂºn el estado del documento.

### 5.2. ASEM: GestiÃƒÂ³n de Contratistas (`GestionContratistas`)
*   **Vista JerÃƒÂ¡rquica:** VisualizaciÃƒÂ³n anidada de Contratistas Principales y sus Sub-contratistas.
*   **Protocolo de VinculaciÃƒÂ³n:** Solo permite asignar Mandantes con los que existe una `Solicitud de VinculaciÃƒÂ³n` aprobada.
*   **Granularidad de Servicios:** Permite asignar los servicios de AcreditaciÃƒÂ³n y VerificaciÃƒÂ³n de forma independiente por cada Unidad Organizacional asignada.
*   **Sistema de Filtrado con ExpansiÃƒÂ³n JerÃƒÂ¡rquica:**
    *   Al aplicar filtros (bÃƒÂºsqueda por nombre, NÃ‚Â° contrato, etc.), el sistema expande automÃƒÂ¡ticamente los resultados para incluir descendientes (sub-contratistas) de los contratistas que coinciden.
    *   **Algoritmo de Ordenamiento (`ordenarJerarquicamente`):** Utiliza un sistema de puntuaciÃƒÂ³n para anidar correctamente los sub-contratistas bajo sus padres:
        *   **PuntuaciÃƒÂ³n por coincidencia de contrato:** +50 puntos si el NÃ‚Â° de contrato del hijo coincide con el padre.
        *   **PenalizaciÃƒÂ³n por discrepancia de contrato:** -100 puntos si los contratos no coinciden (evita agrupaciones incorrectas).
        *   **Coincidencia de UO/Lugar:** Bonus adicional por coincidencia de ubicaciÃƒÂ³n organizacional.
    *   **Nota TÃƒÂ©cnica:** Se utiliza `concat()` en lugar de `merge()` para combinar colecciones, evitando sobrescritura por clave numÃƒÂ©rica.

### 5.3. Mandante: Panel de SupervisiÃƒÂ³n (`PanelSupervision`)
Herramienta estratÃƒÂ©gica para `Mandante_Admin`.
*   **Vista de HalcÃƒÂ³n (Hawk View):** Resumen global de cumplimiento.
    *   Usa cachÃƒÂ© para rendimiento (actualizaciÃƒÂ³n diaria o "RecÃƒÂ¡lculo en Vivo" bajo demanda).
    *   MÃƒÂ©tricas agregadas por Contratista (Empresa, Trabajadores, VehÃƒÂ­culos).
*   **Vista de Lupa (Magnifying Glass):** Detalle en tiempo real (`SupervisionDetalleContratista`).
*   **Reportabilidad:** ExportaciÃƒÂ³n a Excel, PDF y HTML interactivo.

## 6. ONBOARDING Y VINCULACIÃƒâ€œN

### 6.1. Protocolo de Solicitudes (`GestionSolicitudesVinculacion`)
Todo vÃƒÂ­nculo entre Contratista y Mandante nace de una solicitud.
*   **Flujo:** Registro (PÃƒÂºblico) -> Solicitud -> AprobaciÃƒÂ³n (ASEM/Mandante) -> VinculaciÃƒÂ³n Efectiva.
*   **Tipos:**
    *   **Contratista:** Directo a Mandante.
    *   **Sub-Contratista:** Requiere aprobaciÃƒÂ³n de su Contratista Principal antes de llegar a ASEM.
*   **Mandante Fantasma (ID 99999):** Placeholder temporal para sub-contratistas en proceso de aprobaciÃƒÂ³n.

### 6.2. Proceso de Onboarding (`OnboardingContratista`)
Checklist de 7 pasos para habilitaciÃƒÂ³n inicial.
*   **Trazabilidad:** Cada paso registra fecha, usuario responsable y comentarios.
*   **Estado:** `En Proceso`, `Completado`, `Archivado`.

## 7. SISTEMA INTEGRADO DE NOTIFICACIONES

### 7.1. Arquitectura Dual
*   **Manual (Reactiva):** ASEM_Admin filtra documentos y dispara notificaciones personalizadas desde `GestionGeneralDocumentos`.
*   **AutomÃƒÂ¡tica (Proactiva):** Comando `notificaciones:vencimiento` ejecutado por Cron.
    *   Consulta umbrales en `sistema_configuraciones` (ej. 15 y 5 dÃƒÂ­as antes).

### 7.2. Componentes TÃƒÂ©cnicos
*   **Job:** `NotificarDocumentosContratista` (Cola asÃƒÂ­ncrona). Agrupa documentos por contratista para enviar un ÃƒÂºnico correo consolidado.
*   **Mailable:** `NotificacionDocumentos`.
*   **AuditorÃƒÂ­a:** Tabla `notificaciones_enviadas` registra cada despacho.

## 8. PROTOCOLOS DE SEGURIDAD Y MANTENIMIENTO

### 8.1. AutenticaciÃƒÂ³n
*   **Login/Logout:** ImplementaciÃƒÂ³n canÃƒÂ³nica con redirecciÃƒÂ³n HTTP completa (sin `navigate: true` de Livewire) para limpiar estados de memoria y sesiÃƒÂ³n.

### 8.2. Variables de Entorno CrÃƒÂ­ticas
*   `APP_URL`: Esencial para generaciÃƒÂ³n de links (especialmente con Ngrok).
*   `FILESYSTEM_DISK`: `public` para almacenamiento accesible.
*   `QUEUE_CONNECTION`: `database` (requiere `php artisan queue:work`).

---

## 9. DETALLE MODULAR (Historias de Usuario y Fichas TÃƒÂ©cnicas)

Este apartado detalla los mÃƒÂ³dulos del sistema bajo el formato de "Historia de Usuario" (Funcional) y "Ficha TÃƒÂ©cnica" (TÃƒÂ©cnico), siguiendo el estÃƒÂ¡ndar de documentaciÃƒÂ³n del proyecto.

---

### MÃƒÂ³dulo 1: Registro y Onboarding de Contratistas

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador de una Empresa Contratista**, quiero registrar mi empresa en el sistema y completar el proceso de habilitaciÃƒÂ³n inicial (onboarding), para poder empezar a cargar la documentaciÃƒÂ³n de mis trabajadores y vehÃƒÂ­culos."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Componente principal:** `OnboardingContratista` (Formulario de 7 pasos).
*   **Modelo:** `Contratista`, `SolicitudVinculacion`.
*   **Tablas:** `contratistas`, `solicitudes_vinculacion`.
*   **LÃƒÂ³gica**: 
    - Registro pÃƒÂºblico genera una `SolicitudVinculacion`.
    - AprobaciÃƒÂ³n por ASEM o Mandante (segÃƒÂºn tipo de contrato).
    - Trazabilidad: Cada paso registra fecha, usuario y comentarios.

---

### MÃƒÂ³dulo 2: Carga y Reemplazo de Documentos

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Contratista**, quiero cargar los documentos requeridos para mi empresa, trabajadores y vehÃƒÂ­culos, y que el sistema maneje automÃƒÂ¡ticamente el reemplazo de versiones antiguas, para asegurar que siempre haya un documento vigente o en revisiÃƒÂ³n."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Modelo:** `App\Models\DocumentoCargado`.
*   **LÃƒÂ³gica de Versiones**: 
    - Si reemplaza a un documento "Aprobado", el anterior permanece vigente hasta que el nuevo sea aprobado.
    - Si reemplaza a un "Rechazado/Vencido", el anterior pasa a "Archivado" inmediatamente.
*   **Snapshots**: Al cargar, se guardan copias de las reglas vigentes en campos `*_snapshot`.

---

### MÃƒÂ³dulo 3: Centro de Control ASEM (GestiÃƒÂ³n General)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador ASEM**, quiero tener una vista global de todos los documentos cargados, aplicar filtros avanzados y realizar acciones masivas, para gestionar eficientemente la carga de trabajo de validaciÃƒÂ³n."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Componente**: `GestionGeneralDocumentos`.
*   **Acciones Masivas**: 
    - **RevalidaciÃƒÂ³n Masiva**: EnvÃƒÂ­a documentos en lote a estado `Asignar-Revalidar`.
    - **ModificaciÃƒÂ³n de Vencimiento**: Ajuste de fechas en lote con justificaciÃƒÂ³n obligatoria.
*   **Filtros**: Duales por `estado_validacion` (enfoque vs exclusiÃƒÂ³n).

---

### MÃƒÂ³dulo 4: ValidaciÃƒÂ³n TÃƒÂ©cnica

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Validador ASEM**, quiero revisar los documentos asignados a mi cola, aprobarlos o rechazarlos, para garantizar que cumplen con la normativa y criterios tÃƒÂ©cnicos definidos."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Estados**: `Pendiente` -> `Asignado` -> `Aprobado`/`Rechazado`.
*   **Interfaz**: Botones contextuales ("Acciones Flash") segÃƒÂºn el estado del documento.
*   **AuditorÃƒÂ­a**: Se registra quiÃƒÂ©n validÃƒÂ³ y el resultado del snapshot tÃƒÂ©cnico.

---

### MÃƒÂ³dulo 5: SupervisiÃƒÂ³n EstratÃƒÂ©gica (Mandante)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador Mandante**, quiero ver el nivel de cumplimiento documental de todos mis contratistas (Vista de HalcÃƒÂ³n) y entrar al detalle por cada uno (Vista de Lupa), para tomar decisiones sobre el acceso a faena."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Componentes**: `PanelSupervision` (Hawk View) y `SupervisionDetalleContratista` (Magnifying Glass).
*   **OptimizaciÃƒÂ³n**: Uso de cachÃƒÂ© para mÃƒÂ©tricas globales (mÃƒÂ©trica agregada de cumplimiento).
*   **Salida**: ExportaciÃƒÂ³n dinÃƒÂ¡mica a Excel, PDF y HTML interactivo.

---

### MÃƒÂ³dulo 6: Reglas y Criticidad DinÃƒÂ¡mica

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador**, quiero configurar quÃƒÂ© documentos son necesarios y su nivel de criticidad, definiendo filtros precisos por tipo y sub-tipo de recurso, para adaptar el control de acceso a la realidad de cada contrato y mandante."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Servicios**: `DocumentoRequeridoService` y `CriticidadDocumentoService`.
*   **LÃƒÂ³gica "Por Periodo" (Correlatividad Estricta)**:
    - **Mes Vencido**: Los periodos se exigen basÃƒÂ¡ndose en el mes siguiente (el plazo de gracia opera en el mes $P+1$).
    - **Bloqueo Secuencial**: Un nuevo periodo de documento *solo* se exige y habilita para carga si el periodo inmediatamente anterior se encuentra con estado **"Aprobado"**. Si estÃƒÂ¡ Pendiente o Rechazado, el sistema congela el avance.
*   **Filtros Multi-EspecÃƒÂ­ficos**:
    - **VehÃƒÂ­culos**: Filtrado por `Tipo de VehÃƒÂ­culo` y `Sub-Tipo por Principal`.
    - **LÃƒÂ³gica AND**: Si se selecciona Tipo y Sub-tipo, la regla aplica solo si el vehÃƒÂ­culo cumple ambas condiciones.
*   **JerarquÃƒÂ­a de Reglas**: ExcepciÃƒÂ³n EspecÃƒÂ­fica (Activo) > ExcepciÃƒÂ³n Contratista > ConfiguraciÃƒÂ³n General.
*   **Override**: Interruptor de control de acceso manual (ID 99999) con justificaciÃƒÂ³n.
*   **UI/UX Premium**:
    - **Orden LÃƒÂ³gico**: El filtro de Sub-tipos aparece inmediatamente despuÃƒÂ©s del de Tipos de VehÃƒÂ­culo.
    - **Aclaraciones de Ayuda**: "SIN SELECCIÃƒâ€œN = APLICA A TODOS LOS [TIPO]". Formato visual resaltado (Rojo XL Extrabold).
    - **Documento Relacionado**: Herramienta de apoyo que permite vincular un documento de la **misma entidad** para facilitar el anÃƒÂ¡lisis.

---

### MÃƒÂ³dulo 7: AuditorÃƒÂ­a de Vencimientos

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador ASEM**, quiero poder modificar la fecha de vencimiento de un documento ya aprobado si detecto un error o necesito otorgar una prÃƒÂ³rroga, dejando evidencia fÃƒÂ­sica y digital del cambio."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Base de Datos**: `es_vencimiento_modificado` (bool), `motivo_modificacion_vencimiento` (text).
*   **Evidencia**: Almacenamiento opcional de archivo justificativo en `ruta_justificativo_modificacion`.
*   **VisualizaciÃƒÂ³n**: El sufijo `-Modificado` se aÃƒÂ±ade al estado de vigencia del documento.

---

### MÃƒÂ³dulo 8: Sistema de Notificaciones

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Usuario**, quiero recibir recordatorios automÃƒÂ¡ticos sobre vencimientos prÃƒÂ³ximos y alertas manuales sobre rechazos, para subsanar problemas documentales antes de que afecten el acceso a faena."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Proceso AutomÃƒÂ¡tico**: Comando Cron `notificaciones:vencimiento` (umbrales 15 y 5 dÃƒÂ­as).
*   **Manejo de Colas**: Job `NotificarDocumentosContratista` para despacho asÃƒÂ­ncrono consolidado.
*   **AuditorÃƒÂ­a**: Cada envÃƒÂ­o se registra en la tabla `notificaciones_enviadas`.

---

### MÃƒÂ³dulo 9: Sistema de VerificaciÃƒÂ³n Laboral (Contratista)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Contratista**, quiero cargar los documentos de verificaciÃƒÂ³n laboral mensual (liquidaciones, certificados de pago, finiquitos) para cada periodo exigible, y que el sistema me indique claramente si estoy dentro de plazo o fuera de plazo, para cumplir con los requisitos del mandante y obtener mi certificado de cumplimiento."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)

##### Componentes Principales
*   **Backend:** `App\Livewire\Contratista\Verificacion` - GestiÃƒÂ³n de carga de documentos por periodo.
*   **Backend ASEM:** `App\Livewire\Asem\Verificacion` - ConfiguraciÃƒÂ³n del calendario y requisitos.
*   **Vista:** `resources/views/livewire/contratista/verificacion.blade.php`

##### Modelos Involucrados
*   `CalendarioVerificacion`: Define las fechas de apertura, cierre y emisiÃƒÂ³n por periodo.
*   `CarpetaVerificacion`: Representa el envÃƒÂ­o de documentos de un contratista para un periodo especÃƒÂ­fico.
*   `DocumentoVerificacion`: Documentos individuales cargados en una carpeta.
*   `RequisitoVerificacion`: Documentos exigidos por el mandante.
*   `ClasificacionVerificacion`: CategorÃƒÂ­as de documentos (Pago Trabajador, Previsionales, Otros).

##### Sistema de Plazos y Almacenamiento (AlineaciÃƒÂ³n NÃƒÂ³mina)

A partir de la **V8.19**, el sistema utiliza el **mes nominal de las remuneraciones** como clave de almacenamiento en la base de datos. Se eliminÃƒÂ³ la "anarquÃƒÂ­a" de desfases que existÃƒÂ­a previamente.

| Concepto | LÃƒÂ³gica de Almacenamiento | VisualizaciÃƒÂ³n UI |
|----------|--------------------------|------------------|
| **Periodo NÃƒÂ³mina** | `mes` / `anio` reales (ej. Noviembre = 11) | Directa desde BD |
| **Plazos (Deadline)** | Consulta al calendario sumando +1 mes | Informativa |
| **Archivos** | Nombre incluye `mes_anio` nominal | Directa |

**EstandarizaciÃƒÂ³n de NavegaciÃƒÂ³n (Modo Zen):**
A partir de la **V8.35**, se implementÃƒÂ³ una integraciÃƒÂ³n fluida entre el mÃƒÂ³dulo Legacy (Grilla 4x3) y el mÃƒÂ³dulo Moderno (Vista de Carga):
- **Deep-Linking**: La grilla 4x3 del Legacy actÃƒÂºa como lanzador, inyectando parÃƒÂ¡metros de contexto (`v`, `a`, `m`, `from=legacy`) para pre-seleccionar automÃƒÂ¡ticamente la vinculaciÃƒÂ³n y el periodo en la vista moderna.
- **Interfaz Zen (Foco 100%)**: Cuando el sistema detecta que el usuario proviene del Legacy, oculta dinÃƒÂ¡micamente el panel sidebar de vinculaciones y expande el ÃƒÂ¡rea de carga al 100% de la pantalla (`col-span-12`).
- **NavegaciÃƒÂ³n Circular**: InclusiÃƒÂ³n de un botÃƒÂ³n **"VOLVER"** minimalista con estilo "Verde Lechuga" Ã°Å¸Â¥Â¬ para retornar a la grilla de control histÃƒÂ³rica sin perder el contexto.
- **OptimizaciÃƒÂ³n de Espacio**: EliminaciÃƒÂ³n del `<header>` global en `app.blade.php` para maximizar el ÃƒÂ¡rea de trabajo ÃƒÂºtil.

**Tabla de Plazos Detallada:**

| Estado | CondiciÃƒÂ³n | Color | Fecha EmisiÃƒÂ³n Certificado |
|--------|-----------|-------|---------------------------|
| **DENTRO DE PLAZO** | Hoy Ã¢â€°Â¤ `fecha_cierre` | Ã°Å¸Å¸Â¢ Verde | `fecha_emision` |
| **FUERA DE PLAZO** | `fecha_cierre` < Hoy Ã¢â€°Â¤ `fecha_cierre_fuera_plazo` | Ã°Å¸Å¸Â  Ãƒï¿½mbar | `fecha_emision_fuera_plazo` |
| **FUERA DE PLAZO** (Vencido) | Hoy > `fecha_cierre_fuera_plazo` | Ã°Å¸â€Â´ Rojo | Siguiente periodo |

##### Flujo de EnvÃƒÂ­o
1. El contratista carga documentos en la carpeta del periodo.
2. Al hacer clic en "ENVIAR PERIODO", el sistema:
   - Determina el `tipo_envio` basado en la fecha actual vs fechas del calendario.
   - Asigna la `fecha_emision_asignada` correspondiente.
   - Actualiza el estado de la carpeta a "ENVIADO".
3. Una vez enviado, el periodo queda bloqueado y muestra el tipo de envÃƒÂ­o.
4. **FunciÃƒÂ³n "Abrir Periodo"**: Supervisores y Admins (`ASEM_Admin`, `OVAL_Admin`, `Verifica_Supervisor`) pueden resetear el envÃƒÂ­o para permitir rectificaciones, desasignando automÃƒÂ¡ticamente al equipo de revisiÃƒÂ³n y devolviendo el periodo a estado `PENDIENTE`.
5. **Blindaje Post-EmisiÃƒÂ³n (Lockdown)**: Una vez que el certificado es marcado como `EMITIDO`, el periodo se bloquea de forma irreversible para el contratista, impidiendo cualquier carga adicional de documentos, a menos que existan incidencias pendientes que requieran un proceso complementario.

##### VisualizaciÃƒÂ³n de Incidencias y Certificados
- **Resumen de Incidencias**: La tarjeta del periodo muestra un desglose rÃƒÂ¡pido de:
  - **OBS**: Cantidad de observaciones de forma o fondo.
  - **RET**: Contingencias con retenciÃƒÂ³n financiera.
  - **NO RET**: Contingencias informativas sin retenciÃƒÂ³n.
- **Acceso al Certificado**: Disponible ÃƒÂºnicamente en estado `EMITIDO` mediante una ruta neutra y segura (`/certificado/visor/{id}`) que valida la propiedad del documento.
- **Acciones Duales de Documentos (Global)**: Todos los perfiles con acceso a la revisiÃƒÂ³n de documentos (Emisor, Analista, Auditor, Supervisor y Operador IA) disponen de botones diferenciados para **VER** (Navegador) y **DESCARGAR** (Local) mediante la ruta segura `archivo.publico`.

##### Flujo de DesvinculaciÃƒÂ³n y ReversiÃƒÂ³n (A Prueba de Balas)

Este subsistema garantiza que un trabajador pueda ser **desvinculado** (finiquitado) y **revertido** (reactivado) desde **dos vistas independientes** (Maestro y NÃƒÂ³mina) de forma consistente, sin pÃƒÂ©rdida de datos y sin duplicidades visuales.

---

###### Principio Rector: "Escritura en Piedra"

> **REGLA DE ORO**: La purga de datos operativos (UO, Lugar de Trabajo, NÃ‚Â° Contrato) de una vinculaciÃƒÂ³n **SOLO** ocurre en el mÃƒÂ©todo `consolidarReserva()`, que se ejecuta exclusivamente al momento de **enviar el perÃƒÂ­odo** (`enviarPeriodo`). En ningÃƒÂºn otro momento del ciclo de vida se borran estos datos, preservando la reversibilidad total mientras el perÃƒÂ­odo no haya sido emitido.

---

###### Estados de VinculaciÃƒÂ³n Involucrados

| Campo `motivo_desactivacion` | Significado | `is_active` |
|---|---|---|
| `null` | Trabajador activo | `true` |
| `FINIQUITADO` | Desvinculado definitivo (ej. liquidaciÃƒÂ³n firmada) | `false` |
| `CESACION_PRINCIPAL` | Baja solicitada por la empresa mandante | `false` |
| `RECONOCIMIENTO_ANTIGUEDAD` | Finiquito por reconocimiento de antigÃƒÂ¼edad | `false` |

---

###### Estados en NÃƒÂ³mina (`CarpetaVerificacionTrabajador.estado_revision`)

| Estado | Significado |
|---|---|
| `PENDIENTE` | Trabajador activo en la nÃƒÂ³mina del perÃƒÂ­odo |
| `FINIQUITADO` | Desvinculado, con fecha efectiva registrada |
| `CESACION_PRINCIPAL` | Baja por mandante informada en la nÃƒÂ³mina |
| `RECONOCIMIENTO_ANTIGUEDAD` | Finiquito especial en la nÃƒÂ³mina |

---

###### Flujo de DesvinculaciÃƒÂ³n desde el MAESTRO

**Archivo:** `app/Livewire/Contratista/GestionTrabajadoresContratista.php`  
**MÃƒÂ©todo principal:** `procesarDesactivacion($vinculacionId, $motivo, $fecha)`

1. Carga la vinculaciÃƒÂ³n seleccionada y **todas las vinculaciones del mismo trabajador** (`TrabajadorVinculacion::where('trabajador_id', ...)->get()`).
2. Para **cada** vinculaciÃƒÂ³n del trabajador:
   - Verifica si tiene una `CarpetaVerificacionTrabajador` en una nÃƒÂ³mina abierta (no `EMITIDO`).
   - **Con nÃƒÂ³mina abierta** Ã¢â€ â€™ Marca `is_active=false`, `motivo_desactivacion=$motivo`, `fecha_desactivacion=$fecha`, pero **preserva** `dependencia_id`, `unidad_organizacional_mandante_id` y `numero_contrato`.
   - **Sin nÃƒÂ³mina abierta** Ã¢â€ â€™ Marca desactivaciÃƒÂ³n Y nulifica UO/Lugar/Contrato.
3. Llama a `sincronizarEstadoEnNominasAbiertas($vinculacion->trabajador_id, $motivo, $fecha)` **una sola vez** al final del foreach (no por iteraciÃƒÂ³n).

**MÃƒÂ©todo:** `sincronizarEstadoEnNominasAbiertas($trabajadorId, $motivo, $fecha)`

- Busca **todas** las `CarpetaVerificacionTrabajador` de cualquier vinculaciÃƒÂ³n del trabajador en carpetas no emitidas.
- Actualiza su `estado_revision` al motivo y registra `fecha_finiquito`.
- Despacha el Job `ActualizarEstadoRecursoIndividual` para recalcular el estado global del trabajador.

---

###### Flujo de DesvinculaciÃƒÂ³n desde la NÃƒâ€œMINA

**Archivo:** `app/Livewire/Contratista/VerificacionLegacyCarga.php`  
**MÃƒÂ©todo:** `cambiarEstadoTrabajadorPeriodo($cvtId, $nuevoEstado)` Ã¢â€ â€™ delega a `propagarDesvinculacion()`

**MÃƒÂ©todo:** `propagarDesvinculacion($cvt, $nuevoEstado)`

1. Actualiza el `estado_revision` del CVT clicado.
2. Desactiva **todas** las vinculaciones del trabajador: `is_active=false`, `motivo=$nuevoEstado`, `fecha_desactivacion=hoy`.
   - **ProtecciÃƒÂ³n anti-purga**: Si la vinculaciÃƒÂ³n tiene nÃƒÂ³mina abierta Ã¢â€ â€™ preserva UO/Lugar/Contrato (escudo idÃƒÂ©ntico al del Maestro).
3. Propaga el estado a todos los CVT del mismo trabajador en el **mismo perÃƒÂ­odo** (mismo `anio`/`mes`), en todas las carpetas no emitidas.

---

###### Flujo de ReversiÃƒÂ³n desde el MAESTRO Ã¢Â­ï¿½

**Archivo:** `app/Livewire/Contratista/GestionTrabajadoresContratista.php`  
**MÃƒÂ©todo:** `revertirFiniquitoMaestro($vinculacionId)`

> **CorrecciÃƒÂ³n V8.39**: Anteriormente buscaba el CVT por `trabajador_vinculacion_id = $vinculacionId` (el ID del botÃƒÂ³n clicado, siempre el MIN). El CVT FINIQUITADO puede estar en **cualquier** vinculaciÃƒÂ³n del trabajador, por lo que el query fallaba silenciosamente.

**Flujo corregido:**

```
1. Cargar la vinculaciÃƒÂ³n clicada + trabajador.
2. Obtener TODOS los IDs de vinculaciones del trabajador.
   Ã¢â€ â€™ $vinculacionIds = TrabajadorVinculacion::where('trabajador_id', ...)->pluck('id')
3. Buscar el CVT FINIQUITADO con whereIn('trabajador_vinculacion_id', $vinculacionIds).
   Ã¢â€ â€™ Filtra solo carpetas no EMITIDAS.
4. Si no encuentra CVT Ã¢â€ â€™ notifica error "perÃƒÂ­odo ya enviado".
5. Reactivar TODAS las vinculaciones del trabajador:
   Ã¢â€ â€™ WHERE trabajador_id = X AND is_active = false AND motivo_desactivacion IN [...]
   Ã¢â€ â€™ UPDATE: is_active=true, fecha_desactivacion=null, fecha_finiquito=null, motivo=null
6. Revertir TODOS los CVT del mismo perÃƒÂ­odo (mismo anio/mes) a PENDIENTE:
   Ã¢â€ â€™ whereIn(trabajador_vinculacion_id, $vinculacionIds)
   Ã¢â€ â€™ whereIn(carpeta_verificacion_id, carpetas-no-emitidas-del-mismo-mes)
7. LIMPIEZA ANTI-FANTASMA:
   Ã¢â€ â€™ Si el trabajador tiene vinculaciones activas con dependencia_id real,
     eliminar registros con dependencia_id=null AND UO=null (anclas de consolidarReserva).
8. Despachar ActualizarEstadoRecursoIndividual + dispatch('recursosActualizados').
```

---

###### Flujo de ReversiÃƒÂ³n desde la NÃƒâ€œMINA

**Archivo:** `app/Livewire/Contratista/VerificacionLegacyCarga.php`  
**MÃƒÂ©todo:** `cambiarEstadoTrabajadorPeriodo($cvtId, 'PENDIENTE')` Ã¢â€ â€™ delega a `revertirDesvinculacion()`

**MÃƒÂ©todo:** `revertirDesvinculacion($cvt)`

1. Actualiza el CVT clicado a `PENDIENTE`, limpia `fecha_finiquito`.
2. Reactiva **todas** las vinculaciones del trabajador con motivo de finiquito.
3. Propaga `PENDIENTE` a todos los CVT del mismo trabajador en el mismo perÃƒÂ­odo, en carpetas no emitidas.

---

###### Ciclo de Vida Completo

```
ACTIVO (Maestro) Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€ï¿½
  Ã¢â€â€š                                                                       Ã¢â€â€š
  Ã¢â€Å“Ã¢â€â‚¬[Desvincular Maestro]Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€ â€™ procesarDesactivacion()                     Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ is_active=false (todas las vinculaciones)                       Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ CVTs del perÃƒÂ­odo Ã¢â€ â€™ FINIQUITADO                                  Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ UO/Lugar/Contrato PRESERVADOS (hay nÃƒÂ³mina abierta)              Ã¢â€â€š
  Ã¢â€â€š                                                                       Ã¢â€â€š
  Ã¢â€Å“Ã¢â€â‚¬[Desvincular NÃƒÂ³mina]Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€ â€™ propagarDesvinculacion()                    Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ is_active=false (todas las vinculaciones)                        Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ CVTs del perÃƒÂ­odo Ã¢â€ â€™ FINIQUITADO                                   Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ UO/Lugar/Contrato PRESERVADOS (escudo activo)                    Ã¢â€â€š
  Ã¢â€â€š                                                                       Ã¢â€â€š
RESERVA (Maestro) Ã¢â€ ï¿½ ambas vÃƒÂ­as llegan acÃƒÂ¡                                Ã¢â€â€š
  Ã¢â€â€š                                                                       Ã¢â€â€š
  Ã¢â€Å“Ã¢â€â‚¬[Revertir Maestro]Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€ â€™ revertirFiniquitoMaestro()                  Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ Busca CVT via whereIn(allVinculacionIds)   Ã¢â€ ï¿½ FIX V8.39          Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ is_active=true (todas las vinculaciones)                         Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ CVTs del perÃƒÂ­odo Ã¢â€ â€™ PENDIENTE                                     Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ Purga anclas fantasma si hay vinculaciones reales Ã¢â€ ï¿½ FIX V8.39   Ã¢â€â€š
  Ã¢â€â€š                                                                       Ã¢â€â€š
  Ã¢â€Å“Ã¢â€â‚¬[Revertir NÃƒÂ³mina]Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€ â€™ revertirDesvinculacion()                    Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ is_active=true (todas las vinculaciones)                         Ã¢â€â€š
  Ã¢â€â€š    Ã¢â€ â€™ CVTs del perÃƒÂ­odo Ã¢â€ â€™ PENDIENTE                                     Ã¢â€â€š
  Ã¢â€â€š                                                                       Ã¢â€â€š
  Ã¢â€â€Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€ â€™ ACTIVO (Maestro) Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€Ëœ

[Enviar PerÃƒÂ­odo]Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€ â€™ enviarPeriodo()
    Ã¢â€â€Ã¢â€â‚¬Ã¢â€ â€™ consolidarReserva()  Ã¢â€ ï¿½ ÃƒÅ¡NICA PURGA PERMITIDA
         Ã¢â€ â€™ Nulifica UO/Lugar/Contrato de finiquitados confirmados
         Ã¢â€ â€™ Marca perÃƒÂ­odo como EMITIDO Ã¢â€ â€™ IRREVERSIBLE
```

---

###### Archivos y MÃƒÂ©todos Clave

| Archivo | MÃƒÂ©todo | Responsabilidad |
|---|---|---|
| `GestionTrabajadoresContratista.php` | `procesarDesactivacion()` | Desvincular desde Maestro |
| `GestionTrabajadoresContratista.php` | `sincronizarEstadoEnNominasAbiertas()` | Propagar estado a nÃƒÂ³minas abiertas |
| `GestionTrabajadoresContratista.php` | `revertirFiniquitoMaestro()` | Revertir desde Maestro (usa `whereIn` sobre todos los IDs) |
| `VerificacionLegacyCarga.php` | `propagarDesvinculacion()` | Desvincular desde NÃƒÂ³mina (con escudo anti-purga) |
| `VerificacionLegacyCarga.php` | `revertirDesvinculacion()` | Revertir desde NÃƒÂ³mina |
| `VerificacionLegacyCarga.php` | `consolidarReserva()` | Purga definitiva al emitir perÃƒÂ­odo |

---

##### ImportaciÃƒÂ³n de DotaciÃƒÂ³n Anterior (MigraciÃƒÂ³n HistÃƒÂ³rica)

- **Objetivo**: Permitir la carga de datos histÃƒÂ³ricos sin depender de la apertura secuencial manual.
- **LÃƒÂ³gica de AutocreaciÃƒÂ³n**: Si se importa un periodo que no tiene carpeta, el sistema crea una `CarpetaVerificacion` con estado `ENVIADO` y `AUDITADO`.
- **GestiÃƒÂ³n de Fechas**:
  - **Fecha Ingreso**: Si el Excel es nulo, se asigna el **dÃƒÂ­a 1 del mes nominal**.
  - **Fecha Contrato**: Columna dedicada en la plantilla (con fallback a Fecha Ingreso).
- **SincronizaciÃƒÂ³n de Arrastre DinÃƒÂ¡mico**:
  - Al entrar a cualquier periodo no enviado, el sistema busca trabajadores del mes anterior verificado que falten en el actual.
  - **Etiquetado**: Se marcan como `ARRASTRE` para indicar herencia y falta de confirmaciÃƒÂ³n explÃƒÂ­citamente en el periodo actual.

##### Interfaz de Usuario (Contratista)

**Panel Izquierdo - Periodos Exigibles:**
- Lista de periodos con indicadores visuales:
  - Estado de plazo: DENTRO DE PLAZO / FUERA DE PLAZO
  - Estado del documento: PENDIENTE / EN PROGRESO / PERIODO ENVIADO

**Panel Derecho - Cabecera de Carga:**
- InformaciÃƒÂ³n de la vinculaciÃƒÂ³n (Lugar, UO, NÃ‚Â° Contrato, Tipo Contrato)
- Periodo seleccionado
- Fechas de emisiÃƒÂ³n: Normal y Fuera de Plazo

##### EstandarizaciÃƒÂ³n de Filtros y Columnas (ENVÃƒï¿½O)
Para unificar la visibilidad del cumplimiento de los plazos en todos los roles, se implementÃƒÂ³ una interfaz estandarizada de filtros y columnas:
- **TerminologÃƒÂ­a Unificada**: El concepto "Estado Plazo" ha sido renombrado a **"ENVÃƒï¿½O"** en todos los paneles para coincidir con el nombre de la columna en las tablas de resultados.
- **Filtro Transversal**: Todos los perfiles (Supervisor, Emisor, Auditor, Analista y Operador IA) incluyen ahora un selector de filtro por **ENVÃƒï¿½O** con las opciones:
  - **DnP** (Dentro de Plazo): Filtra carpetas enviadas en el periodo normal.
  - **FdP** (Fuera de Plazo): Filtra carpetas enviadas en el periodo extraordinario.
- **Columna de Estado**: Se integrÃƒÂ³ la columna **"ENVÃƒï¿½O"** en las tablas de Auditor, Analista y Operador IA, mostrando badges visuales consistentes:
    - `Ã¢Å“â€ DnP` (Verde): EnvÃƒÂ­o correcto en tiempo.
    - `FdP` (Rojo): EnvÃƒÂ­o fuera de plazo.
- **Consistencia de Datos**: El filtrado utiliza el campo `tipo_envio` de la tabla `carpetas_verificacion`, asegurando que todos los roles vean la misma informaciÃƒÂ³n de cumplimiento.

##### ConfiguraciÃƒÂ³n (ASEM)

En `/gestion/verificacion` Ã¢â€ â€™ PestaÃƒÂ±a "CONFIGURACION CALENDARIO":
- Campos para cada mes:
  - Carga Desde / Carga Hasta (fechas normales)
  - Cierre Fuera de Plazo
  - EmisiÃƒÂ³n Normal
  - EmisiÃƒÂ³n Fuera de Plazo

##### Migraciones Relacionadas
- `2026_01_19_201200_add_fuera_plazo_fields.php`: AÃƒÂ±ade campos de fuera de plazo a calendario y carpetas.
- `2026_01_19_200000_add_orden_to_clasificaciones_verificacion.php`: AÃƒÂ±ade campo de orden para clasificaciones.

---

---

### MÃƒÂ³dulo 10: Sistema de Sub-Contratistas (JerÃƒÂ¡rquico)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Contratista Principal**, quiero registrar y gestionar a mis empresas sub-contratistas, creando una jerarquÃƒÂ­a de responsabilidad, para que ellos puedan cargar su propia documentaciÃƒÂ³n bajo mi supervisiÃƒÂ³n y la del Mandante."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **JerarquÃƒÂ­a**: Soporte hasta 4 niveles de profundidad (Contratista -> Sub -> Sub-Sub -> Sub-Sub-Sub).
*   **Rol**: `Subcontratista` (Permisos limitados: carga documentos, gestiona sus trabajadores, no puede crear usuarios).
*   **Flujo de AprobaciÃƒÂ³n**:
    - **Solicitud**: Iniciada por el Contratista Padre (`SolicitudSubContratista`).
    - **AprobaciÃƒÂ³n**: Realizada por `Mandante_Admin`.
    - **VinculaciÃƒÂ³n**: Hereda vinculaciones del padre seleccionadas explÃƒÂ­citamente durante la aprobaciÃƒÂ³n.
*   **Visibilidad**:
    - `Mandante_Admin` ve toda la cadena.
    - `Contratista_Admin` ve a sus descendientes directos e indirectos (MÃƒÂ³dulo "Mis Subcontratistas").
*   **GestiÃƒÂ³n de Usuarios Diferenciada**:
    - **GestiÃƒÂ³n Usuarios (MÃƒÂ³dulo General):** Restringido a usuarios INTERNOS de la empresa.
    - **Mis Subcontratistas:** Incluye funcionalidad CRUD para crear/gestionar usuarios de los subcontratistas (Rol `Subcontratista` forzado).
*   **GestiÃƒÂ³n de Estados (Reglas de Negocio)**:
    - **DesactivaciÃƒÂ³n en Cascada:** Al desactivar a un Contratista, todos sus descendientes se desactivan recursivamente.
    - **ReactivaciÃƒÂ³n Manual:** Al reactivar a un padre, los descendientes permanecen inactivos y deben activarse manualmente.

---

### MÃƒÂ³dulo 11: GestiÃƒÂ³n de Carga Documental (IMC) y Reportabilidad Ejecutiva

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador ASEM o Mandante**, quiero visualizar y exportar la carga administrativa mensual (IMC) que genera cada documento y mandante, para identificar cuellos de botella y tomar decisiones sobre la extensiÃƒÂ³n de vigencias."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Modelo:** `ReglaDocumental` (Campo `imc_meses_estimados` y Accessor `imc`).
*   **Dashboard:** Tarjeta informativa de "Carga Mensual (IMC Total)" que suma el IMC de todas las reglas activas de la Principal filtrada.
*   **Reporte Ejecutivo (`ReporteImcExport`):** ExportaciÃƒÂ³n multioja (Maatwebsite/Excel) que incluye:
    - **Hoja 1 (Resumen)**: Consolidado por Principal y desglosado por Entidad (Persona, VehÃƒÂ­culo, etc.).
    - **Hoja 2+ (Detalles)**: Una pestaÃƒÂ±a por cada Principal con el detalle completo de sus reglas y pesos individuales.
    - **Hoja Final (Ranking)**: Top 25 global de documentos con mayor carga administrativa.

---

---

### MÃƒÂ³dulo 12: Ingesta Masiva de Datos (Excel Premium)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador ASEM**, quiero disponer de plantillas de carga masiva para Contratistas, Trabajadores y VehÃƒÂ­culos que incluyan menÃƒÂºs desplegables para evitar errores de tipeo y aseguren que los datos ingresados siempre existan en el sistema, permitiendo ademÃƒÂ¡s vincular recursos a sus respectivos Mandantes de forma automÃƒÂ¡tica."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **LibrerÃƒÂ­a**: `Maatwebsite/Excel`.
*   **Estrategia**: `WithMultipleSheets` (Template + Listados).
*   **ValidaciÃƒÂ³n**: Uso de `DataValidation::TYPE_LIST` con fÃƒÂ³rmulas `Listados!$Col$2:$Col$N`.
*   **Parsing**: FunciÃƒÂ³n `cleanCompositeName()` separa el prefijo del Mandante del nombre real del recurso.
*   **Modelos**: `Contratista`, `Trabajador`, `Vehiculo`, `SolicitudVinculacion`, `DocumentoCargado`.

---

### MÃƒÂ³dulo 13: Descarga Masiva de Documentos (ZIP DinÃƒÂ¡mico)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador (ASEM, Supervisor o Analista)**, quiero poder descargar en un solo archivo comprimido todos los documentos de un tipo especÃƒÂ­fico para un periodo determinado, incluyendo un resumen previo de cuÃƒÂ¡ntos archivos se extraerÃƒÂ¡n, para facilitar la auditorÃƒÂ­a externa o el respaldo local."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Componente**: `Shared\DescargaMasivaVerificacion`.
*   **LÃƒÂ³gica de SelecciÃƒÂ³n**:
    - Filtrado por Principal (Mandante), Requisito (Tipo de Documento) y Periodo (Mes/AÃƒÂ±o).
    - OpciÃƒÂ³n de filtrar por rango de fecha de recepciÃƒÂ³n o estado de entrega (Al dÃƒÂ­a/Fuera de plazo).
*   **Preview Reactivo**: CÃƒÂ¡lculo en vivo de la cantidad de contratistas afectados y nÃƒÂºmero total de documentos mediante el ID de la CUO.
*   **GeneraciÃƒÂ³n de ZIP**:
    - Uso de `ZipArchive`.
    - Estructura interna: `[RUT_RAZONSOCIAL]/[NOMBRE_ORIGINAL_ARCHIVO]`.
    - SanitizaciÃƒÂ³n automÃƒÂ¡tica de nombres de carpetas y archivos para compatibilidad con Windows/Unix.

---

### MÃƒÂ³dulo 14: GestiÃƒÂ³n de VerificaciÃƒÂ³n (Admin / Analista / Auditor)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador OVAL o Validador**, quiero asignar, revisar y auditar las carpetas de verificaciÃƒÂ³n cargadas por los contratistas, gestionando la nÃƒÂ³mina de trabajadores y sus estados especÃƒÂ­ficos en un entorno unificado y transparente, para garantizar la integridad de los certificados de cumplimiento mensual."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Componentes**: 
    - `Supervisor\AsignacionVerificacion` (Panel de Control y AsignaciÃƒÂ³n).
    - `Analista\MisAsignaciones` (Cola de revisiÃƒÂ³n de documentos).
    - `Auditor\MisAuditorias` (Cola de aprobaciÃƒÂ³n final).
*   **NÃƒÂ³mina DinÃƒÂ¡mica (Snapshot DotaciÃƒÂ³n)**:
    - El sistema inicializa la nÃƒÂ³mina del periodo a partir de un snapshot de los trabajadores vinculados activos en la fecha de apertura.
    - **Estados Maestros**: `1. ACTIVO`, `2. FINIQUITADO`, `3. MOVIDO`, `4. BAJA_MANDANTE`.
*   **Visibilidad Admin (God Mode)**:
    - Bypassing de filtros de asignaciÃƒÂ³n para roles administrativos en todas las vistas operativos.
*   **GestiÃƒÂ³n Integral de Cierre (V2 - DiseÃƒÂ±o 1:1)**:
    - **Analista**: Genera la propuesta de precierre con montos financieros y situaciÃƒÂ³n de trabajadores en un formato de bloques sÃƒÂ³lidos (Azul #003a5c / Amarillo #fcc01a).
    - **Auditor**: Posee un panel espejo con paridad visual total en el encabezado y estructura de filas. Su modal de cierre permite validar y corregir:
        - **DotaciÃƒÂ³n**: Nuevos, Bajas y Vigentes (VisualizaciÃƒÂ³n 50/50 con RecepciÃƒÂ³n).
        - **Financiero (Bloques Planos)**: Remuneraciones Pagadas y Cotizaciones Pagadas ocupando el 100% del ancho.
        - **Indemnizaciones (Bloque DinÃƒÂ¡mico)**: Aviso Previo, AÃƒÂ±os de Servicio y Feriado con labels estandarizados.
        - **Contingencias**: Registro de incidencias por trabajador con flag `es_retenible` para bloqueo de certificado complemento.
*   **Flujo de AprobaciÃƒÂ³n**: Los periodos requieren firma secuencial (Analista -> Auditor) para la emisiÃƒÂ³n del certificado final.
*   **Seguridad y Blindaje (Lockdown)**: 
    - Al alcanzar el estado `EMITIDO`, el sistema desactiva automÃƒÂ¡ticamente todos los controles de ediciÃƒÂ³n en las vistas de Analista, Auditor y Supervisor.
    - Se impide la reasignaciÃƒÂ³n de carpetas emitidas y la modificaciÃƒÂ³n de montos o trabajadores verificados para garantizar la integridad legal del certificado ante el INN.
    - La previsualizaciÃƒÂ³n y descarga del certificado se centraliza en un controlador seguro que gestiona permisos por rol y empresa.

---

### MÃƒÂ³dulo 15: AutomatizaciÃƒÂ³n de VerificaciÃƒÂ³n con IA (Gemini API)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Analista de VerificaciÃƒÂ³n**, quiero que el sistema utilice Inteligencia Artificial para extraer automÃƒÂ¡ticamente los datos de las liquidaciones de sueldo (RUT, Nombre, Monto LÃƒÂ­quido) desde archivos PDF unitarios o masivos, comparÃƒÂ¡ndolos con la base de datos para reducir el tiempo de revisiÃƒÂ³n manual y eliminar el error humano."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Motor de IA**: Google Gemini 2.5 Flash-Lite (Optimizado para alto volumen y bajo costo).
*   **Capacidad Multimodal**: Procesamiento de archivos PDF y fotos de liquidaciones.
*   **Estrategia de Documento ÃƒÅ¡nico**:
    - Capacidad de procesar un solo PDF con mÃƒÂºltiples liquidaciones (NÃƒÂ³mina completa).
    - Gemini actÃƒÂºa como "Separador Inteligente", indexando en quÃƒÂ© pÃƒÂ¡gina se encuentra cada trabajador.
*   **Prompting Estructurado**: ExtracciÃƒÂ³n de datos en formato JSON (RUT, Nombre_Completo, Monto_Liquido, Periodo, Pagina).
*   **LÃƒÂ³gica de ValidaciÃƒÂ³n (Match)**:
    - **ValidaciÃƒÂ³n de Identidad**: Cruce automÃƒÂ¡tico del RUT extraÃƒÂ­do contra el RUT del trabajador asignado.
    - **ValidaciÃƒÂ³n de Integridad**: ComparaciÃƒÂ³n del monto extraÃƒÂ­do vs lo declarado o calculado.
*   **Interfaz de Usuario (UI)**:
    - BotÃƒÂ³n **"Ã°Å¸â€ï¿½ AUTO-VERIFICAR CON IA"** en el modal del Analista.
    - Badges de estado: `[ PROCESANDO ]`, `[ OK - $850.000 ]`, `[ Ã¢Å¡Â Ã¯Â¸ï¿½ RUT NO COINCIDE ]`.
    - Registro histÃƒÂ³rico de la extracciÃƒÂ³n en la tabla de auditorÃƒÂ­a del documento.

---

### MÃƒÂ³dulo 16: Panel de Control de ExtracciÃƒÂ³n (Operador IA)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Operador de IA**, quiero disponer de un panel simplificado donde pueda filtrar rÃƒÂ¡pidamente los periodos por ID_REGISTRO o Mandante, visualizar los documentos cargados por el contratista y marcar los periodos cuya extracciÃƒÂ³n de datos ya ha sido completada, para notificar automÃƒÂ¡ticamente al Supervisor que el periodo estÃƒÂ¡ listo para revisiÃƒÂ³n."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Componente**: `OperadorIA\ControlExtraccion`.
*   **Ruta**: `/ia/extraccion`.
*   **LÃƒÂ³gica de Negocio**:
    *   **Flag de Estado**: Manejo directo del campo `ia_datos_extraidos` en `carpetas_verificacion`.
    *   **Buscadores Globales**: Filtrado jerÃƒÂ¡rquico por Mandante/Contratista e integraciÃƒÂ³n del filtro por `ID_REGISTRO`.
    *   **Visor de Documentos (IA Mode)**:
        *   Modal con clasificaciÃƒÂ³n documental resumida.
        *   Acciones duales: **VER** (Preview) y **DESCARGAR** (Download) para agilizar la entrada de datos en herramientas externas.
*   **IntegraciÃƒÂ³n Transversal**:
    *   **Supervisor/Emisor**: La columna **IA** (ubicada a la derecha de Auditor) muestra el badge reactivo `IA OK` si el flag es verdadero.

---

### MÃƒÂ³dulo 17: GestiÃƒÂ³n de Documentos Obligatorios (Bloqueo de EnvÃƒÂ­o)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador ASEM**, quiero definir quÃƒÂ© documentos son obligatorios para el envÃƒÂ­o mensual, para que el contratista no pueda cerrar el periodo sin haber cargado la evidencia crÃƒÂ­tica mÃƒÂ­nima requerida por el mandante."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Modelo:** `RequisitoVerificacion` (Campo `es_obligatorio`).
*   **LÃƒÂ³gica Defensiva:** El mÃƒÂ©todo `obtenerDocumentosObligatoriosFaltantes()` intercepta el proceso de envÃƒÂ­o en tiempo real.
*   **UX:** Badge amarillo `Ã¢Â­ï¿½ OBLIGATORIO` y checklist dinÃƒÂ¡mico que bloquea el botÃƒÂ³n "ENVIAR PERIODO" hasta cumplir la cuota.

---

### MÃƒÂ³dulo 18: ResoluciÃƒÂ³n de AnarquÃƒÂ­a en Desvinculaciones (Escudo de Datos)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Contratista**, quiero que al desvincular a un trabajador sus datos operativos (UO, Lugar, Contrato) se mantengan intactos mientras la nÃƒÂ³mina estÃƒÂ© abierta, para asegurar la reversibilidad total y la trazabilidad histÃƒÂ³rica."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Principio:** "Escritura en Piedra".
*   **LÃƒÂ³gica:** La purga de datos se delega exclusivamente al mÃƒÂ©todo `consolidarReserva()` al momento de la emisiÃƒÂ³n.
*   **ProtecciÃƒÂ³n:** Bloqueo (Hard-Lock) de fechas de finiquito una vez enviado el periodo para evitar alteraciones no auditables.

---

### MÃƒÂ³dulo 19: Protocolo de DeclaraciÃƒÂ³n Jurada (INN)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Administrador de una Empresa Contratista**, quiero que el sistema me presente una declaraciÃƒÂ³n jurada formal antes de enviar mi periodo, para formalizar mi responsabilidad legal sobre la veracidad de los datos entregados segÃƒÂºn exige el INN."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Interfaz:** Modal "Zen" de pantalla completa con base legal (Ley 20.123).
*   **Seguridad:** ValidaciÃƒÂ³n forzada del flag `$declaracion_aceptada` en el backend; cualquier bypass de frontend anula la transacciÃƒÂ³n.

---

### MÃƒÂ³dulo 20: Descarga Masiva de Certificados (EstandarizaciÃƒÂ³n INN)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Supervisor o Emisor**, quiero descargar en lote los certificados finales de mÃƒÂºltiples contratistas bajo una nomenclatura estandarizada, para responder rÃƒÂ¡pidamente a auditorÃƒÂ­as masivas sin gestiÃƒÂ³n manual de archivos."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Componente:** `Shared\DescargaMasivaVerificacion`.
*   **GeneraciÃƒÂ³n:** PDFs generados al vuelo para garantizar integridad de datos.
*   **Nomenclatura:** `PRINCIPAL_LUGAR_CONTRATO_IDREGISTRO_PERIODO.PDF`.

---

### MÃƒÂ³dulo 21: Flujo de RetroalimentaciÃƒÂ³n (Devolver al Auditor)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Supervisor o Emisor**, quiero poder devolver un periodo al Auditor con comentarios especÃƒÂ­ficos si detecto inconsistencias antes de la emisiÃƒÂ³n final, para garantizar que el certificado refleje fielmente la realidad documental sin errores de revisiÃƒÂ³n."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Componente:** `Supervisor\AsignacionVerificacion`.
*   **AcciÃƒÂ³n:** MÃƒÂ©todo `devolverAlAuditor()`.
*   **LÃƒÂ³gica:** 
    - Retrocede el estado de la carpeta de "POR EMITIR" a "AUDITANDO".
    - Captura un comentario obligatorio que se notifica al Auditor responsable.
    - Mantiene la trazabilidad de quiÃƒÂ©n solicitÃƒÂ³ la devoluciÃƒÂ³n.

---

### MÃƒÂ³dulo 22: Filtrado JerÃƒÂ¡rquico Avanzado (Lugar de Trabajo)

#### Historia de Usuario (El QUÃƒâ€°)
"Como **Usuario del Sistema**, quiero visualizar la ruta jerÃƒÂ¡rquica completa de los lugares de trabajo en los desplegables, para evitar confusiones al seleccionar ÃƒÂ¡reas que tienen nombres similares en distintas unidades organizacionales."

#### Ficha TÃƒÂ©cnica / Blueprint (El CÃƒâ€œMO)
*   **Modelo:** `Dependencia` (Atributo `nombre_jerarquico`).
*   **ImplementaciÃƒÂ³n:** TransformaciÃƒÂ³n dinÃƒÂ¡mica del nombre mediante el separador `/` recorriendo recursivamente la relaciÃƒÂ³n de padres.
*   **Alcance:** Aplicado transversalmente en MÃƒÂ³dulos de OperaciÃƒÂ³n, SupervisiÃƒÂ³n y Descarga Masiva.

---

### V8.8 (2026-03-04)
- **Nuevo:** MÃƒÂ³dulo "Informar Contratistas" para Empresas Principales.
  - Permite a la Principal seleccionar quÃƒÂ© contratistas/vinculaciones serÃƒÂ¡n auditados en un periodo especÃƒÂ­fico.
  - GeneraciÃƒÂ³n de exclusiones temporales en `exclusiones_verificacion_periodo`.
  - EdiciÃƒÂ³n directa de fechas de inicio/fin de verificaciÃƒÂ³n en la tabla.
- **Mejora:** Sistema de Colores Estilo "Legacy" unificado.
  - **Contratistas Solitarios:** Fondo Blanco / Gris (`bg-gray-300`) alternado.
  - **JerarquÃƒÂ­as/Grupos:** Resaltado de toda la familia con Amarillo claro / Naranja claro alternado entre grupos.
  - Aplicado en: `informar-contratistas`, `gestion-contratistas` (ASEM) y `mandante/gestion-contratistas`.
- **Mejora:** UX de ValidaciÃƒÂ³n de Rangos.
  - **Bloqueo de Checkbox:** Si el contratista no cubre el periodo, el checkbox se deshabilita automÃƒÂ¡ticamente (`disabled`) para evitar selecciones errÃƒÂ³neas.
  - **Feedback Visual:** Texto "Fuera de rango" en color rojo intenso, negrita y tamaÃƒÂ±o `11px` para mÃƒÂ¡xima visibilidad.
  - **AlineaciÃƒÂ³n de Columnas:** Cajones de fecha con ancho fijo (`110px`) para mantener la estructura de la tabla limpia.
- **Seguridad y Acceso:**
  - **Permisos Globales:** Se actualizÃƒÂ³ el acceso al prefijo `/mandante` para incluir explÃƒÂ­citamente al rol `ASEM_Admin`.
  - **Selector de Principal:** El Administrador ASEM ahora cuenta con un dropdown para elegir la empresa Mandante a gestionar dentro del mÃƒÂ³dulo.

### V8.7 (2026-02-03)
- **CorrecciÃƒÂ³n CrÃƒÂ­tica:** SincronizaciÃƒÂ³n Calendario-Tarjetas de VerificaciÃƒÂ³n Contratista.
  - Las tarjetas de periodo del contratista ahora usan `periodo` (mes de nÃƒÂ³mina) en lugar de `nombre_mes` (mes del calendario).
  - El panel derecho ahora calcula correctamente el nombre del periodo usando `subMonth()`.
  - Archivos corregidos: `resources/views/livewire/contratista/verificacion.blade.php` (lÃƒÂ­neas 79, 184).
- **CorrecciÃƒÂ³n:** Filtrado de periodos por fechas de contrato.
  - Ahora verifica si el **MES DE NÃƒâ€œMINA** (no el mes del calendario) estÃƒÂ¡ dentro de las fechas del contrato.
  - Ejemplo: Contrato desde 01/11/2025 Ã¢â€ â€™ Octubre 2025 NO aparece (nÃƒÂ³mina Oct termina antes del contrato).
  - Archivo corregido: `App\Livewire\Contratista\Verificacion.php` (lÃƒÂ­neas 91-116).
- **Mejora:** Carga de documentos en periodos atrasados.
  - Ahora el contratista puede cargar documentos en CUALQUIER periodo que ya haya abierto.
  - Estados FUERA_PLAZO y VENCIDO ahora permiten carga de documentos.
  - Solo el estado FUTURO bloquea la carga.
  - Archivo corregido: `App\Livewire\Contratista\Verificacion.php` (lÃƒÂ­neas 126-162).
- **CorrecciÃƒÂ³n:** Modo oscuro en tabla de trabajadores vinculados.
  - Reemplazadas todas las instancias de `dark:bg-gray-750` (clase invÃƒÂ¡lida) por `dark:bg-gray-700`.
  - Archivos corregidos: `resources/views/livewire/contratista/verificacion.blade.php` (lÃƒÂ­neas 19, 255, 356, 387).

### V8.6 (2026-01-29)
- **Nuevo:** Roles de verificaciÃƒÂ³n para el mÃƒÂ³dulo de VerificaciÃƒÂ³n Laboral:
  - `Verifica_Supervisor`: Rol de supervisiÃƒÂ³n (permisos pendientes).
  - `Verifica_Analista`: Rol de anÃƒÂ¡lisis (permisos pendientes).
  - `Verifica_Auditor`: Rol de auditorÃƒÂ­a y aprobaciÃƒÂ³n final (2da instancia).
  - Solo pueden ser asignados por `Oval_Admin` (ASEM_Admin).
- **Nuevo:** Flujo completo de VerificaciÃƒÂ³n Laboral:
  - **Supervisor:** AsignaciÃƒÂ³n de Analistas y Auditores, Dashboard de mÃƒÂ©tricas, Nuevas columnas (Fecha EnvÃƒÂ­o, Lugar/Contrato).
  - **Analista:** RevisiÃƒÂ³n documental detallada.
  - **Auditor:** AuditorÃƒÂ­a de segunda instancia (Vista `/auditor/mis-auditorias`).
- **Mejora:** Filtro de subcontratistas en conteo de trabajadores (exclusiÃƒÂ³n estricta).
- **Desarrollo:** 2FA por email **desactivado temporalmente** para facilitar pruebas con mÃƒÂºltiples usuarios.
  - Archivo: `App\Livewire\Forms\LoginForm.php`
  - Buscar comentario: `// 2FA DESACTIVADO TEMPORALMENTE PARA DESARROLLO`
  - Para reactivar: Eliminar el bloque "MODO DESARROLLO" y descomentar "2FA ORIGINAL".

### V8.5 (2026-01-29)
- **Nuevo:** PreselecciÃƒÂ³n automÃƒÂ¡tica de filtros al navegar desde SupervisiÃƒÂ³n a "GestiÃƒÂ³n de Entidades".
  - Al hacer clic en "GestiÃƒÂ³n Entidad" desde cualquier fila de la tabla de SupervisiÃƒÂ³n, se preseleccionan: Contratista/Subcontratista, Lugar de Trabajo, U.O. y NÃ‚Â° Contrato.
  - Funciona tanto en **perfil Principal (Mandante)** como en **perfil OVAL (ASEM)**.
  - DetecciÃƒÂ³n automÃƒÂ¡tica de subcontratistas: Si el ID de la URL corresponde a un sub, encuentra su padre, lo preselecciona y luego preselecciona el sub.
- **Nuevo:** Filtro desplegable de **NÃ‚Â° Contrato** en el Panel de Operaciones (`panel-operacion.blade.php`).
  - Selector verde que muestra TODOS los contratos disponibles del contratista.
  - Permite filtrar por contrato especÃƒÂ­fico.
  - Se sincroniza con la URL para mantener el estado.
- **Mejora:** Grid responsive de filtros ajustado para 4 columnas en pantallas grandes.
- **Archivos Modificados:**
  - `Operaciones.php`, `OperacionesGlobales.php`, `PanelOperacion.php` (PHP)
  - `supervision.blade.php`, `operaciones.blade.php`, `operaciones-globales.blade.php`, `panel-operacion.blade.php` (Blade)

### V8.4 (2026-01-28)
- **Mejora:** SeparaciÃƒÂ³n estricta de gestiÃƒÂ³n de usuarios. Contratistas solo ven sus usuarios internos en el mÃƒÂ³dulo general.
- **Nuevo:** CRUD de gestiÃƒÂ³n de usuarios integrado en el mÃƒÂ³dulo "Mis Subcontratistas" (rol automÃƒÂ¡tico).
- **Mejora:** VisualizaciÃƒÂ³n de jerarquÃƒÂ­a completa (Abuelo -> Padre -> Hijo) en tabla de subcontratistas.
- **Regla de Negocio:** ImplementaciÃƒÂ³n de desactivaciÃƒÂ³n de contratistas en cascada (recursiva).
- **Nuevo:** Columna "Usuario Admin" visible en listado de subcontratistas.

### V8.3 (2026-01-27)
- **Nuevo:** Sistema completo de Sub-Contratistas con jerarquÃƒÂ­a de 4 niveles.
- **Nuevo:** Rol `Subcontratista` con permisos especÃƒÂ­ficos.
- **Nuevo:** MÃƒÂ³dulo "Mis Subcontratistas" para gestiÃƒÂ³n por parte del Contratista Padre.
- **Mejora:** Flujo de aprobaciÃƒÂ³n de sub-contratistas con selecciÃƒÂ³n de vinculaciones heredadas.
- **Seguridad:** RestricciÃƒÂ³n de registro pÃƒÂºblico (solo Contratistas Principales).
- **HomologaciÃƒÂ³n Visual/Operativa:** Las tablas principales de `GestiÃƒÂ³n de Contratistas` entre OVAL_Admin y Principal_Admin ahora poseen una estructura altamente consistente (ej. se comparte SAP y Cuota Trab.), pero conservan restricciones lÃƒÂ³gicas y visuales propias del perfil (Mandante no ve columnas como ID_BD ni Principal, y solo OVAL puede reasignar un Mandante).
- **Nuevo LÃƒÂ­mite Estricto (Hard Limit):** Funcionalidad "Cuota de Trabajadores" (`trabajadores_cuota`) agregada en la tabla pivote de vinculaciones (`contratista_unidad_organizacional`). El sistema valida y bloquea preventivamente (en los formularios de ficha, modal de nueva vinculaciÃƒÂ³n y cargas masivas desde Excel) el registro de trabajadores activos si la cantidad excede la cuota pactada para esa combinaciÃƒÂ³n de UO/Lugar/Contrato. Esta cuota es de carÃƒÂ¡cter "familiar", calculando bajo el lÃƒÂ­mite la suma de trabajadores del responsable de la cuota y de todos sus subcontratistas (hijos, nietos, etc.) vinculados al mismo nÃƒÂºmero de contrato.

### V8.2 (2026-01-22)
- **Nuevo:** Soporte para mÃƒÂºltiples contratos del mismo contratista en la misma UO/Lugar de Trabajo.
- **Nuevo:** Campo `numero_contrato` incluido en constraint ÃƒÂºnico de `contratista_unidad_organizacional`.
- **Nuevo:** Campos `numero_contrato` y `tipo_contrato_id` en `trabajador_vinculaciones` (opcionales).
- **Nuevo:** Filtros por NÃ‚Â° Contrato y Tipo Contrato en el listado de trabajadores.
- **Mejora:** ValidaciÃƒÂ³n preventiva de duplicados con mensajes de error descriptivos.
- **MigraciÃƒÂ³n:** `2026_01_22_180000_update_unique_to_include_numero_contrato.php`
- **MigraciÃƒÂ³n:** `2026_01_22_190000_add_contrato_to_trabajador_vinculaciones.php`

### V8.1 (2026-01-19)
- **Nuevo:** Sistema de VerificaciÃƒÂ³n Laboral con control de Fuera de Plazo.
- **Nuevo:** Campos `fecha_cierre_fuera_plazo` y `fecha_emision_fuera_plazo` en calendario.
- **Nuevo:** Campos `tipo_envio`, `fecha_emision_asignada`, `fecha_envio` en carpetas.
- **Mejora:** Interfaz reorganizada con informaciÃƒÂ³n clara de fechas y estados.
- **Mejora:** Indicadores visuales de DENTRO DE PLAZO / FUERA DE PLAZO.

---

## 11. NOTAS TÃƒâ€°CNICAS DE DESARROLLO

### 11.1. Sistema de AutenticaciÃƒÂ³n de Dos Factores (2FA)

El sistema cuenta con un mecanismo de autenticaciÃƒÂ³n de dos factores por correo electrÃƒÂ³nico.

**Archivo:** `App\Livewire\Forms\LoginForm.php`

**Funcionamiento:**
1. El usuario ingresa email y contraseÃƒÂ±a
2. Si el dispositivo NO es de confianza, el sistema genera un cÃƒÂ³digo de 6 dÃƒÂ­gitos
3. El cÃƒÂ³digo se envÃƒÂ­a por email usando `TwoFactorCodeMail`
4. El usuario ingresa el cÃƒÂ³digo para completar el login
5. Opcionalmente, puede marcar "Confiar en este dispositivo" para evitar el 2FA en futuros logins

**Estado Actual: Ã¢Å¡Â Ã¯Â¸ï¿½ DESACTIVADO PARA DESARROLLO**

Para facilitar las pruebas con mÃƒÂºltiples usuarios sin requerir mÃƒÂºltiples cuentas de correo, el 2FA estÃƒÂ¡ temporalmente desactivado.

**CÃƒÂ³mo Reactivar el 2FA para ProducciÃƒÂ³n:**
```php
// En LoginForm.php, buscar el comentario:
// 2FA DESACTIVADO TEMPORALMENTE PARA DESARROLLO

// Eliminar estas lÃƒÂ­neas (MODO DESARROLLO):
Auth::login($user, false);
RateLimiter::clear($this->throttleKey());
return;

// Y descomentar el bloque "2FA ORIGINAL"
```

### 11.2. Roles del Sistema

| Role | Nombre UI | Tipo Usuario | DescripciÃƒÂ³n |
|-------------|-----------|--------------|-------------|
| ASEM_Admin | Oval_Admin | asem | Superusuario con control total |
| ASEM_Validator | Oval_Validator | asem | Validador de documentos |
| Mandante_Admin | Principal_Admin | mandante | Administrador de empresa principal |
| Mandante_Validator | Principal_Validator | mandante | Validador del mandante |
| Mandante_Ver | Principal_Ver | mandante | Solo lectura |
| Contratista_Admin | Contratista_Admin | contratista | Administrador de empresa contratista |
| Contratista_User | Contratista_User | contratista | Usuario operativo de contratista |
| Subcontratista | Subcontratista | contratista | Usuario de empresa sub-contratista |
| **Verifica_Supervisor** | Verifica_Supervisor | asem | Supervisor del mÃƒÂ³dulo de verificaciÃƒÂ³n |
| **Verifica_Analista** | Verifica_Analista | asem | Analista del mÃƒÂ³dulo de verificaciÃƒÂ³n |
| **Verifica_Auditor** | Verifica_Auditor | asem | Auditor de verificaciÃƒÂ³n (2da instancia) |
| **Operador_IA** | Operador_IA | ia | Operador externo de extracciÃƒÂ³n de datos |


> **Nota:** Los roles `Verifica_Supervisor`, `Verifica_Analista` y `Verifica_Auditor` solo pueden ser asignados por `Oval_Admin`.

---

## 12. HISTORIAL DE VERSIONES

### V8.39 (2026-04-22) - Flujo de DesvinculaciÃƒÂ³n A Prueba de Balas (Maestro + NÃƒÂ³mina)

#### CorrecciÃƒÂ³n CrÃƒÂ­tica: `revertirFiniquitoMaestro` era sordo
- **Bug**: El mÃƒÂ©todo buscaba el registro `FINIQUITADO` en `CarpetaVerificacionTrabajador` usando `WHERE trabajador_vinculacion_id = $vinculacionId`, donde `$vinculacionId` era el ID de la vinculaciÃƒÂ³n de menor ID consolidada en Reserva (MIN). Cuando el registro FINIQUITADO real pertenecÃƒÂ­a a **otra vinculaciÃƒÂ³n** del mismo trabajador (ej: contrato 4000 vs contrato 3000), el query no encontraba nada y el mÃƒÂ©todo retornaba silenciosamente sin ejecutar nada. El botÃƒÂ³n `Ã¢â€ Â© Revertir` del Maestro **parecÃƒÂ­a no hacer nada**.
- **Fix**: La bÃƒÂºsqueda ahora usa `whereIn('trabajador_vinculacion_id', $todosLosIdsDelTrabajador)`, encontrando el CVT FINIQUITADO sin importar cuÃƒÂ¡l de sus vinculaciones lo contiene.
- **EliminaciÃƒÂ³n de doble escritura**: Se eliminÃƒÂ³ la llamada redundante a `$registroNomina->update(['estado_revision' => 'PENDIENTE'])` que ocurrÃƒÂ­a antes del bulk update ya incluÃƒÂ­a ese registro.

#### CorrecciÃƒÂ³n: Limpieza Anti-Fantasma post-ReversiÃƒÂ³n
- **Bug**: Al revertir un finiquito, el bulk update reactivaba TODAS las vinculaciones con `motivo_desactivacion = FINIQUITADO`, incluidos los registros ancla (`dependencia_id=null`, `unidad_organizacional_mandante_id=null`) creados por `consolidarReserva()` en procesos anteriores. Estos anclas aparecÃƒÂ­an como una **tercera fila "EN RESERVA"** espectral en el Maestro.
- **Fix**: DespuÃƒÂ©s de la reactivaciÃƒÂ³n, el sistema verifica si el trabajador tiene vinculaciones activas con `dependencia_id` real. De ser asÃƒÂ­, purga todos los registros con `dependencia_id=null AND unidad_organizacional_mandante_id=null`, eliminando el fantasma de forma definitiva.

#### Paridad Confirmada Maestro/NÃƒÂ³mina
- **Desvincular desde Maestro** (`procesarDesactivacion`): Propaga `FINIQUITADO` a todas las nÃƒÂ³minas abiertas vÃƒÂ­a `sincronizarEstadoEnNominasAbiertas`, preservando UO/Lugar/Contrato si hay nÃƒÂ³mina abierta.
- **Desvincular desde NÃƒÂ³mina** (`cambiarEstadoTrabajadorPeriodo` Ã¢â€ â€™ `propagarDesvinculacion`): Desactiva todas las vinculaciones del trabajador en el Maestro, propaga el estado a todas las nÃƒÂ³minas del mismo perÃƒÂ­odo.
- **Revertir desde Maestro** (`revertirFiniquitoMaestro`): Reactiva todas las vinculaciones + revierte todos los CVT del perÃƒÂ­odo + limpia anclas fantasma.
- **Revertir desde NÃƒÂ³mina** (`cambiarEstadoTrabajadorPeriodo` Ã¢â€ â€™ `revertirDesvinculacion`): Reactiva vinculaciones + revierte otros CVT del perÃƒÂ­odo.
- **Enviar PerÃƒÂ­odo** (`enviarPeriodo`): ÃƒÅ¡nico momento donde `consolidarReserva()` ejecuta la purga definitiva de UO/Lugar/Contrato para los finiquitados confirmados.

### V8.38 (2026-04-15) - Visibilidad Universal de Reserva y ConsolidaciÃƒÂ³n de PolÃƒÂ­tica
- **Visibilidad Universal y Saneamiento de "Fantasmas" (Reserva Estricta)**:
    - Se eliminÃƒÂ³ el "punto ciego" de la reserva consolidada en la vista general (`Lugar: Todos` y `Estado: Todos`).
    - **LÃƒÂ³gica de ExclusiÃƒÂ³n Mutua**: El sistema ahora garantiza que un trabajador aparezca como "En Reserva" **solo si no posee ninguna vinculaciÃƒÂ³n activa**. En el momento en que se le asigna un contrato (Activo), su lÃƒÂ­nea de reserva se oculta automÃƒÂ¡ticamente del Dashboard, eliminando duplicidades visuales.
    - **ConsolidaciÃƒÂ³n QuirÃƒÂºrgica**: Los trabajadores desvinculados sin proyectos activos muestran un ÃƒÂºnico registro representante de reserva, colapsando su historial de finiquitos.
- **ImplementaciÃƒÂ³n de "Reserva ÃƒÅ¡nica" (Single Reserve Record)**:
    - **ConsolidaciÃƒÂ³n FÃƒÂ­sica**: Proceso de saneamiento automÃƒÂ¡tico (`consolidarReserva`) al emitir periodos, purgando vinculaciones redundantes de finiquitados.
- **Refinamiento UX de Re-AsignaciÃƒÂ³n**:
    - Foco exclusivo en la **Cadena (Ã°Å¸â€â€”)** para trabajadores en reserva, forzando el flujo de nueva vinculaciÃƒÂ³n reglamentaria.

### V8.36 (2026-04-09) - SincronizaciÃƒÂ³n de Gobernanza (Exclusiones y Plazos Futuros)
- **EliminaciÃƒÂ³n de AnarquÃƒÂ­as de VerificaciÃƒÂ³n**:
    - **SincronizaciÃƒÂ³n Estricta de MÃƒÂ³dulos**: IntegraciÃƒÂ³n profunda del modelo `ExclusionVerificacionPeriodo` en los submÃƒÂ³dulos Legacy (`VerificacionLegacy`, `VerificacionLegacyCarga`) y Moderno (`Verificacion`). Las exclusiones dictaminadas operativamente por el Mandante en "Informar Contratistas" ahora bloquean obligatoriamente la interacciÃƒÂ³n en ambas interfaces front-end, asÃƒÂ­ como en los guardias de selecciÃƒÂ³n.
    - **Sello Visual Anti-AnarquÃƒÂ­a**: Las tarjetas excluidas por la Principal adquieren un estado irremediablemente inoperable (disabled, escala de grises al 100%, opacidad 40%). Muestran las leyendas definitivas "Periodo no habilitado" (Legacy) o "Ã°Å¸â€Â´ BLOQUEADO POR MANDANTE" (Moderno).
- **Blindaje CronolÃƒÂ³gico Estricto (Continuidad de DotaciÃƒÂ³n)**:
    - **Backend (Loop de Retroceso)**: Se reestructurÃƒÂ³ la lÃƒÂ³gica de validaciÃƒÂ³n en `enviarPeriodo()` (tanto en Moderno como en Legacy) para buscar de manera dinÃƒÂ¡mica la ÃƒÂºltima carpeta "exigible". El sistema retrocede cronolÃƒÂ³gicamente mes a mes ignorando los "baches" (meses excluidos por el Mandante) hasta topar con `fecha_inicio_verifica`. Si existe un periodo anterior vÃƒÂ¡lido, se exige obligatoriamente que este cuente con su certificado en estado `EMITIDO` para permitir la continuidad.
    - **Frontend (PrevenciÃƒÂ³n UX)**: Se implementÃƒÂ³ la funciÃƒÂ³n en vivo `verificarBloqueoSecuencial()` para bloquear visualmente el botÃƒÂ³n "ENVIAR PERIODO" (`disabled`, grisado, `cursor-not-allowed`) e inyectar un *tooltip* nativo indicando con precisiÃƒÂ³n la carpeta pendiente que estÃƒÂ¡ deteniendo la fila de auditorÃƒÂ­a, evitando falsos envÃƒÂ­os y dolores de cabeza.
- **Paridad Operativa y RelajaciÃƒÂ³n de Plazos**:
    - **Choque de FilosofÃƒÂ­as Resuelto**: Se identificÃƒÂ³ que la vista Moderna imponÃƒÂ­a restricciones rÃƒÂ­gidas de Calendario (bloqueando clics en estados `FUTURO` o `S/C`), mientras que el formato Legacy tradicionalmente favorece el trabajo adelantado por sobre la apertura.
    - **Fidelidad**: Se "relajÃƒÂ³" unÃƒÂ¡nimemente el bloqueo frontend de la vista Moderna (`$isBloqueado = $p['fuera_vigencia'] || $p['excluido']`). El resultado es que si el sistema confirma que el Mandante otorgÃƒÂ³ explÃƒÂ­citamente el permiso comercial para ejecutar la verificaciÃƒÂ³n contraintuitiva (ej: PrÃƒÂ³ximamente), el recuadro de validaciÃƒÂ³n pasarÃƒÂ¡ a estar habilitado, equiparÃƒÂ¡ndose con la total libertad con la cual histÃƒÂ³ricamente han trabajado los contratistas en Legacy.

### V8.35 (2026-04-09) - IntegraciÃƒÂ³n Zen, Deep-Linking y EstabilizaciÃƒÂ³n Estructural
- **IntegraciÃƒÂ³n Legacy-Moderno (Modo Zen)**:
    - ImplementaciÃƒÂ³n de **Deep-Linking** desde la grilla 4x3 del Legacy hacia la vista de carga moderna mediante parÃƒÂ¡metros de URL.
    - **Interfaz Adaptativa**: Ocultamiento dinÃƒÂ¡mico del panel lateral y expansiÃƒÂ³n automÃƒÂ¡tica al 100% del ancho (`lg:col-span-12`) cuando el origen es Legacy.
    - **BotÃƒÂ³n "VOLVER"**: DiseÃƒÂ±o minimalista en "Verde Lechuga" Ã°Å¸Â¥Â¬ para navegaciÃƒÂ³n circular eficiente.
- **EstandarizaciÃƒÂ³n de LÃƒÂ³gica "Honesta"**:
    - UnificaciÃƒÂ³n de periodos en ambos mÃƒÂ³dulos: **Enero = 1** (eliminaciÃƒÂ³n de desfases de indexaciÃƒÂ³n).
    - Las tarjetas de la grilla Legacy ahora reflejan correctamente la carga moderna en tiempo real.
- **OptimizaciÃƒÂ³n de Espacio Global**:
    - EliminaciÃƒÂ³n del bloque `<header>` en `app.blade.php` para ganar 64px de espacio vertical en todo el sistema.
- **Refinamiento de UI/UX**:
    - **Tarjetas Legacy**: Cambio de color para 'Periodo no iniciado' de rojo a blanco sÃƒÂ³lido con texto gris oscuro (reducciÃƒÂ³n de ruido visual).
- **EstabilizaciÃƒÂ³n de CÃƒÂ³digo (Blindaje)**:
    - ResoluciÃƒÂ³n de errores de sintaxis Blade y "multiple root elements" mediante una auditorÃƒÂ­a de simetrÃƒÂ­a total: **98/98 etiquetas div** y **34/34 directivas @if**.

### V8.34 (2026-04-08) - AlineaciÃƒÂ³n de NÃƒÂ³mina y Refinamiento de Certificados
- **CorrecciÃƒÂ³n de la "AnarquÃƒÂ­a" de Periodos**:
    - **AlineaciÃƒÂ³n de SelecciÃƒÂ³n**: El mÃƒÂ³dulo "Informar Contratistas" ahora opera bajo el concepto de **Mes de NÃƒÂ³mina**. Al seleccionar "Febrero", el sistema gestiona automÃƒÂ¡ticamente el calendario de marzo y sus exclusiones.
    - **ValidaciÃƒÂ³n de Doble Factor**: El panel del contratista ahora valida obligatoriamente: 1) Que el periodo estÃƒÂ© marcado como "Seleccionado" por el mandante y 2) Que el mes de nÃƒÂ³mina estÃƒÂ© dentro del rango de vigencia del contrato.
- **Refinamiento del Certificado de Cumplimiento (Auditor)**:
    - **Filtro Estricto de Contingencias**: El certificado PDF ahora incluye exclusivamente contingencias marcadas como `retenibles`.
    - **CÃƒÂ³digos Reales**: Se eliminÃƒÂ³ la generaciÃƒÂ³n aleatoria de cÃƒÂ³digos; ahora se utilizan los cÃƒÂ³digos reales (`100.XXX`) persistidos en la base de datos.
    - **SegregaciÃƒÂ³n de Incidencias**: Las observaciones y contingencias no retenibles se excluyen del certificado oficial para limpieza normativa, manteniÃƒÂ©ndose solo en la base de datos y paneles internos.
- **Mejoras UI/UX y Limpieza Visual**:
    - **Dashboard Contratista**: EliminaciÃƒÂ³n de filtros redundantes (IA/EnvÃƒÂ­o) y encabezados innecesarios para simplificar la navegaciÃƒÂ³n.
    - **Etiquetado Positivo**: Reemplazo de la etiqueta `Ã°Å¸â€Â´ FUERA PERIODO` por `Ã¢Å“â€¦ PERIODO ENVIADO` con color verde para mejorar la experiencia del usuario en cargas fuera de ciclo.
    - **Analista**: RemociÃƒÂ³n del sufijo "(DotaciÃƒÂ³n)" en el modal de precierre para evitar ambigÃƒÂ¼edad con el botÃƒÂ³n "Ver Docs".
- **Mantenimiento TÃƒÂ©cnico**: ImplementaciÃƒÂ³n de script de reset completo para datos de verificaciÃƒÂ³n, asegurando una limpieza total de carpetas, documentos y nÃƒÂ³minas temporales sin afectar la acreditaciÃƒÂ³n.

### V8.33 (2026-04-07) - EstandarizaciÃƒÂ³n Visual 1:1 (Legacy-Drawing Style)
- **RediseÃƒÂ±o de Interfaz Financiera (Analista/Auditor)**:
    - ImplementaciÃƒÂ³n de un diseÃƒÂ±o de "bloques sÃƒÂ³lidos" basado en filas ÃƒÂºnicas, eliminando completamente el estilo de tarjetas y sombras.
    - **Paleta Institucional**: Uso de Azul (`#003a5c`) para secciones principales y Amarillo (`#fcc01a`) para sub-secciones de indemnizaciones.
    - **EstandarizaciÃƒÂ³n de Etiquetas**: "Remuneraciones Pagadas", "Cotizaciones Pagadas", "Trabajadores con pago", "Total pagado", "Feriado", "AÃƒÂ±o de Servicio".
- **Paridad de Encabezados de Modal**:
    - SincronizaciÃƒÂ³n total de la disposiciÃƒÂ³n y jerarquÃƒÂ­a visual entre Analista y Auditor.
    - **ID de Registro**: Ubicado a la derecha de la RazÃƒÂ³n Social (`text-blue-300 ml-2`).
    - **SubtÃƒÂ­tulo EstÃƒÂ¡ndar**: Uso de etiquetas de bajo contraste (`text-white/60`) para `PRINCIPAL`, `LUGAR` y `CT`, con separadores de punto medio (`Ã‚Â·`).
- **Layout de Flujo Superior**:
    - ReorganizaciÃƒÂ³n de la cuadrÃƒÂ­cula superior para separar "Trabajadores Revisados" y "RecepciÃƒÂ³n" en una fila compartida (50/50), permitiendo que el bloque financiero ocupe el 100% del ancho inferior.
- **Identidad en el Cierre**:
    - El mensaje de conclusiÃƒÂ³n ahora reconoce dinÃƒÂ¡micamente al usuario logeado ("Estimado(a) [Nombre del Analista]...").

### V8.32 (2026-04-07) - MÃƒÂ³dulo Operador IA y EstandarizaciÃƒÂ³n de Acciones (Ver/Descargar)
- **Nuevo Rol Operador_IA**: 
    - Panel de control en `/ia/extraccion` para gestiÃƒÂ³n de extracciÃƒÂ³n externa de datos.
    - RedirecciÃƒÂ³n automÃƒÂ¡tica desde Dashboard para usuarios de este rol.
- **EstandarizaciÃƒÂ³n de Acciones (Ver/Descargar)**:
    - ImplementaciÃƒÂ³n global de botones duales para la gestiÃƒÂ³n de documentos en los perfiles: **Emisor (Contratista)**, **Analista**, **Auditor**, **Supervisor** y **Operador IA**.
    - Uso mandatorio de la ruta segura `archivo.publico` para todas las descargas, garantizando la validaciÃƒÂ³n de sesiÃƒÂ³n y el nombre original del archivo.
- **Filtro Global ID_REGISTRO**: 
    - Implementado buscador por `ID_REGISTRO` en Supervisor, Emisor y Operador IA.
- **Mejoras UI Supervisor/Emisor**:
    - AdiciÃƒÂ³n de columna dedicada **IA** a la derecha de la columna Auditor.
    - Indicador visual reactivo **IA OK** (Ã°Å¸Â¤â€“) para periodos procesados.
- **Base de Datos**: InclusiÃƒÂ³n de `ia_datos_extraidos` en el modelo `CarpetaVerificacion`.

### V8.31 (2026-04-06) - Blindaje Post-EmisiÃƒÂ³n e Integridad Normativa
- **Blindaje de Integridad (Lockdown)**:
    - Bloqueo irreversible de carpetas en estado `EMITIDO` en todos los niveles (Auditor, Supervisor, Emisor). 
    - backend guards en `MisAuditorias.php` y `AsignacionVerificacion.php` para impedir manipulaciones accidentales o malintencionadas post-certificaciÃƒÂ³n.
- **Acceso Seguro a Certificados**:
    - ImplementaciÃƒÂ³n de ruta neutra `/certificado/visor/{id}` con validaciÃƒÂ³n de propiedad por `contratista_id`.
    - Los contratistas ahora pueden visualizar y descargar sus certificados directamente desde su dashboard si el estado es `EMITIDO`.
- **Mejoras UX Contratista**:
    - IntegraciÃƒÂ³n de resumen de incidencias (Observaciones, Retenibles, No Retenibles) en las tarjetas de historial de periodos.
    - LÃƒÂ³gica de visibilidad que oculta la interfaz de carga en periodos finalizados sin incidencias.
- **Consistencia de Datos**:
    - AdiciÃƒÂ³n del campo `fecha_emision` al proceso de cierre.
    - Casting de `fecha_envio` a objeto Carbon en el modelo `CarpetaVerificacion` para eliminar errores de formateo.

### V8.30 (2026-04-06) - ReconstrucciÃƒÂ³n Auditor y Cierre Laboral V2
- **ReconstrucciÃƒÂ³n MÃƒÂ³dulo de AuditorÃƒÂ­a**:
    - **Paridad Funcional**: RestauraciÃƒÂ³n completa de la vista `mis-auditorias.blade.php` con dashboard de contadores (`Pendientes`, `En ObservaciÃƒÂ³n`, `Aprobados`) y tabla jerÃƒÂ¡rquica.
    - **Mejora en Filtros**: SincronizaciÃƒÂ³n de bÃƒÂºsqueda por Mandante, Contratista y Periodos para el rol Auditor.
- **OptimizaciÃƒÂ³n de Modal de Cierre (Auditor V2)**:
    - **Nuevos Campos de Control**: InclusiÃƒÂ³n de `Trabajadores Revisados` y `Fecha de RecepciÃƒÂ³n` de documentaciÃƒÂ³n.
    - **GestiÃƒÂ³n de Indemnizaciones**: Tabla detallada para Aviso Previo, AÃƒÂ±os de Servicio y Feriado Proporcional con ingreso de cantidad de trabajadores y montos totales.
    - **CÃƒÂ¡lculos DinÃƒÂ¡micos**: Pie de tabla con suma automÃƒÂ¡tica de indemnizaciones del periodo.
- **EvoluciÃƒÂ³n del Sistema de Contingencias**:
    - **Persistencia de Retenciones**: Capacidad de registrar mÃƒÂºltiples incidencias por trabajador directamente desde el modal de auditorÃƒÂ­a.
    - **Flag de RetenciÃƒÂ³n**: ImplementaciÃƒÂ³n del campo `es_retenible` para bloquear la emisiÃƒÂ³n de certificados complemento en caso de deudas previsionales grave detectadas.
- **Flujo de Trabajo Operativo**:
    - Botones de acciÃƒÂ³n rÃƒÂ¡pida (`Aprobar` / `Rechazar`) integrados en el footer del modal con transiciÃƒÂ³n de estados a `PARA_EMITIR` y `EN_REVISION` respectivamente.
- **Mantenimiento**: Limpieza proactiva de cachÃƒÂ© de vistas para asegurar consistencia en cambios estructurales de Blade.

### V8.29 (2026-04-01) - MigraciÃƒÂ³n HistÃƒÂ³rica y Arrastre DinÃƒÂ¡mico
- **Importador de DotaciÃƒÂ³n Anterior**:
    - **Independencia de Carpeta**: Auto-creaciÃƒÂ³n de carpetas en estado `AUDITADO` para periodos histÃƒÂ³ricos importados.
    - **NormalizaciÃƒÂ³n de Fechas**: Fallback al primer dÃƒÂ­a del mes para ingresos vacÃƒÂ­os y mapeo de `FECHA_CONTRATO`.
- **SincronizaciÃƒÂ³n de Arrastre Premium**:
    - LÃƒÂ³gica de sincronizaciÃƒÂ³n idempotente que detecta trabajadores faltantes del mes anterior cada vez que se visualiza un periodo.
    - **Etiquetado de Arrastre**: DiferenciaciÃƒÂ³n visual entre `ARRASTRE` (heredado) y `VIGENTE` (informado/nuevo).
- **Continuidad Garantizada**: Las cargas en el pasado se propagan automÃƒÂ¡ticamente a periodos futuros ya existentes.

### V8.27 (2026-03-31) - Fase Admin y EstandarizaciÃƒÂ³n de NÃƒÂ³mina
- **EstandarizaciÃƒÂ³n de Estados de Trabajadores**:
    - ImplementaciÃƒÂ³n de 4 estados maestros con nomenclatura unificada: `1. ACTIVO`, `2. FINIQUITADO`, `3. MOVIDO`, `4. BAJA_MANDANTE`.
    - **Integridad de Movilidad**: Alerta visual (`Ã¢Å¡Â Ã¯Â¸ï¿½ TRABAJADOR NO REGISTRA...`) si un trabajador marcado como "Movido" no tiene una vinculaciÃƒÂ³n de destino activa.
    - **Respaldo de Baja**: OpciÃƒÂ³n de subir documento de respaldo directamente desde el modal para el estado "Baja a pedido de la Principal".
- **Visibilidad Total (God Mode)**:
    - Los roles `OVAL_Admin` y `ASEM_Admin` ahora tienen visibilidad global en el mÃƒÂ³dulo de verificaciÃƒÂ³n.
    - **Analista**: El dashboard de "Mis Asignaciones" ahora muestra todas las carpetas del sistema para administradores, ignorando el filtro de asignaciÃƒÂ³n individual.
    - **Supervisor**: Acceso a la nÃƒÂ³mina interactiva completa desde el modal de detalle.
- **Enriquecimiento de Datos de NÃƒÂ³mina**:
    - InclusiÃƒÂ³n de columnas `F. Ingreso`, `F. Contrato` y `F. CreaciÃƒÂ³n` en todas las tablas de trabajadores (Contratista, Analista, Auditor, Supervisor).
- **Refinamiento de UX (Limpieza de Modales)**:
    - RemociÃƒÂ³n de botones de flujo de trabajo (**"Marcar como Revisado"**, **"Aprobar AuditorÃƒÂ­a"**) del interior de los modales de documentos.
    - La finalizaciÃƒÂ³n de periodos ahora se gestiona exclusivamente desde los botones de acciÃƒÂ³n en las tablas principales para evitar errores operativos durante la revisiÃƒÂ³n.

### V8.24 (2026-03-30)
- **Mejora en Modal de VinculaciÃƒÂ³n (Herencia de Identidad)**:
    - **SincronizaciÃƒÂ³n AutomÃƒÂ¡tica**: ImplementaciÃƒÂ³n del helper `_precargarIdentidad` que detecta si una empresa ya tiene datos de `SAP` o `ID_Registro` bajo un Mandante especÃƒÂ­fico.
    - **Carga Proactiva**: El sistema autocompleta estos campos al abrir el modal (en modo subcontratista con mandante fijo) o al seleccionar un Mandante en el dropdown, evitando duplicidad de IDs y errores de digitaciÃƒÂ³n.
- **GestiÃƒÂ³n de Recursos HuÃƒÂ©rfanos al Eliminar Vinculaciones**:
    - **Limpieza en Cascada Inteligente**: Al eliminar una vinculaciÃƒÂ³n estructural (Contrato/UO/Lugar) de una empresa, el sistema ahora procesa recursivamente a todos los Trabajadores, VehÃƒÂ­culos, Maquinarias y Embarcaciones asociados.
    - **LÃƒÂ³gica de Reserva de Recursos**:
        - **ÃƒÅ¡nica VinculaciÃƒÂ³n**: Si el recurso solo pertenecÃƒÂ­a a esa vinculaciÃƒÂ³n, se mueve automÃƒÂ¡ticamente al estado **"En Reserva"** (mantiene la ficha tÃƒÂ©cnica pero con UO/Lugar en NULL), preservando su historial documental.
        - **MÃƒÂºltiples Vinculaciones**: Si el recurso opera en otros contratos activos de la misma empresa, simplemente se elimina la asignaciÃƒÂ³n especÃƒÂ­fica del contrato borrado, manteniÃƒÂ©ndolo operativo en sus otros frentes de trabajo.
    - **Alcance**: Aplicado a `TrabajadorVinculacion`, `VehiculoAsignacion`, `MaquinariaAsignacion` y `EmbarcacionAsignacion`.
- **CorrecciÃƒÂ³n de Errores**:
    - Se eliminÃƒÂ³ una validaciÃƒÂ³n residual en el modo subcontratista que impedÃƒÂ­a el guardado de nuevas vinculaciones dinÃƒÂ¡micas en el modal.

### V8.23 (2026-03-26)
- **CorrecciÃƒÂ³n: Nomenclatura de Descarga de Documentos (Analista/Auditor)**:
    - Se modificÃƒÂ³ la llamada a la ruta `archivo.publico` en las vistas de Analistas y Auditores para pasar explÃƒÂ­citamente el parÃƒÂ¡metro `name` del documento.
    - Esto asegura que al descargar documentos, el navegador conserve el nombre original del archivo (ej: `909192-01_2026-D2-1.PDF`) y no lo renombre a un ID genÃƒÂ©rico.
- **Nuevo: Historial de Verificadores (Analista/Auditor Anterior)**:
    - ImplementaciÃƒÂ³n de un accessor dinÃƒÂ¡mico `historial_revision` en el modelo `CarpetaVerificacion` que recupera los ÃƒÂºltimos 3 periodos `REVISADOS` del mismo contratista/vinculaciÃƒÂ³n.
    - **VisualizaciÃƒÂ³n en SupervisiÃƒÂ³n**: Agregadas dos columnas nuevas ("Analista Anterior" y "Auditor Anterior") a la izquierda de las columnas de asignaciÃƒÂ³n actual.
    - Muestra un listado compacto con el nombre del verificador y el periodo correspondiente (Mes-AÃƒÂ±o) para otorgar trazabilidad inmediata del flujo de control previo.

### V8.21 (2026-03-24)
- **EvoluciÃƒÂ³n Interfaz Analista (MÃƒÂ³dulo VerificaciÃƒÂ³n)**:
    - **MigraciÃƒÂ³n a Modales (Overlay)**: Se eliminÃƒÂ³ el panel de detalle "inline" (que desplazaba el contenido) y se reemplazÃƒÂ³ por un **Modal (Popup)** con fondo oscuro y desenfoque (*backdrop-blur*). Esto permite al Analista revisar documentos sin perder el contexto de su lista de asignaciones.
    - **Acciones Duales**: El botÃƒÂ³n ÃƒÂºnico "Revisar" se dividiÃƒÂ³ en dos acciones claras: `VER DOCS` (Apertura de modal de expedientes) y `FINALIZAR` (Apertura de popup de cierre).
- **MÃƒÂ³dulo de Cierre (Popup "Finalizar")**:
    - **RÃƒÂ©plica Fiel OVAL**: ImplementaciÃƒÂ³n de un modal de alta fidelidad visual basado en tablas HTML y estilos inline para replicar exactamente la estÃƒÂ©tica del sistema OVAL (Colores Navy `#1a3560`, tablas de situaciÃƒÂ³n con 5 columnas, banners rojos de alerta y secciones de indemnizaciÃƒÂ³n amarillas).
    - **SimulaciÃƒÂ³n Completa**: Incluye campos para RecepciÃƒÂ³n de Doc/Planilla, Horas Hombre, Remuneraciones, Cotizaciones e Indemnizaciones (Aviso Previo, AÃƒÂ±o de Servicio, Feriado).
- **Limpieza de Interfaz y Robustez**:
    - **CorrecciÃƒÂ³n de Encoding**: Reescritura total del componente a **UTF-8 puro**, eliminando caracteres corruptos (`ÃƒÆ’OÃ‚Â³n`) y emojis que generaban basura visual (`RPÃ‚Â°]`, `ÃƒÂ°Ã…Â¸"`).
    - **UnificaciÃƒÂ³n EstÃƒÂ©tica**: EliminaciÃƒÂ³n de emojis en tÃƒÂ­tulos y botones para una apariencia mÃƒÂ¡s sobria y profesional ("Maestro").

### V8.20 (2026-03-24)
### V8.19 (2026-03-24)
- **AlineaciÃƒÂ³n de Periodos (Fin de la AnarquÃƒÂ­a)**:
    - MigraciÃƒÂ³n masiva de las coordenadas `mes` / `anio` en `CarpetaVerificacion` para representar el mes nominal de las remuneraciones (ej. Noviembre = 11).
    - EliminaciÃƒÂ³n de offsets redundantes (`addMonth` / `subMonth`) en toda la aplicaciÃƒÂ³n.
    - ActualizaciÃƒÂ³n de nombres de archivos en disco para usar el periodo nominal.
- **Nuevo MÃƒÂ³dulo: Descarga Masiva**:
    - ImplementaciÃƒÂ³n de motor de pre-visualizaciÃƒÂ³n reactiva.
    - GeneraciÃƒÂ³n de archivos ZIP estructurados por contratista.
- **Mejoras UI**:
    - CorrecciÃƒÂ³n de tÃƒÂ­tulos en paneles de carga del contratista (conciencia de periodo).
    - Limpieza de rutas de migraciÃƒÂ³n y optimizaciÃƒÂ³n de consultas SQL en Supervisor y Mandante.

### V8.18 (2026-03-19)

#### Overhaul: Sub-tipos de VehÃƒÂ­culo y Refinamiento de Reglas
- **Nuevo: Sub-tipos de VehÃƒÂ­culo por Principal**:
    - Se implementÃƒÂ³ la entidad `SubTipoVehiculoMandante` que permite definir sub-categorÃƒÂ­as de vehÃƒÂ­culos especÃƒÂ­ficas para cada Mandante (ej: CamiÃƒÂ³n Cisterna, CamiÃƒÂ³n Tolva).
    - **VinculaciÃƒÂ³n DinÃƒÂ¡mica**: Al asignar un vehÃƒÂ­culo a un Mandante/UO, se requiere seleccionar el sub-tipo correspondiente para ese contrato.
    - **CRUD Universal**: GestiÃƒÂ³n integrada en el mÃƒÂ³dulo de Listados Universales.
- **Mejora Premium: UI de Reglas Documentales**:
    - **Filtro de Sub-tipos**: IncorporaciÃƒÂ³n del selector de sub-tipos en el modal de reglas, ubicado inmediatamente despuÃƒÂ©s del tipo de vehÃƒÂ­culo.
    - **Legibilidad Extrema**: Todas las aclaraciones ("SIN SELECCIÃƒâ€œN = APLICA A TODOS") se actualizaron a formato **Rojo, Negrita y TamaÃƒÂ±o XL** para garantizar visibilidad al 100%.
    - **UnificaciÃƒÂ³n de LÃƒÂ³gica**: Se estandarizÃƒÂ³ el comportamiento de todos los selectores multi-filtros: el estado vacÃƒÂ­o implica aplicabilidad total.
    - **AclaraciÃƒÂ³n Documento Relacionado**: El texto de ayuda se generalizÃƒÂ³ de "Trabajador" a "Entidad" para reflejar la aplicabilidad a vehÃƒÂ­culos y otros recursos.
- **Base de Datos**:
    - Tabla `sub_tipos_vehiculo_mandante`.
    - Campo `sub_tipo_vehiculo_mandante_id` en `vehiculo_asignaciones`.
    - Tabla pivote `regla_documental_sub_tipo_vehiculo_mandante`.

### V8.9 (2026-03-04)

#### Rol `Mandante_Ver` (Principal_Ver) Ã¢â‚¬â€ Solo Lectura Completo

- **Nuevo:** MenÃƒÂº lateral completo para el rol `Mandante_Ver` con acceso a 8 mÃƒÂ³dulos:
  - Resumen General, Listado de Contratistas, GestiÃƒÂ³n de Entidades, Solicitudes VinculaciÃƒÂ³n, GestiÃƒÂ³n Documentos, GestiÃƒÂ³n de Excepciones, VerificaciÃƒÂ³n, Informar Contratistas.
  - Ã¢ï¿½Å’ Sin acceso a GestiÃƒÂ³n de Usuarios (exclusivo `Mandante_Admin`).

- **Seguridad (2 capas):**
  - **Capa UI:** Botones de acciÃƒÂ³n ocultos con `@if(!$esSoloLectura)` en las 5 vistas de supervisiÃƒÂ³n (empresa, trabajadores, vehÃƒÂ­culos, embarcaciones, maquinaria). En "Informar Contratistas": fechas deshabilitadas, checkboxes bloqueados, botones "Guardar" reemplazados por badge `Ã°Å¸â€â€™ Solo lectura`.
  - **Capa Backend:** Guards `abort(403)` en `SupervisionDetalleGlobal` (3 mÃƒÂ©todos) y `InformarContratistas::guardarSelecciones()`.

- **PropagaciÃƒÂ³n de `$esSoloLectura`:** AÃƒÂ±adido como propiedad pÃƒÂºblica y asignado desde `Auth::user()->hasRole('Mandante_Ver')` en todos los componentes Mandante relevantes.

#### Fixes de bugs

| Bug | Archivo afectado | Causa |
|-----|-----------------|-------|
| `Undefined variable $tiposContrato` solo en `Principal_Ver` | `GestionContratistas.php` | Early-return en `render()` excluÃƒÂ­a a `Mandante_Ver` Ã¢â€ â€™ cambiado `hasRole('Mandante_Admin')` a `hasAnyRole([..., 'Mandante_Ver'])` |
| `syntax error unexpected token "endforeach"` | `tabla-trabajadores-global.blade.php` | `@if(!$esSoloLectura)` sin `@endif` de cierre Ã¢â€ â€™ reescritura limpia del archivo |
| `syntax error unexpected token "elseif"` | `supervision-detalle.blade.php` | `@endif` extra en lÃƒÂ­nea 87 cerraba el bloque prematuramente Ã¢â€ â€™ eliminaciÃƒÂ³n de la lÃƒÂ­nea |

#### Limpieza de logs

- Eliminados 5 `Log::info()` de debug en `CriticidadDocumentoService::determinarAccesoFinalRecurso()` que generaban ruido en el log en cada cÃƒÂ¡lculo de acceso. Solo quedan logs de nivel `error` para fallos reales.

#### Skill creada

- **Archivo:** `.agent/workflows/jerarquia-contratistas/SKILL.md`
- **PropÃƒÂ³sito:** Documenta el patrÃƒÂ³n completo de visualizaciÃƒÂ³n jerÃƒÂ¡rquica de contratistas/subcontratistas con correlativos (`1`, `1.1`, `1.1.1`) y colores de fondo alternos por grupo (Blanco/Gris para simples, Amarillo/Naranja para grupos con hijos).
- **Uso:** Invocar con `/jerarquia-contratistas` para aplicar el patrÃƒÂ³n a cualquier mÃƒÂ³dulo.

---

### V8.8 (2026-03-04)

- **Nuevo:** MÃƒÂ³dulo "Informar Contratistas" para Empresas Principales (`Mandante_Admin`).
  - SelecciÃƒÂ³n de contratistas a auditar en un perÃƒÂ­odo especÃƒÂ­fico (mes/aÃƒÂ±o).
  - ConfiguraciÃƒÂ³n de fechas de verificaciÃƒÂ³n (inicio/fin) por vinculaciÃƒÂ³n.
  - ExclusiÃƒÂ³n de contratistas con fechas fuera del perÃƒÂ­odo (checkbox deshabilitado + aviso "Fuera de rango").
  - LÃƒÂ³gica jerÃƒÂ¡rquica: correlativos `1`, `1.1`, `1.1.1` con colores de fondo alternos (Amarillo/Naranja para grupos, Blanco/Gris para simples).
  - Acceso tambiÃƒÂ©n para `ASEM_Admin` con selector de Principal.
- **Seguridad:** Ruta protegida con middleware `role:Mandante_Admin|Mandante_Ver|ASEM_Admin`.
- **Fix:** `ASEM_Admin` (Oval_Admin) ahora incluido en el middleware del prefijo `mandante`, permitiendo acceso a todos los mÃƒÂ³dulos de Principal sin error 403.



### V8.10 (2026-03-05)

#### Overhaul: MÃƒÂ³dulo SupervisiÃƒÂ³n Global (ASEM)
- **Reescritura de Consulta Core:** Se migrÃƒÂ³ la fuente de datos principal a `contratista_unidad_organizacional` (CUO) para permitir el filtrado preciso por **NÃ‚Â° de Contrato** y **Tipo de Contrato**.
- **JerarquÃƒÂ­a Visual Reconstruida:** 
  - ImplementaciÃƒÂ³n de correlativos jerÃƒÂ¡rquicos automÃƒÂ¡ticos (`1`, `1.1`, `1.1.1`).
  - LÃƒÂ³gica de agrupaciÃƒÂ³n basada en `vinculacion_id` y relaciones padre-hijo detectadas vÃƒÂ­a `solicitudes_vinculacion`.
- **Sistema de Colores Skill-Hierarchy:**
  - **Grupos (Principales + Subs):** Fondo `Amarillo 100` / `Naranja 100` alternado entre familias.
  - **Independientes:** Estilo cebra `Blanco` / `Gris 100` estÃƒÂ¡ndar.
- **Nuevas Funcionalidades de UI:**
  - **Filtros DinÃƒÂ¡micos:** Selector de "Tipo de Contrato" (cargado desde BD) y bÃƒÂºsqueda por "NÃ‚Â° de Contrato".
  - **GestiÃƒÂ³n de Columnas:** 
    - Columna `#` para el correlativo jerÃƒÂ¡rquico.
    - Columna `ID` (`id_registro`) con interruptor para mostrar/ocultar.
    - Columna `ID_BD` (oculta por defecto) con interruptor para diagnÃƒÂ³stico.
- **OptimizaciÃƒÂ³n TÃƒÂ©cnica:**
  - InyecciÃƒÂ³n de propiedades reactivas: `$columnasExcluidas`, `$filtroNumeroContrato`, `$filtroTipoContratoId`, `$tiposContratoDisponibles`.
  - MÃƒÂ©todo `forzarRecalculoEnVivo()` optimizado para procesar el ÃƒÂ¡rbol de jerarquÃƒÂ­a en memoria.

#### Notas de ImplementaciÃƒÂ³n
- **Parche de Escritura:** Debido a bloqueos de sistema en el entorno Windows, se utilizÃƒÂ³ un script PHP externo (`patch_sv2.php`) para aplicar los cambios lÃƒÂ­nea por lÃƒÂ­nea en `SupervisionGlobal.php`, asegurando la integridad del archivo de 22KB.

### V8.11 (2026-03-09)

#### Overhaul: GestiÃƒÂ³n de Reglas Documentales (Historial & UI Premium)
- **Historial de Cambios Legible:**
  - Se implementÃƒÂ³ un mapeo dinÃƒÂ¡mico en `compararYRegistrarCambios` que resuelve IDs tÃƒÂ©cnicos por nombres reales (Cargos, Nacionalidades, UOs, etc.) en los logs de auditorÃƒÂ­a.
  - El modal de historial ahora incluye una cabecera informativa con: **Mandante**, **Entidad Aplicable** y **Nombre del Documento**.
- **ExportaciÃƒÂ³n Individual:**
  - ActivaciÃƒÂ³n de botones **Excel** y **PDF** dentro del modal de historial para descargar la ficha tÃƒÂ©cnica completa de una regla especÃƒÂ­fica junto con su pista de auditorÃƒÂ­a.
- **RediseÃƒÂ±o de Selectores (EstÃƒÂ©tica Premium):**
  - TransformaciÃƒÂ³n de los controles de selecciÃƒÂ³n mÃƒÂºltiple (Cargos, Activos, Tenencias).
- **Exportación Individual:**
  - Activación de botones **Excel** y **PDF** dentro del modal de historial para descargar la ficha técnica completa de una regla específica junto con su pista de auditoría.
- **Rediseño de Selectores (Estética Premium):**
  - Transformación de los controles de selección múltiple (Cargos, Activos, Tenencias).
  - Inclusión de **Badges de Conteo** en tiempo real y micro-iconos de acción (Marcar/Desmarcar) en cabeceras de sección estilizadas.
- **Estandarización de Aplicabilidad:**
  - Refuerzo de la regla: "Selección vacía = No aplica a nadie".
  - Feedback visual en el listado principal con etiquetas en rojo para indicar falta de aplicabilidad específica.
- **Integridad Técnica:**
  - Resolución de errores de sintaxis críticos mediante **reconstrucción atómica por índices de línea**, superando limitaciones de persistencia del entorno local.

---

### V8.13 (2026-03-10)

#### Nueva Funcionalidad: Reportabilidad de Carga (IMC)
- **Implementación del Índice Mensual de Carga (IMC):**
  - Nuevo campo `imc_meses_estimados` en `reglas_documentales`.
  - Accessor dinámico `getImcAttribute()` que calcula el doc/mes: `1 / meses`.
  - Automatización total: Si no hay meses manuales, el sistema calcula el IMC desde `dias_validez_documento` (ej: 365 días → IMC 0.0833).
- **Reporte Ejecutivo Multicapa (Excel):**
  - **Motor de Exportación:** Nueva clase `ReporteImcExport` con soporte para múltiples hojas dinámicas.
  - **Modal de Selección Múltiple:** Interfaz premium para seleccionar Varias Principales a la vez.
  - **Estructura del Reporte:**
    - `Resumen Ejecutivo`: Dashboard consolidado por Principal y desglosado por Entidad (Persona, Vehículo, etc.).
    - `Hojas por Principal`: Detalle íntegro de reglas, meses de vigencia real y número de cargas por año.
    - `Top 25 Global`: Ranking de mayor presión administrativa entre todas las seleccionadas.

### V8.17 - Gestión Avanzada de Migración y Repositorio UI (17-03-2026)
- **Herramienta de Migración Total de Documentos (Módulo de Ingesta Masiva)**:
    - **Repositorio de Archivos Físicos (UI)**:
        - Implementación de un modal de alto rendimiento con **Dropzone.js**.
        - Permite la subida masiva de miles de archivos PDF directamente desde el explorador al servidor (almacenados en `storage/app/public/importar_documentos_fisicos/`).
        - **Contador en Tiempo Real**: Notifica al administrador cuántos archivos están disponibles en el repositorio temporal listos para ser vinculados.
    - **Opcionalidad de Unidad Organizacional (UO)**:
        - **Cambio Estructural**: La columna `unidad_organizacional_id` en la tabla `documentos_cargados` ahora es **TOTALMENTE OPCIONAL** (`nullable`).
        - **Motivo**: Compatibilidad con sistemas legados donde el dato de la jerarquía organizacional no existía o es irrelevante para documentos históricos.
        - **Lógica de Excel**: Se puede dejar la celda vacía o usar el tag `"SIN DATOS/MIGRACION"`.
    - **Control de Duplicidad Pendiente (Integridad)**:
        - **Regla de Bloqueo**: El importador impide cargar un documento con `resultado_validacion` nulo si ya existe un registro pendiente (`NULL`) para la misma entidad y regla.
        - **Feedback**: El sistema informa el error exacto en la tabla de resultados: "Debe procesar el existente antes de cargar uno nuevo". Esto evita sobrecargar a los validadores con trámites duplicados.
    - **Proceso de Ingesta Asíncrona Dual**:
        - **Fase 1**: Carga de archivos físicos (PDFs) al repositorio UI.
        - **Fase 2**: Carga del archivo Excel (Metadata). El sistema vincula automáticamente cada fila del Excel con el archivo físico correspondiente por nombre exacto.
        - Capacidad certificada para manejar volúmenes de +20,000 registros.
    - **Arquitectura de Plantilla Multioja (Excel Premium)**:
        - **Hoja "Instrucciones"**: Guía paso a paso con mapa de 27 campos técnicos, diferenciando obligatorios (8) de opcionales (19).
        - **Hoja "Migración de Documentos"**: Interfaz de carga con validaciones de datos integradas.
        - **Hoja "Listados" (Oculta)**: Catálogos dinámicos de Mandantes, Contratistas, Reglas, Estados de Validación y Tipos de Entidad para asegurar la integridad de la referencia.
    - **Robustez Técnica**:
        - **Snapshots Históricos**: Captura el estado de las reglas y criterios al momento de la migración.
        - **Normalización de Nombres**: La función `cleanCompositeName()` elimina ruidos en nombres de UO y Reglas (guiones, espacios, separadores).
- **Mejoras en Importación de Contratistas**:
    - Generación automática de `id_registro` único por Mandante si no se especifica en el Excel.
- **Robustez en Reglas Documentales**:
    - Normalización automática de cabeceras en el importador de reglas para evitar errores por caracteres especiales o variaciones de nombre (`prepareForValidation`).

### V8.16 (2026-03-12)
- **Overhaul UI: Tooltips "Nivel Dios" (Extendido)**:
    - **Documentos**: Rediseño de tooltips de aplicabilidad con ancho `w-[600px]` y disposición en **dos columnas** (grid) para máxima legibilidad.
    - **Legibilidad**: Escalado de fuentes a `11px/12px` y aumento de tamaño de badges y micro-iconos.
    - **Resumen Ejecutivo**: Tooltips de "Nivel Dios" en tarjetas de `Total Entidades` y `Docs Esperados`, mostrando desglose por tipo (Persona, Vehículo, Maquinaria, etc.) al pasar el mouse.
- **Optimización Vertical (Screen Fill)**:
    - **Tabla Maestra**: Implementación de `min-h-[70vh]` y `max-h-[85vh]` para garantizar que la tabla ocupe la mayor parte de la pantalla.
    - **Sticky Header**: Encabezado de tabla fijo (`sticky top-0`) para mantener el contexto durante el scroll vertical.
    - **Espaciado Inteligente**: Reducción de márgenes (`py-12` -> `py-2`) para optimizar el área de trabajo útil.
- **Interactividad ICM**:
    - Columna interactiva con botones `+/-` para ajuste de meses en tiempo real.

### V8.15 (2026-03-11)

#### Exportación Premium y Auditoría Refinada (Excel/PDF)
- **Motor de Auditoría Quirúrgica**:
  - Se implementó una **comparación estricta por índice de posición** para el historial de criterios. Esto soluciona el fallo donde se ignoraban cambios en criterios duplicados.
  - El sistema ahora guarda matrices JSON estructuradas en el Activity Log, asegurando trazabilidad real campo por campo.
- **Excel Premium (3 Columnas de Auditoría)**:
  - Implementación de las columnas finales: **Valor Anterior**, **Valor Nuevo** y **Cambios Específicos**.
  - La columna de cambios utiliza un sistema de banderas `(+)` y `(-)` para indicar exactamente qué se agregó o quitó de las relaciones y criterios.
- **PDF Ejecutivo de Alto Contraste**:
  - **Código de Colores Estricto**: Rojo (Fondo claro/Texto granate) para lo Antiguo, Verde (Fondo claro/Texto oscuro) para lo Nuevo, y Azul para resaltar el cambio.
  - **Claridad de Lectura**: Se reemplazaron caracteres técnicos (`->`, `?`) por lenguaje natural (ej: `A por B`) para evitar ambigüedades y errores de visualización.

### V8.14 (2026-03-10) [SOLUCIONADO]
- **Fix Crítico**: Los criterios, sub-criterios, aclaraciones y textos de rechazo ya se registran y visualizan correctamente en el historial de cambios tras refactorizar la captura de estados originales en `compararYRegistrarCambios`.

---

## 13. HOJA DE RUTA: SISTEMA V-NEXT (PRÓXIMA GENERACIÓN)

> **⚠ NOTA ESTRATÉGICA:** Esta sección documenta las capacidades arquitectónicas que **NO se implementarán** en el sistema actual (MASTER_AV_14). El sistema actual se completará y estabilizará con su arquitectura actual (Spatie/laravel-permission con roles fijos). Este diseño se conserva como **base para el proyecto sucesor**, el cual se construirá desde cero incorporando estas capacidades desde el día 1.

---

### 13.1. Visión del Sistema V-NEXT

El sistema sucesor será una plataforma enterprise de gestión documental multitenant, construida sobre los aprendizajes del sistema actual pero con una arquitectura de control de accesos **completamente dinámica, configurable en runtime y sin código hardcodeado**.

---

### 13.2. Motor de Control de Accesos (ACL Enterprise)

#### 13.2.1. Aislamiento Multitenant por Grupos

El sistema operará con **tres grupos lógicos estrictamente aislados**:

| Grupo | Actor | Alcance |
|-------|-------|---------|
| `INTERNO` | Personal ASEM/OVAL | Administración global del sistema |
| `PRINCIPAL` | Empresa Mandante | Gestión de sus contratistas e hijos |
| `CONTRATISTA` | Empresa Contratista | Solo su propia información |

**Implementación técnica:**
- **Global Scopes en Eloquent**: Cada modelo inyectará automáticamente el `tenant_id` en curso en todas las consultas, garantizando aislamiento a nivel de ORM. Sin filtros manuales.
- **Tipología de Módulos**:
  - `Generales`: Aplicables a cualquier empresa. Las actualizaciones se propagan en cascada.
  - `Específicos`: Desarrollados a medida para una Principal en particular.
  - **Excepciones**: Una Principal puede quedar excluida de actualizaciones en cascada si tiene lógica de negocio propia.

#### 13.2.2. Modelo de Datos: Tablas Clave

```
perfil
  - id, nombre, grupo (INTERNO|PRINCIPAL|CONTRATISTA), empresa_id, vigente

perfil_modulo_asignado
  - id, perfil_id, modulo_id, puede_ver, puede_crear, puede_editar, puede_eliminar, vigente

modulos
  - id, nombre, slug, grupo, descripción, vigente

usuario_perfil
  - usuario_id, perfil_id
```

#### 13.2.3. Reglas de Perfiles

- **Usuarios Internos:** Multi-perfil, solo con módulos del set `INTERNO`.
- **Usuarios Principal:** Multi-perfil. Cada Principal define y nombra sus propios perfiles con módulos `PRINCIPAL` habilitados.
- **Usuarios Contratista:** Sin perfiles propios. La Principal asigna directamente módulos y permisos del set `CONTRATISTA`.

#### 13.2.4. Dependencia Lógica CRUD

> **Regla Dura**: Sin permiso `Ver`, es **imposible** habilitar `Crear`, `Editar` o `Eliminar`.

- Aplicada a nivel de **interfaz** (botones ocultos/deshabilitados) y a nivel de **backend** (HTTP 403 ante peticiones directas).

#### 13.2.5. Soft-Disable de Módulos (Sin Borrado)

- Columna `vigente` en cada tabla de configuración.
- Al desactivar un módulo de una Principal, **todos sus usuarios y contratistas pierden el acceso inmediatamente**.
- Los registros de datos existentes **no se borran**: quedan huérfanos e inaccesibles por interfaz, pero con integridad referencial intacta para hacer rollback sin pérdida.

---

### 13.3. Sistema de Suplantación de Usuarios (Impersonation)

#### 13.3.1. Jerarquía y Reglas de Alcance

| Suplantador | Puede suplantar a |
|-------------|-------------------|
| Usuario Interno | Cualquier usuario del sistema |
| Usuario Principal | Solo usuarios de sus Contratistas asignadas |
| Usuario Contratista | Nadie (sin permiso de suplantación) |

#### 13.3.2. Modos de Ejecución

| Modo | Capacidad | Restricción UI |
|------|-----------|----------------|
| **Lite** | Solo lectura | Botones Crear/Editar/Eliminar ocultos del DOM |
| **Full** | Capacidad total del suplantado | Sin restricciones visuales adicionales |

#### 13.3.3. Seguridad y Gestión de Sesiones

- **Sudo Mode**: Para iniciar la suplantación, el administrador revalida sus credenciales (contraseña o 2FA).
- **Sin archivos de sesión extra**: La suplantación muta el payload de la sesión existente del suplantador. **Prohibido** crear nuevos archivos de sesión física por cada cambio de contexto (evita degradación del servidor).
- **Timeout por inactividad**: Sesión de suplantación con expiración configurable. Al caducar, el sistema devuelve automáticamente al usuario a su cuenta original.
- **Concurrencia**: Suplantador y suplantado pueden operar simultáneamente bajo contextos de sesión lógicamente separados.

#### 13.3.4. Experiencia de Usuario (UI)

- **Banner persistente e inamovible** durante la suplantación, indicando modo activo y cuenta suplantada.
- **Store/Estado global reactivo** con dos entidades: `AuthUser` (físico) y `ContextUser` (suplantado).

#### 13.3.5. Trazabilidad Inmutable (Regla Crítica)

> Los datos se guardan en el entorno del `ContextUser`. Los logs de auditoría y columnas `created_by`/`updated_by` registran **siempre** el ID del `AuthUser` (el suplantador físico). Este log es **inmutable desde la aplicación**.

---

### 13.4. Paquetes de Referencia (Stack Laravel)

| Capacidad | Paquete Sugerido |
|-----------|-----------------|
| Impersonation | `lab404/laravel-impersonate` |
| ACL Dinámico | `spatie/laravel-permission` (extendido) o implementación propia con las tablas definidas arriba |
| Global Scopes | Nativo Laravel Eloquent |
| Auditoría Inmutable | `spatie/laravel-activitylog` con guards adicionales |
| 2FA | `pragmarx/google2fa-laravel` |

---

### 13.5. Criterios de Aceptación (QA Checklist para V-NEXT)

- [ ] **Aislamiento de Módulos**: Al crear un perfil `INTERNO`, el sistema solo muestra módulos del set `INTERNO`.
- [ ] **Dependencia Ver**: Petición API para crear sin permiso de Ver → HTTP 403. Botón de creación no existe en el DOM.
- [ ] **Visibilidad Reactiva**: Al deshabilitar un módulo para la Principal "A", el módulo desaparece del menú de sus contratistas de forma inmediata.
- [ ] **Restricción Suplantación**: Un usuario de la Principal "A" solo ve en el menú de suplantación a los usuarios de sus propias contratistas.
- [ ] **Restricción UI Modo Lite**: Botones de guardar/eliminar deshabilitados y ausentes del DOM.
- [ ] **Rechazo API Modo Lite**: POST/PUT forzado → HTTP 403 + registro del intento en log de seguridad.
- [ ] **Gestión de Sesiones**: 50 suplantaciones seguidas → solo 1 archivo de sesión activo por usuario.
- [ ] **Caducidad de Suplantación**: Inactividad de X minutos → revocación automática + modal de advertencia.
- [ ] **Auditoría Inmutable**: Acción en Modo Full → datos en BD del suplantado, `created_by` = ID del suplantador físico. Campo no editable desde la aplicación.

---

> **Referencia de Origen**: Esta arquitectura fue propuesta en el contexto del proyecto MASTER_AV_14 el **19-20 de marzo de 2026** como mejora arquitectónica. Se decidió conservarla como diseño base para el proyecto sucesor, completando el sistema actual sin modificar su arquitectura de roles.

---
---

## 14. HOJA DE RUTA - PRÓXIMOS DESARROLLOS (VERIFICACIÓN)

### Fase 1: Implementación de Dotación Histórica (Anterior)
- **Migración de Resultados Legados**: Desarrollo de un motor de ingesta para cargar certificados previos (Legacy) con campos de identificación unívoca (`ID_REGISTRO`, `LUGAR + CONTRATO`).
- **Snapshot de Continuidad**: El sistema utilizará la última dotación verificada registrada en este portal para inicializar el "Arrastre" de trabajadores en el primer periodo gestionado totalmente en la nueva plataforma.
- **Independencia de Retenciones**: Las contingencias monetarias (Retenciones) **NO se arrastran** de un periodo a otro automáticamente. Cada periodo es independiente y se resuelve mediante solicitudes complementarias; el histórico permanece como registro inmutable del hito.

### Fase 2: Flujo de Emisión y Firma
- **Automatización del Certificado**: Desarrollo del motor de generación de Certificados de Cumplimiento basados en los resultados del Analista y la aprobación del Auditor.
- **Flujo de Firma Digital**: Implementación de firma electrónica o código QR de verificación de integridad para los documentos emitidos.

### Fase 3: Certificados Complementarios
- **Módulo de Rectificación**: Permitir a los contratistas solicitar la revisión de una contingencia específica de un periodo cerrado, generando un certificado anexo que libere retenciones informadas previamente.

### Fase 4: Reconstitución del Histórico de Expedientes
- **Carga de Documentación Física**: Una vez operativos todos los flujos en vivo, se habilitará la carga masiva de los expedientes PDF históricos para completar el archivo digital total de la empresa principal.

### Fase 5: Automatización e IA (Gemini Studio Integration)
- **Implementación de API Gemini**: Integración del modelo 2.5 Flash-Lite para la lectura automática de liquidaciones de sueldo.
- **Validación Automática de Nóminas**: Sistema de semáforos basado en la coincidencia de datos extraídos por IA vs datos maestros del sistema.
- **Detección Proactiva de Inconsistencias**: Alertas en tiempo real sobre documentos que no corresponden al trabajador o periodos con montos fuera de rango.

## 15. GOBERNANZA DE DESVINCULACIÓN (REGLAS DE NEGOCIO)

Esta sección define el comportamiento lógico del sistema ante los diferentes estados de desvinculación para evitar "Anarquías de Datos" entre el Maestro de Trabajadores y el Módulo de Nómina.

### 15.1. Matriz de Comportamiento de Estados

| Código | Estado | Alcance (Scope) | Efecto en Maestro | ¿Desactiva otros contratos? | Propósito Operativo |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **2** | **FINIQUITADO** | **GLOBAL** | Mueve a "RESERVA" | ✔ **SÍ (TODOS)** | El trabajador se retira definitivamente de la empresa. |
| **3** | **CESACIÓN EN LA PRINCIPAL** | **GLOBAL** | Mueve a "RESERVA" | ✔ **SÍ (TODOS)** | Termina relación contractual actual (ej: para cambio de contrato). |
| **4** | **RECONOCIMIENTO ANTIGÜEDAD** | **GLOBAL** | Mueve a "RESERVA" | ✔ **SÍ (TODOS)** | Salida de la contratista (análogo a un finiquito). Traspaso de antigüedad a otra empresa. |
| **5** | **PRESENTE EN OTRA...** | **LOCAL** | Mueve a "INACTIVO" | ✘ **NO** | Corrección de duplicidad. Solo anula la vinculación seleccionada. |
| **6** | **LICENCIA MÉDICA** | **INFORMATIVO** | **NADA** | ✘ **NO** | Solo informa el estado del mes en nómina para auditoría. |

### 15.2. Reglas de Oro para el Usuario

1.  **El Finiquito es una Orden Total**: Al elegir estados **2, 3 o 4**, el sistema entiende que el trabajador ya no presta servicios operativos bajo ninguna modalidad actual. Por seguridad, el sistema "apaga" todas sus vinculaciones vigentes para evitar pagos duplicados o históricos sucios.
2.  **La Reserva no es Borrado**: Un trabajador en "Reserva" mantiene su hoja de vida y documentos. Simplemente no aparece en la dotación activa del día a día.
3.  **Protección del Pasado (Escudo)**: Una desvinculación o finiquito procesado hoy no afectará a las nóminas de meses anteriores que ya fueron cerradas o están en curso, a menos que se use el botón de reactivación.
4.  **Reactivación Global**: Si se reactiva un contrato que fue finiquitado globalmente, el sistema reactivará automáticamente los contratos hermanos para restaurar la coherencia de la dotación.
5.  **Re-contratación Inteligente**: El sistema detecta automáticamente si un trabajador ha pertenecido previamente a la empresa (Reserva). Al ingresar el RUT en el formulario de "Nuevo Trabajador", el sistema alertará del hallazgo y precargará los datos personales, permitiendo una re-vinculación fluida y sin re-digitación. 
6.  **Blindaje de Confidencialidad**: El reconocimiento histórico es EXCLUSIVO para cada contratista. Una empresa C podrá ver la ficha técnica de un trabajador previamente contratado por ella, pero NUNCA podrá acceder a datos de trabajadores pertenecientes a otras empresas mediante el ingreso de RUT.

### 15.3. El Protocolo de "Anarquía Cero" (Ciclo de Vida)

Este protocolo garantiza que la base de datos operativa se mantenga limpia, coherente y libre de duplicados visuales, protegiendo siempre la integridad de los documentos y el historial de auditoría.

#### A. Definiciones de Entidades
*   **La Ficha (Trabajador)**: Contiene los datos "biológicos" y personales (Nombre, RUT, etc.). Es **permanente** y global para el contratista.
*   **La Vinculación (Registro Operativo)**: Es el vínculo técnico que dice "Jose es Pintor en la UO 15 con el Contrato 3000". Puede haber muchas históricas, pero solo debe haber **una** operativa.

#### B. Protocolo de Salida Global (Anarquía Cero - Purga Física)
Cuando se aplica el Estado 2, 3 o 4 (Finiquito, Cese o Antigüedad) a un trabajador con múltiples cargos activos:
1.  **Saneamiento Masivo**: El sistema desactiva (`is_active = 0`) TODAS sus vinculaciones actuales.
2.  **Vaciado Técnico**: Se limpian los campos de Lugar, UO y Contrato (NULL) en todos los registros para evitar que el trabajador "arrastre" deudas de acreditación.
3.  **Purga Física Agresiva**: Un **Saneador Global** identifica las filas duplicadas y vacías, eliminando **DEFINITIVAMENTE (`Hard Delete`)** las redundantes y dejando **EXACTAMENTE UNA** fila en Reserva.
    *   *Propósito*: Que en el Panel de "En Reserva", el trabajador aparezca una sola vez, sin residuos visuales ni registros "fantasmas" en la base de datos operacional.

#### C. Protocolo de Re-Ingreso Zen (Mutual Exclusion)
Cuando un trabajador vuelve a ser contratado tras haber estado en Reserva:
1.  **Reconocimiento por RUT**: Al ingresar el RUT en "Nuevo Trabajador", el sistema identifica la **Ficha** existente y completa los datos automáticamente.
2.  **Validación de Ubicación Obligatoria**: El sistema **BLOQUEA** el guardado si el lugar seleccionado es "Reserva". Se le EXIGE un Lugar de Trabajo real (Planta, UO, etc.) para poder activar al trabajador.
3.  **Exclusión Mutua (Activo vs Reserva)**: Al presionar Guardar, el sistema **borra físicamente** el registro de reserva antes de crear la nueva vinculación activa.
    *   *Regla de Oro*: Un trabajador NUNCA puede estar activo y en reserva simultáneamente. Una vez activado, su pasado en reserva en la tabla operacional deja de existir.

#### D. Reglas de Persistencia Documental (Leyes de Aislamiento)
1.  **Persistencia Local (Misma Principal)**: Dentro del mismo Mandante, los documentos transversales (ej: Carnet, Título) se mantienen aprobados si la regla documental es la misma. El ahorro administrativo es del 100%.
2.  **Aislamiento Global (Principales Distintas)**: Si el trabajador cambia de Principal, los documentos **NO se heredan**. Cada Mandante es un silo independiente. Debido a que cada `DocumentoCargado` se vincula a un ID de Regla único por Principal, el trabajador debe ser re-acreditado bajo las normas del nuevo Mandante.

---

### 15.4. Saneador Global Automático (Mantenimiento Continuo)

El sistema incluye un **Saneador Global** que se ejecuta en cada operación de guardado de ficha. Este componente escanea la base de datos para el trabajador en cuestión y elimina instantáneamente:
*   Registros con Lugar de Trabajo inválido (NULL o 0).
*   Vinculaciones inactivas que no aportan al historial de reserva.
*   Cualquier inconsistencia que pueda generar visualizaciones duplicadas en los filtros de la plataforma.

---

## 16. Sincronización de Contingencias y Paridad de Módulos

Esta fase asegura una comunicación fluida entre el Contratista que subsana y el equipo de Auditoría/Supervisión que valida, eliminando asimetrías de información y optimizando el flujo de asignación.

### 16.1. Panel del Contratista: El "Semáforo del Éxito"

Se ha implementado una lógica de retroalimentación inmediata para el contratista basada en el campo `estado_subsanacion`.

*   **Tarjetas Mensuales Dinámicas**: Las tarjetas de contingencias del contratista ahora cambian a **Verde Esmeralda (`#28a745`)** automáticamente cuando el número de pendientes es cero.
*   **Sincronización de Cierre**: Una contingencia se considera cerrada solo cuando el Auditor la marca como `SOLUCIONADO`. En ese instante, el acordeón del mes correspondiente se libera de alertas rojas.
*   **Bloqueo de Seguridad**: Los ítems ya subsanados y aprobados quedan bloqueados para selección, impidiendo re-envíos duplicados de evidencia.

### 16.2. Módulo de Gestión Complementaria: Paridad Total (Supervisor/Auditor)

Se ha rediseñado el panel del **Supervisor (Asignación)** para igualar en potencia y estética al panel del **Auditor (Gestión)**, permitiendo una administración mucho más ágil de las incidencias.

#### A. Buscador Inteligente Universal (Caja 360°)
Se reemplazó el antiguo filtro de "Folio/Registro" por una **Búsqueda Universal** bajo la etiqueta **"ID"**. Este campo procesa inteligentemente:
1.  **ID de Registro**: El identificador legacy para migración de datos.
2.  **RUT del Trabajador**: Búsqueda por documento de identidad (sin caracteres especiales).
3.  **Nombre Completo**: Filtrado por nombres o apellidos del afectado.
4.  **Folio de Sistema**: Trazabilidad del folio generado por la plataforma.
5.  **Código de Incidencia**: Localización directa por el número único de incidencia (ej: 100001).

#### B. Arquitectura de Filtros Horizontales Premium
Se estandarizó el **Grid de Filtros Horizontal** (Navy Blue) en ambos módulos, incluyendo:
*   Filtros en cascada (Principal -> Contratista -> Lugar -> Contrato).
*   Selector de **Tipo de Ítem**: Permite separar rápidamente Observaciones de Contingencias (Retenibles/No Retenibles).
*   Filtro por **Periodo**: Año y Mes de la incidencia original.

#### C. Lógica de Estados "Activos"
Se introdujo el concepto de solicitudes **"Activas"** como filtro por defecto para limpiar el ruido operativo:
*   **Activas**: Incluye tanto las solicitudes **Pendientes de Asignar** (Pte. Asignar) como las que ya están **En Revisión**.
*   **Opción "Todos"**: Permite al usuario romper el filtro por defecto y visualizar el historial completo (incluyendo Solucionados y Rechazados).

### 16.3. Protocolos de Asignación (Supervisor/Emisor)

El Supervisor ahora visualiza una tabla enriquecida que incluye:
*   **ID_REGISTRO** en primera columna para identificación rápida.
*   **Periodo Destacado** (Mes Short / Año) con fondo índigo.
*   **Columna de Asignación**: Selector de Auditor vinculado directamente al estado de la solicitud, con capacidad de "Quitar" asignación si se requiere re-gestión.
*   **Visor Indigo**: Modal de detalle (Read-only para Supervisor) que permite pre-visualizar la evidencia enviada por el contratista antes de delegar la revisión a un Auditor.

---

### 16.4. Paridad de Nóminas y Lógica de "VERIFICADO"

Para garantizar una experiencia de usuario coherente y eliminar confusiones en el cierre de procesos, se ha estandarizado la visualización de la nómina de trabajadores en todos los roles (Contratista, Auditor y Supervisor).

#### A. Estructura Única de 7 Columnas (Layout 360°)
Se ha implementado el mismo diseño de tabla en el Escritorio de Carga y en los detalles de Auditoría/Supervisión, incluyendo:
1.  **RUT**: Con indicador de "Arrastre" si corresponde.
2.  **Nombre Completo**: En mayúsculas, fuente negra de alta visibilidad.
3.  **Cargo**: Según catálogo del principal.
4.  **F. Ingreso**: Fecha de vinculación al contrato.
5.  **F. Contrato**: Fecha de firma del documento contractual.
6.  **NUEVO (Badge)**: Indicador automático si el trabajador ingresó dentro del mes auditado.
7.  **Estado Verificado (Semáforo)**:
    *   **Ámbar (bg-amber-100)**: Estado `PENDIENTE` (En revisión).
    *   **Verde (bg-green-100)**: Estado `VERIFICADO` (Finalizado).
    *   **Rojo (bg-red-100)**: Estado `FINIQUITADO`.
    *   **Púrpura (bg-purple-100)**: Estado `BAJA POR PRINCIPAL`.

#### B. Protocolo de "Sello Final" (SELLADO EN PIEDRA + PURGA AFUERA)
Para garantizar una experiencia de usuario coherente y eliminar confusiones en el cierre de procesos:
1.  **Sellado en Piedra (Snapshots)**: Al presionar "EMITIR CERTIFICADO", se refrescan los datos de RUT, Nombre y Cargo en la tabla histórica.
2.  **Finalización Automática**: El estado de todos los trabajadores `PENDIENTE` pasa a `VERIFICADO`.
3.  **Purga Física Post-Emisión**: Inmediatamente después del sellado, se ejecuta el **Saneador Global** para todos los desvinculados del periodo. 
    *   *Lógica*: Si el trabajador termina el ciclo en reserva, se borran físicamente sus excedentes y se consolida su única línea de reserva. Si fue re-contratado durante el ciclo, se borra su registro de reserva histórico.
---

## 17. ESTANDARIZACIÓN DE CONDICIONES DE VEHÍCULO Y REGLAS (ZEN UI)

Esta fase consolida la arquitectura de "Condiciones" para todas las entidades (Persona, Empresa, Vehículo) y refina la experiencia de usuario en el módulo de Reglas Documentales, eliminando informalidades y optimizando la navegación operativa.

### 17.1. El Patrón "Zen UI" (Inclusión por Omisión)

Se ha estandarizado la lógica de filtrado en las Reglas Documentales bajo el principio de **Exclusión Activa**:
- **Sin Selección = Aplica a Todos**: Si una regla no tiene condiciones marcadas, el sistema asume que es transversal a toda la dotación/flota. Esto reduce drásticamente la carga administrativa al evitar la selección redundante para reglas generales.
- **Con Selección = Filtro Específico**: Solo cuando se marcan opciones específicas, la regla se restringe estrictamente a ese subconjunto.
- **Entidades Cubiertas**: Esta lógica opera en armonía para Condiciones de Persona, Condiciones de Empresa y **Condiciones de Vehículo**.

### 17.2. Gestión de Flota: Condiciones de Vehículo

Se integraron las condiciones dinámicas como tercer eje de validación para vehículos:
1. **Multi-Select Integrado**: El modal de gestión de reglas incluye ahora un selector múltiple de condiciones de vehículo.
2. **Motor de Cálculo (Service Layer)**: `DocumentoRequeridoService.php` realiza intersecciones lógicas entre las condiciones activas en la vinculación del vehículo y las exigidas por la regla.
3. **Persistencia y Auditoría**: Todas las modificaciones en las aplicaciones de condiciones se registran en el `ActivityLog`, permitiendo reconstruir el estado de exigibilidad en cualquier punto del tiempo.

### 17.3. Refinamientos de Estética Corporativa y Usabilidad

Para alinear el sistema con estándares de alta gama ("Premium"), se aplicaron las siguientes mejoras:
- **Orden Alfabético Predictivo**: Las tarjetas de desglose (IMC y Documentos Esperados) en el dashboard de Reglas se auto-ordenan alfabéticamente (`ksort`). Esto mejora la memoria muscular del usuario al encontrar cada categoría siempre en la misma posición.
- **Purgado de Informalidades (Zero Emojis)**: Se eliminaron emoticonos literales de etiquetas y advertencias, sustituyéndolos por iconografía SVG minimalista y tipografía profesional de alto contraste.
- **Ergonomía de Modales**: Se estandarizó una altura mínima de **70vh** para el modal de vinculación de trabajador, optimizando el aprovechamiento del espacio vertical en pantallas de alta resolución.

### 17.4. Estabilidad Técnica en Componentes Reutilizables
- **Robustez de Partials**: El componente `_multi_select_condicion.blade.php` incluye ahora valores por defecto preventivos para `$wireKey`, eliminando errores de tiempo de ejecución en contextos de edición donde la variable no sea explícitamente inyectada.

---

### 16.5. Nómina "Escrita en Piedra" (Archivo Histórico)

Para resolver la "anarquía de datos" donde los trabajadores desaparecían del historial al ser finiquitados o consolidados en reserva, se ha implementado un sistema de **Snapshots Denormalizados**.

#### A. Mecanismo de Captura (Snapshot)
Al momento de sincronizar la dotación con una Carpeta de Verificación (ya sea por inicio de periodo o carga histórica), el sistema realiza una "fotografía" de los datos maestros del trabajador en ese instante:
-   **snapshot_rut**: Identificación tributaria.
-   **snapshot_nombres**: Nombre completo registrado.
-   **snapshot_cargo**: Cargo asignado bajo el contrato.
-   **snapshot_fecha_ingreso**: Fecha de vinculación a la vinculación.
-   **snapshot_fecha_contrato**: Fecha de firma del contrato original.

#### B. Protocolo de "Sellado en Piedra" (Cierre Definitivo)
Para cumplir con la máxima de "ni antes ni después", el sistema opera bajo un flujo de dos etapas:
1.  **Captura Preventiva (Borrador)**: Al entrar en nómina, se genera un snapshot inicial. Esto garantiza que trabajadores desvinculados durante el ciclo (como despidos a mitad de mes) permanezcan visibles para el auditor.
2.  **Sellado Final (Emisión)**: Al momento exacto de presionar **"EMITIR CERTIFICADO"**, el sistema realiza un refresco masivo:
    *   Si el trabajador tiene datos actualizados en el maestro (ej: corrección de un apellido por el auditor), el snapshot se actualiza con la versión final.
    *   Si el trabajador ya no existe en el maestro (finiquitado), se conserva la captura preventiva.
3.  **Bloqueo Inalterable**: Una vez en estado `EMITIDO`, la tabla de snapshots queda blindada. Ya no se realizarán más refrescos, permitiendo que el archivo histórico sea un reflejo fiel del momento de la emisión para siempre.

#### C. Integridad del Certificado
Este cambio garantiza que cualquier auditoría retrospectiva encuentre los datos exactos que fueron verificados en su momento, eliminando el riesgo de "registros fantasma" o desapariciones por consolidación de anarquía.

---

## 18. ESTANDARIZACIÓN Y SEGREGACIÓN DE GESTIÓN DOCUMENTAL (MANDANTE/PRINCIPAL)

Esta fase establece un marco operativo seguro y eficiente para el flujo híbrido de validación entre administradores (`Mandante_Admin`) y validadores externos (`Mandante_Validator`), garantizando la integridad del proceso y la especialización de roles.

### 18.1. Segregación de Responsabilidades y Seguridad (RBAC)

Se han implementado fronteras estrictas para evitar el cruce de responsabilidades entre el equipo de auditoría central (OVAL/ASEM) y el equipo interno del Mandante:
- **Blindaje de Asignación**: Los administradores de OVAL tienen restringida la capacidad de asignar documentos que posean una regla de "Solo la Principal". Esto evita que documentos que no corresponden a la gestión de OVAL terminen accidentalmente en sus colas de trabajo.
- **Visibilidad Granular**: 
    - **Administrador Mandante**: Conserva una visión global de toda la gestión documental de su Principal, con acceso a filtros de supervisión y herramientas de asignación masiva.
    - **Validador Mandante**: Opera bajo un modelo de "Cola de Tarea Única". Solo visualiza los documentos que le han sido asignados explícitamente por su administrador, simplificando su interfaz al máximo.

### 18.2. Módulo "Panel de Validación" (Validator Experience)

Se ha estandarizado la experiencia del validador interno para que sea análoga a la de los auditores profesionales de OVAL:
1. **Filtros Avanzados**: Se integró una barra de búsqueda rápida por Contratista, Entidad, Nombre de Documento e ID de Entidad (RUT/Patente).
2. **Navegación Enfocada**: El enlace "GESTIÓN DOCUMENTOS" en el menú lateral redirige a los validadores directamente a su panel de trabajo (`PanelValidacionMandante`), eliminando el acceso a dashboard administrativos complejos.
3. **Zen UI Defensivo**: La interfaz del validador oculta automáticamente checkboxes de selección masiva, botones de revalidación y selectores de asignación, dejando el botón "REVISAR" como la única acción primaria disponible.

### 18.3. Refinamientos de Gestión Administrativa

Para los roles administrativos del Mandante, se optimizó el dashboard global:
- **Filtro de Responsabilidad/Flujo**: Se añadió un filtro que permite identificar rápidamente los documentos que requieren validación exclusiva del Mandante o doble validación, facilitando la gestión de pendientes propios.
- **Ordenamiento y Estética**: Las columnas de la tabla se alinearon con el estándar corporativo de OVAL, situando las columnas de control operativo (Asignar) al inicio y las de resultado/vencimiento al final para mejorar la legibilidad.
- **Saneamiento de UI**: Se eliminaron elementos residuales y se corrigieron errores de renderizado en encabezados, asegurando una visualización "Pixel Perfect" en todas las resoluciones.

---

## 19. SISTEMA DE VERIFICACIÓN: DOCUMENTOS OBLIGATORIOS PARA ENVÍO

Esta fase resuelve la vulnerabilidad de envíos en blanco en el proceso de Verificación Mensual (Remuneraciones), implementando una barrera de contención dinámica y estrictamente configurable desde la administración central.

### 19.1. Filosofía de "Configurabilidad Nivel Dios"

Para evitar lógicas *hardcodeadas* que limiten la escalabilidad, se estableció un modelo de configuración de obligatoriedad a nivel de Base de Datos:
- **Modelo de Datos Modificado**: Se añadió el campo booleano `es_obligatorio` a la tabla `requisitos_verificacion`.
- **Delegación Operativa**: La responsabilidad de definir qué documentos bloquean un envío mensual recae 100% en el rol `ASEM_Admin`. Desde la interfaz de configuración de la Verificación, el administrador puede crear o editar un requisito (ej. "Liquidaciones de Sueldo") y marcar explícitamente el flag "Es Obligatorio para Envío".

### 19.2. Barrera de Contención (Backend)

La lógica de bloqueo se centraliza en los controladores del Contratista (`VerificacionLegacyCarga` y `Verificacion`):
1. **Detección Dinámica**: El método privado `obtenerDocumentosObligatoriosFaltantes()` evalúa en tiempo real los requisitos activos del Mandante que tengan el flag `es_obligatorio = true`.
2. **Cruce Estricto**: Por cada requisito obligatorio, el sistema verifica si existe al menos un registro en `documentos_verificacion` asociado a la carpeta del periodo actual.
3. **Bloqueo Secuencial y Feedback**: Si existen requisitos obligatorios sin archivos cargados, el método `enviarPeriodo()` aborta la transacción y retorna un arreglo con los nombres precisos de los documentos faltantes, los cuales se renderizan en el frontend como alertas.

### 19.3. UX Defensivo y Orientación al Contratista (Zen UI)

Para garantizar que el contratista no experimente confusión ante un botón de envío bloqueado, la interfaz despliega un diseño guiado:
- **Visualización Explícita de Exigibilidad**: Cada requisito en la tabla que posea el flag verdadero muestra permanentemente un badge amarillo de alto contraste (`OBLIGATORIO`).
- **Checklist de Faltantes Dinámico**: Inmediatamente arriba del botón "ENVIAR PERIODO", se despliega una caja de advertencia (color rojo) que enlista exactamente qué documentos le falta subir a la empresa para poder enviar. Esta lista desaparece dinámicamente conforme el usuario sube los archivos requeridos.
- **Botón Inteligente**: El botón principal de envío permanece en estado deshabilitado (`disabled`, opacidad reducida) y solo se activa cuando el arreglo de faltantes reporta cero.

---

## 20. RESOLUCIÓN DE ANARQUÍA EN DESVINCULACIONES Y CIERRE DE PERIODOS

Esta fase resuelve las anarquías de datos originadas por los procesos de finiquito y la sincronización entre el estado maestro de los trabajadores y los periodos de nómina.

### 20.1. Eliminación de Borrado en Cascada (Cascading Delete)
Se erradicó una falla crítica en el método `consolidarReserva()`, el cual eliminaba físicamente (`delete()`) las vinculaciones de los trabajadores al enviar una nómina con un estado inactivo. Esto causaba una reacción en cadena (por el `onDelete('cascade')` de la BD) que eliminaba al trabajador de **todas** las nóminas activas en otros contratos.
- **Acción Correctora**: Se removió la purga agresiva. Ahora los registros inactivos permanecen en la BD para mantener el rastro histórico y la visibilidad transversal en otras nóminas.

### 20.2. Candado en Fechas de Finiquito (UX/UI Defensivo)
Para prevenir alteraciones no auditables, se implementó un bloqueo estricto (Hard-Lock) en los inputs de fechas:
- **Verificación y Legacy**: En ambas vistas de contratista (`verificacion` y `verificacion-legacy-carga`), el campo `<input type="date">` para la fecha de finiquito/término queda completamente deshabilitado (`disabled`) si:
  - La carpeta actual tiene el estado `ENVIADO`.
  - La desvinculación ya fue confirmada (bloqueada) por **otra carpeta** que ya se envió en el mismo periodo.

### 20.3. Blindaje del Maestro de Trabajadores contra Reversiones Fantasma
Anteriormente, el contratista podía presionar el botón "Revertir" en el Maestro de Trabajadores (para un trabajador en estado de *Reserva*) e invalidar un finiquito incluso si este ya formaba parte de una nómina enviada a revisión.
- **Pre-Cálculo de Bloqueo**: El componente `GestionTrabajadoresContratista` ahora evalúa de antemano (`$vinculacionesBloqueadasReversion`) si el trabajador tiene alguna carpeta enviada (`ENVIADO`) que contenga el finiquito.
- **Ocultamiento Visual**: El botón de "Revertir" simplemente deja de renderizarse para los trabajadores bloqueados, reduciendo la fricción visual y previniendo clics inútiles.
- **Validación Estricta de Backend**: El método `revertirFiniquitoMaestro` incluye ahora una capa de seguridad que intercepta y rechaza la reversión si detecta una confirmación de finiquito en cualquier carpeta cerrada.

### 20.4. Unificación de Estados: ACTIVO vs. EN RESERVA
Se ha eliminado definitivamente el concepto de "Inactividad" mediante interruptores rápidos (Soft-Delete) para evitar quiebres de lógica en Acreditación y Verificación.
- **Badge Estático**: El badge de "Activo" en el Dashboard ya no es un botón. Esto previene que un usuario desactive a un trabajador globalmente sin pasar por el flujo de desvinculación.
- **Eliminación de Filtros Redundantes**: Se removió la opción "Sólo Inactivos" del filtro de estados, unificando la vista en trabajadores con contrato vivo (**Activo**) y trabajadores finiquitados (**En Reserva**).
- **Mando Centralizado**: La única forma de inactivar a un trabajador es a través del botón formal de **Desvincular** (Puerta Roja), garantizando que siempre se capture la fecha y el motivo oficial.
- **Eliminación Física/Total**: El botón para eliminar permanentemente una ficha (en caso de error de creación) se mantiene exclusivamente dentro del modal de edición del trabajador, separado de la gestión operativa diaria.

---

## 21. PROTOCOLO DE DECLARACIÓN JURADA (INN)

Para cumplir con los estándares de auditoría del INN, se ha formalizado el proceso de envío de periodos mediante una declaración jurada vinculante.

### 21.1. Modal de Confirmación "Zen"
- **Sustitución de Checkbox**: Se eliminó el checkbox simple de confirmación y se reemplazó por un modal de pantalla completa (estilo Zen) que aparece al hacer clic en "ENVIAR PERIODO".
- **Contenido Legal**: El modal despliega el texto legal obligatorio citando la Ley 20.123 y el DS 319/2006, responsabilizando al contratista por la veracidad de los datos.
- **Validación Forzada**: El botón de envío final dentro del modal solo se habilita tras marcar un checkbox de aceptación interna.

### 21.2. Blindaje de Backend
- El método `enviarPeriodo` verifica la propiedad `$declaracion_aceptada`. Si es falsa, el proceso se aborta con un mensaje de error, impidiendo envíos automatizados o maliciosos que salten la interfaz.

---

## 22. MÓDULO DE DESCARGA MASIVA DE CERTIFICADOS

Este módulo permite a los roles de gestión (Supervisor, Emisor, Admin) obtener respaldos masivos de certificados finales en formato PDF, optimizando los tiempos de respuesta ante auditorías de la Dirección del Trabajo o el Mandante.

### 22.1. Filtros Avanzados de Localización
Se ha dotado al módulo de una capacidad de filtrado quirúrgico:
1. **Jerarquía de Dependencia**: Selección por Lugar de Trabajo mostrando la ruta completa (`PADRE / HIJO / NIETO`).
2. **Empresa Contratista**: Filtrado por razón social específica.
3. **N° Contrato**: Filtrado por folio de contrato vinculado.

### 22.2. Generación Dinámica y Nomenclatura Estricta
- **Generación On-the-Fly**: El sistema no descarga archivos estáticos, sino que regenera el PDF del certificado en el momento de la descarga para asegurar que incluya todas las incidencias y resoluciones actualizadas.
- **Nomenclatura INN**: Los archivos dentro del ZIP siguen el formato:
  `[PRINCIPAL]_[LUGAR]_[CONTRATO]_[ID_REGISTRO]_[PERIODO].PDF`
- **Sanitización**: Los nombres se limpian de caracteres especiales, tildes y espacios para garantizar compatibilidad total con sistemas de archivos Windows y Unix.

---

### Módulo 22: Automatización de Checklist de Validación en Acreditación

#### Historia de Usuario (El QUÉ)
"Como **Validador de Acreditación**, quiero que el checklist de sub-criterios venga pre-marcado por defecto al abrir un documento para revisión, de modo que solo deba desmarcar los ítems faltantes (ej: EPPs ausentes), y que el sistema genere automáticamente el motivo de rechazo con los ítems que no estén marcados, para evitar errores manuales de digitación y asegurar coherencia absoluta entre el checklist y la observación final enviada al contratista."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Componente:** `Asem\RevisarDocumento` (`app/Livewire/Asem/RevisarDocumento.php`).
*   **Vista:** `resources/views/livewire/asem/revisar-documento.blade.php`.
*   **Lógica de Inicialización:** En `mount()`, se itera sobre `criteriosParaMostrar` y se inicializa `subCriteriosSeleccionados[$index][$sub['id']] = true` para cada sub-criterio, dejando todos marcados al cargar.
*   **Generación Dinámica de Rechazo:** El método `seleccionarDecision('Rechazado')` distingue dos casos:
    -   **Criterio CON sub-criterios:** Se recorre el array de sub-criterios. Si alguno está desmarcado, se construye el string: `[MOTIVO_BASE]: falta [SUB1], [SUB2]`. El criterio padre **no interviene** en esta decisión.
    -   **Criterio SIN sub-criterios:** Se rechaza si el checkbox del padre está desmarcado, usando `criteriosCumplidos[$index]`.
*   **Interfaz Zen:** Los criterios con sub-criterios reemplazan el checkbox interactivo del "padre" por un **indicador visual de solo lectura** (verde si todos marcados / rojo si falta alguno). El validador solo interactúa con los sub-criterios individuales para eliminar ambigüedad.
*   **Habilitación de Botones:**
    -   **"Aprobar"** -> Solo activo si todos los criterios y sub-criterios están marcados.
    -   **"Rechazar"** -> Solo activo si al menos un criterio o sub-criterio está desmarcado.
*   **Alcance:** Aplica a **todos** los criterios que tengan sub-criterios, independientemente del tipo de recurso (Trabajador, Vehículo, Maquinaria, Embarcación).

---

### Módulo 23: Aislamiento Multi-Tenant de Condiciones (Data Anarchy Fix)

#### Historia de Usuario (El QUÉ)
"Como **Administrador del Sistema**, quiero que las Condiciones (de Empresa y Personales) estén estrictamente asociadas a un **Mandante (Principal)** específico, de modo que al gestionar vinculaciones o crear Reglas Documentales, solo se desplieguen las condiciones autorizadas para ese mandante, eliminando la 'anarquía de datos' y previniendo la contaminación cruzada entre operaciones de distintos clientes."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Base de Datos:** Se agregó la clave foránea `mandante_id` (nullable) a las tablas `tipos_condicion` (Empresa) y `tipos_condicion_personal` (Persona).
*   **Restricciones de Integridad:** Se eliminó la restricción `UNIQUE(nombre)` global y se reemplazó por un índice compuesto `UNIQUE(mandante_id, nombre)`. Esto permite que dos mandantes distintos puedan tener una condición llamada "TRANSPORTE" sin generar conflicto.
*   **CRUDs de Administración:** 
    *   `GestionTiposCondicion` y `GestionTiposCondicionPersonal` ahora exigen la selección obligatoria de un Mandante al crear o editar. 
    *   La tabla principal de ambos módulos incluye una columna visible "Principal" para facilitar la auditoría.
*   **Módulo Reglas Documentales:** 
    *   Los selectores de "Condición Empresa" y "Condición Persona" inician vacíos. 
    *   Al cambiar el selector de "Principal", el sistema dinámicamente carga *solo* las condiciones asociadas a esa Principal.
    *   Al editar una regla existente, el sistema respeta el orden de carga para no perder las selecciones previas guardadas en la BD.
*   **Módulo Vinculación Contratista:** 
    *   En `GestionContratistas` (Empresa) y `GestionTrabajadoresContratista` (Persona), el modal de vinculación carga las condiciones disponibles basándose en el `mandante_id` de la operación actual, mediante métodos de aislamiento específicos (`_cargarCondicionesPorMandante` y `_cargarCondicionesPersonalesPorMandante`).
*   **Transición Segura:** Las condiciones históricas con `mandante_id = NULL` no se muestran en los combos operativos para forzar su re-asignación desde los mantenedores, asegurando datos 100% limpios a futuro.

---

### Módulo 24: Arquitectura Perseguidor / Por Vinculación (Eliminación de Anarquía Documental)

#### Historia de Usuario (El QUÉ)
"Como **Administrador del Sistema**, quiero que los documentos puedan configurarse como 'Perseguidores' (un solo documento aprobado cubre todas las vinculaciones del trabajador, ej: Cédula de Identidad) o 'Por Vinculación' (cada vinculación exige su propio documento independiente, ej: Contrato de Trabajo específico para un cargo), para evitar la anarquía de datos donde la aprobación de un contrato para el cargo de 'Soldador' marque incorrectamente como aprobado el requisito de contrato para el cargo de 'Operador' del mismo trabajador."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Base de Datos:** Se agregó la clave foránea `trabajador_vinculacion_id` (nullable) a la tabla `documentos_cargados`.
*   **Modelo:** `DocumentoCargado` actualizado con `$fillable` y relación `trabajadorVinculacion()`.
*   **Palanca de Control:** El flag `es_perseguidor` existente en `documento_configuraciones_criticidad` (Gestionable en Criticidad General) ahora dicta la lógica de validación y carga.
*   **Motor de Reglas (`DocumentoRequeridoService`):**
    *   Pre-carga de dos colecciones en memoria: una global (por entidad) y otra filtrada (por vinculación).
    *   Bifurcación dinámica: Si `es_perseguidor == false` y es Trabajador, el servicio evalúa la colección filtrada. Si no, usa la global.
    *   La consulta POR PERIODO también fue bifurcada para respetar el contexto de la vinculación cuando aplica.
*   **Interfaz de Carga (`ModalGestionDocumentosRecurso` y `CargaFlashContratista`):**
    *   **Stamping al crear:** Si el documento no es perseguidor, se guarda guardando el `trabajador_vinculacion_id` en curso.
    *   **Prevención de Colisiones:** Al buscar el documento activo a reemplazar/archivar, se filtra estrictamente por `trabajador_vinculacion_id` (o se exige `NULL` si es perseguidor), evitando que subir un contrato de "Soldador" archive accidentalmente el contrato de "Operador".
*   **Fallback Seguro:** Los documentos históricos que no poseen `trabajador_vinculacion_id` son tratados automáticamente como "Perseguidores", previniendo cualquier regresión o caída en el cumplimiento histórico.

---

### HISTORIAL DE VERSIONES RECIENTES

#### V8.42 (2026-04-27)
- **Mejora Crítica: Aislamiento Multi-Tenant de Condiciones**
  - Fin de la "anarquía de datos": Las Condiciones de Empresa (`tipos_condicion`) y Personales (`tipos_condicion_personal`) ahora están vinculadas estrictamente a un Mandante.
  - Refactorización de Modales de Vinculación (Contratistas y Trabajadores) para listar solo las condiciones del Mandante en curso.
  - Actualización de Reglas Documentales: Selectores dinámicos de condición que reaccionan al cambiar la Principal.
  - Índices únicos compuestos por `(mandante_id, nombre)` permitiendo nombres de condición repetidos entre distintas Principales.

#### V8.41 (2026-04-27)
- **Nuevo: Automatización de Checklist de Validación (Acreditación)**
  - Checklist de sub-criterios (EPPs, Herramientas, etc.) pre-marcado por defecto al abrir un documento.
  - Generación automática de glosa de rechazo: `[Motivo del criterio]: falta [Ítem desmarcado 1], [Ítem desmarcado 2]`.
  - Refactorización de la lógica `seleccionarDecision()` separando criterios simples (checkbox padre) de criterios con sub-criterios (solo hijos interactivos).
  - Indicador visual de solo lectura en criterios padre (Aprobado/Rechazado) en lugar de checkbox interactivo, eliminando ambigüedad de estado.

#### V8.40 (2026-04-23)
- **Nuevo: Declaración Jurada INN (Zen)**
  - Implementación de un **Modal de Declaración de Veracidad** obligatorio antes de enviar cualquier periodo (Estándar y Legacy).
  - Sustituye los checkboxes de confirmación simples por una declaración formal con base legal (Ley 20.123, DS 319/2006).
- **Nuevo: Descarga Masiva de Certificados**
  - Módulo de descarga extendido para permitir la obtención de certificados oficiales emitidos.
  - Generación de PDFs al vuelo con agrupación de contingencias retenibles y no retenibles.
  - **Filtros Avanzados:** Búsqueda granular por Lugar de Trabajo (Dependencia), Contratista y N° Contrato.
  - **Nomenclatura INN:** Nombres de archivo estandarizados: `PRINCIPAL_LUGAR_CONTRATO_IDREGISTRO_PERIODO.PDF`.
- **Mejora: Jerarquía en Filtros de Ubicación**
  - Actualización del atributo `nombre_jerarquico` en el modelo `Dependencia` para mostrar la ruta completa `PADRE / HIJO / NIETO`.
- **Corrección: Estabilidad en Descarga Masiva**
  - Filtrado de vinculaciones huérfanas sin contratista para evitar errores de propiedad nula (`Attempt to read property id on null`).

---

#### V8.41 (2026-04-28)
- **Nuevo: Arquitectura Perseguidor / Por Vinculación (Eliminación de Anarquía Documental)**
  - Resolución del problema crítico donde un documento aprobado para una vinculación de un trabajador (ej: cargo SOLDADOR) contaminaba las demás vinculaciones del mismo trabajador (ej: cargo OPERADOR).
  - **Nuevo Campo BD:** `documentos_cargados.trabajador_vinculacion_id` (nullable FK). Registra la vinculación específica para docs no-perseguidores.
  - **Palanca de Control:** Flag `es_perseguidor` del módulo Criticidad General (`/gestion/criticidad-general`):
    - `es_perseguidor = true` Un doc compartido entre TODAS las vinculaciones (ej: Cédula, Cert. Médico). Comportamiento sin cambio.
    - `es_perseguidor = false` Cada vinculación requiere su propio doc independiente (ej: Contrato de Trabajo cargo-específico).
  - **Archivos Modificados:**
    - `database/migrations/2026_04_28_190000_add_vinculacion_id_to_documentos_cargados.php` [NUEVO]
    - `app/Models/DocumentoCargado.php`: campo en fillable + relación trabajadorVinculacion().
    - `app/Services/DocumentoRequeridoService.php`: Bifurcación de colección de docs y query POR PERIODO según es_perseguidor.
    - `app/Livewire/Contratista/ModalGestionDocumentosRecurso.php`: Stamping de trabajador_vinculacion_id al crear. Búsqueda de doc activo filtrada por vinculación.
  - **Fallback Seguro:** Datos históricos sin trabajador_vinculacion_id se tratan como perseguidores. Sin riesgo de regresión.
  - **Alcance:** Solo aplica a Trabajadores. Vehículos, Maquinarias, Embarcaciones y Empresas NO se ven afectados.

#### V8.44 (2026-04-29)
- **Nuevo: Carga Masiva de Contratistas con Jerarquía (Upsert)**
  - Implementación del motor de importación jerárquica que soporta hasta 4 niveles de profundidad.
  - **Lógica Anti-Anarquía**: El sistema ahora busca vinculaciones existentes por Mandante para evitar la creación de filas duplicadas en la tabla pivot `contratista_unidad_organizacional`.
  - **Mejora UX**: Exportación recursiva de árboles de contratistas por Mandante para facilitar auditorías y actualizaciones masivas.
  - **Blindaje de Datos**: 
    - Manejo de tipos opcionales (`?string`) en los modales de gestión para prevenir TypeErrors con administradores antiguos o incompletos.
    - Los datos de usuario administrador no se sobrescriben si vienen vacíos en el Excel, protegiendo la información ya existente.
  - **Campos Nuevos**: Soporte para `Número de Contrato Inicial` en plantillas de exportación e importación.

#### V8.43 (2026-04-28)
- **Mejora Crítica: Estandarización Maestro de Solicitudes Complementarias**
  - **Bloqueo Escrito en Piedra:** Las solicitudes en estado `EMITIDO` ya no permiten reasignación/remoción de auditores por parte del Supervisor, ni edición/re-envío por parte del Auditor. Candados estrictos implementados en UI y Backend.
  - **Paridad de Modales:** El Supervisor y Emisor ahora visualizan el detalle del complementario exactamente con la misma interfaz e información que el Auditor.
*   **Terminología Unificada:** Se estandarizaron los términos a `SOLUCION TOTAL`, `SOLUCIÓN PARCIAL`, `NO SOLUCIONADO` y `ESTADO SOLUCION` en todas las vistas, eliminando etiquetas ambiguas.
*   **Trazabilidad de Saldos para el Contratista:** 
    *   En `VerificacionLegacy.php`, se actualizó la lógica para reconocer el estado `EMITIDO` como un ciclo cerrado.
    *   En `verificacion-legacy.blade.php`, se modificó la vista para mostrar el *saldo real pendiente* (`monto`) tras una solución parcial, conservando el monto original como referencia secundaria e incluyendo una etiqueta histórica (ej: "SOLUCIÓN PARCIAL") al lado de cada código, informando el motivo del saldo.
*   **Blindaje "Escrito en Piedra" (Estados Cerrados):**
    *   **Supervisor:** Si la SC está en estado `EMITIDO`, se oculta el botón para remover o reasignar auditor, mostrando únicamente "SIN AUDITOR" o el nombre del auditor que finalizó el trabajo. Validación backend agregada en `AsignacionComplementaria.php`.
    *   **Auditor:** Al abrir una SC en estado `EMITIDO`, el sistema fuerza el modo `$esSoloLectura`. Todos los inputs se bloquean, el botón de "FINALIZAR Y ENVIAR CIERRE" desaparece, y el botón de la tabla cambia de "Revisar" a "Ver". Se implementaron early-returns en el backend (`GestionComplementarios.php`) para interceptar intentos de modificación forzada.
    *   **Analista:** Si el periodo de pre-cierre se encuentra finalizado (estados `REVISADO`, `PARA_EMITIR` o `EMITIDO`), se fuerza el modo `$esSoloLectura`. Todos los campos numéricos de la nómina y los selects de estado de los trabajadores se bloquean. Se ocultan los botones de Guardado/Finalización, consolidando la inmutabilidad de la información revisada. Se implementaron validaciones backend en `MisAsignaciones.php` para bloquear ediciones externas.

---

### Módulo 26: Carga Masiva de Contratistas con Jerarquía (Upsert)

#### Historia de Usuario (El QUÉ)
'Como **Administrador ASEM**, quiero poder cargar y actualizar masivamente empresas contratistas manteniendo su jerarquía (Principal/Sub/Sub-Sub), vinculándolas automáticamente a mandantes, unidades organizacionales y lugares de trabajo específicos, para mantener el árbol de contratistas sincronizado con la realidad operativa sin necesidad de ingreso manual uno por uno.'

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Componente**: ImportarContratistas (Livewire) + ContratistasImport (Excel).
*   **Modo Upsert (Inteligente)**: 
    *   Basado en el RUT de la empresa. Si el contratista existe, actualiza sus datos; si no, lo crea.
    *   **Protección de Administradores**: Si un contratista ya tiene un administrador asignado, el sistema solo actualiza los campos (Nombre, RUT, Email) que vengan **con información** en el Excel, preservando los datos existentes si las celdas están vacías.
*   **Gestión de Jerarquía**: 
    *   Soporte para niveles 0 (Principal) hasta 3 (Sub-Sub-Sub).
    *   Uso de RUT Contratista Padre para establecer vinculaciones automáticas vía SolicitudVinculacion (estado APROBADA por defecto).
*   **Exportación Recursiva (Paso 0)**: 
    *   Funcionalidad para descargar la plantilla pre-llenada filtrando por un Mandante y, opcionalmente, un Contratista Principal específico.
    *   La Exportación recorre recursivamente toda la descendencia, permitiendo obtener el árbol completo para ediciones masivas.
*   **Vinculación Operativa (Pivot CUO)**:
    *   Actualización/Creación automática de la vinculación en la tabla contratista_unidad_organizacional.
    *   **Optionalidad Zen**: Los campos UO y Lugar de Trabajo son opcionales. Si se omiten, el sistema permite la vinculación a nivel de Mandante.
    *   **ID_REGISTRO**: Generación automática de folios correlativos (comenzando en 40000) si el campo viene vacío, asegurando unicidad dentro del mandante.
*   **Campos Extendidos**: Inclusión del campo Número de Contrato Inicial para establecer la referencia contractual desde la carga.

---

### Módulo 27: Escudo Forense de Seguridad "Zero-Trust" (Hardening Operativo)

#### Historia de Usuario (El QUÉ)
"Como **Administrador de Seguridad (OvalControl)**, quiero implementar un escudo de vigilancia forense que detecte y registre en tiempo real cualquier intento de sabotaje, exfiltración de datos sensibles o escalamiento de privilegios, asegurando que cada interacción crítica con el sistema (especialmente en la Gestión de documentos y exportaciones) deje un rastro de verdad inmutable para análisis forenses posteriores y auditorías de cumplimiento."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Vigilancia de Sabotaje (Mass Deletion):**
    *   Implementación de `SecurityMassDeletionObserver` en modelos críticos (`Contratista`, `Trabajador`, `Vehículo`, `Vinculación`).
    *   Detección automática de borrados de alto volumen que disparan alertas de seguridad inmediatas (`seguridad-alerta`) en el registro de actividad.
*   **Protección contra Exfiltración (Data Exfiltration):**
    *   Instrumentación de todos los endpoints de exportación (Excel/PDF) en los paneles de Informes, Reporte de Dotación y Auditoría de Actividad.
    *   Registro del "Quién, Qué y Cuándo" para cada reporte descargado, permitiendo reconstruir el historial de acceso a datos financieros y operativos sensibles.
*   **Monitoreo de Privilegios (Privilege Escalation):**
    *   Integración de listeners para eventos de Spatie Roles (`RoleAssigned`, `RoleRemoved`).
    *   Auditoría automática de cambios en permisos y roles de usuarios para detectar elevaciones de privilegios no autorizadas o manipulaciones del esquema de seguridad.
*   **Hardening de Autenticación (Brute-Force Detection):**
    *   Listener de `FailedLogin` para rastrear patrones de IP y User-Agent en intentos fallidos, permitiendo identificar ataques de fuerza bruta dirigidos.
*   **Visibilidad Forense en Ingestión:**
    *   Refactorización de `AuditService` para capturar contexto detallado del recurso (Trabajador, Vehículo o Empresa).
    *   Registro de metadatos de archivos (Nombre original, Tipo de Documento, Recurso asociado) en cada carga individual, masiva (Flash) y de verificación (Legacy).
    *   Detalle de motivos de rechazo y devoluciones directamente en el rastro forense.
*   **Validación Segura de Archivos (Malware Shield):**
    *   Estandarización del trait `ValidatesFileUpload` con validaciones de "Anti-Camuflaje" para detectar extensiones maliciosas y archivos no permitidos antes de su procesamiento.

---

### Módulo 28: Control de Acceso Terreno (OVAL Control)

#### Historia de Usuario (El QUÉ)
"Como **Personal de Seguridad o Guardia (OVAL_Admin)**, quiero un buscador ultra-rápido de RUT y Patente que sea fácil de operar desde un teléfono móvil, para verificar instantáneamente si un trabajador o vehículo tiene acceso habilitado a mi lugar de trabajo específico, conociendo los motivos exactos (documentos vencidos o pendientes) si el acceso es denegado."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Interfaz Mobile-First:** Diseño responsivo (Tailwind) optimizado para pantallas táctiles y lectores de códigos QR/Barras, con botones y campos de entrada de gran tamaño (Patrón Zen).
*   **Buscador Inteligente (Agente de Identidad):** Lógica unificada que detecta automáticamente si el término ingresado es un RUT o una Patente, limpiando caracteres especiales (puntos, guiones, espacios) para evitar errores de digitación.
*   **Contextualización de Acceso:** Selectores de Mandante y Dependencia (Lugar de Trabajo) que filtran las vinculaciones activas, permitiendo un control estricto por ubicación geográfica/operativa.
*   **Integración con Reglas Documentales:** Uso del `DocumentoRequeridoService` para evaluar en tiempo real la criticidad y el estado de los documentos requeridos para la vinculación específica del recurso.
*   **Visibilidad de Restricciones:** Desglose visual detallado de los documentos críticos que están bloqueando el acceso (Vencidos, Rechazados o No Cargados), eliminando la ambigüedad en portería.

---

### Módulo 29: Importación de Periodos Históricos (Excel + PDF)

#### Historia de Usuario (El QUÉ)
"Como **Administrador ASEM**, necesito cargar masivamente la historia de verificaciones de una empresa (años de periodos cerrados) subiendo tanto el archivo de nómina (Excel) como los respaldos físicos (PDFs), para que el sistema refleje el cumplimiento histórico sin tener que cargar mes a mes manualmente."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Repositorio de PDFs:** Implementación de un "Bunker" temporal de archivos donde se suben masivamente los documentos.
*   **Algoritmo de Vinculación:** El sistema procesa el nombre del archivo bajo la convención `{PRINCIPAL}_{ID_REGISTRO}_{AAAAMM}_{LUGAR}_{NUM_CONTRATO}_{COD_DOCUMENTO}.pdf` para asociarlo automáticamente al trabajador y periodo correcto.
*   **Auto-Inyección de Carpetas:** Si el periodo importado no existe, el sistema crea la `CarpetaVerificacion` con estado `AUDITADO` y emite un certificado histórico virtual.

---

### Módulo 30: Gestión de Incidencias del Auditor (Cajón de Codificación)

#### Historia de Usuario (El QUÉ)
"Como **Auditor de Verificación**, quiero poder pegar una cadena de códigos de incidencia (ej. '100.01; 104.05') en un cajón de texto único, para que el sistema cargue automáticamente todas las observaciones y retenciones financieras correspondientes, evitando tener que buscarlas una por una en un listado largo."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Parser de Codificación:** Lógica en el backend que separa la cadena por puntos y comas, busca el código en la tabla `incidencias_tipo` y genera el registro en `carpetas_verificacion_incidencias`.
*   **Integración Financiera:** Si el código pegado corresponde a una incidencia de "Fondo" (Retenible), el sistema actualiza automáticamente el monto de retención del periodo.

---

### Módulo 31: Gestión Centralizada de Popups [2026-05-05]

#### Historia de Usuario (El QUÉ)
"Como **OVAL_Admin**, quiero gestionar todos los anuncios emergentes del sistema desde un único panel, pudiendo elegir si un mensaje es global o está dirigido a los contratistas de una empresa principal específica, para mantener una comunicación segmentada y eficiente."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Multi-Tenancy Scoping:** Inclusión del campo `mandante_id` en el modelo `Popup`.
*   **Control Centralizado:** El módulo `GestionPopups` ahora reside bajo el namespace de administración general, permitiendo a `OVAL_Admin` y `ASEM_Admin` filtrar y auditar mensajes por "Principal".
*   **Lógica de Visibilidad:** El método `esVisiblePara()` valida si el usuario pertenece al mandante asignado o si el mensaje es de carácter global (`mandante_id = NULL`).

---

### Módulo 32: Política de Cookies Premium [2026-05-05]

#### Historia de Usuario (El QUÉ)
"Como **Usuario del Sistema**, quiero ver un aviso de cookies elegante y no intrusivo al ingresar, que recuerde mi preferencia por dispositivo para no interrumpir mi flujo de trabajo en cada inicio de sesión."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Estética Premium:** Banner inferior con efecto Glassmorphism (blur de fondo) y tipografía Outfit para alineación con el diseño "Nivel Dios".
*   **Persistencia Local:** Uso de `localStorage` (`oval_cookies_accepted`) para manejar el estado del consentimiento sin recargar el servidor ni generar tráfico de base de datos innecesario.
*   **Inyección Global:** Integrado vía `@include` en los layouts `app`, `guest` y `guest-wide`.

---

### Módulo 33: Escudo de Acreditación y Validaciones de Cumplimiento (Zero-Trust) [2026-05-07]

#### Historia de Usuario (El QUÉ)
"Como **Contratista**, el sistema debe restringir mi acceso a las funciones de cumplimiento documental si mi acreditación no está vigente (bloqueando la carga de documentos y ocultando datos sensibles) y debe impedirme iniciar nuevos periodos si no tengo trabajadores en la nómina para ese mes específico, asegurando que solo opere cuando cumplo con los requisitos contractuales."

#### Ficha Técnica / Blueprint (El CÓMO)
*   **Guardia de Acreditación (Frontend & Backend):** 
    *   Implementación de validación estricta en el `LoginForm` contra el flag `is_active` del contratista, mostrando mensajes de alerta en blanco sobre rojo oscuro para alta legibilidad.
    *   La visibilidad del componente de trabajadores se rige por la propiedad `$sinAcreditacion`, ocultando condicionalmente las columnas de "% Cumplimiento", "Acceso", botones de carga y la pestaña "Carga Flash".
    *   El cálculo de `$cuoAcredita` se recalcula dinámicamente al cambiar el lugar de trabajo en `PanelOperacion`.
    *   Bloqueo preventivo en `ControlAcceso` al buscar trabajadores si no hay acreditación vigente.
*   **Guardia #4 de Nómina (Temporalidad por Mes):**
    *   En `VerificacionLegacy` y `VerificacionLegacyCarga`, se reemplazó la validación estática de trabajadores por una **validación temporal por mes**.
    *   Se verifica que hayan existido trabajadores activos (`fecha_ingreso_vinculacion <= fin_de_mes` y `fecha_desactivacion` >= `inicio_de_mes` o nula) vinculados específicamente a la **misma Unidad Organizacional y Dependencia** de la nómina.
    *   Si no había trabajadores en el mes a iniciar, se bloquea la creación del periodo mostrando tarjetas ámbar con el mensaje: *"Sin trabajadores vinculados, no puede iniciar periodo"*, deteniendo la creación de carpetas vacías (`firstOrCreate`).
    *   Los periodos históricos ya creados conservan su accesibilidad y estado original de revisión independientemente de las validaciones de nómina actual.

---

### Módulo 34: Sincronización de Documentos desde Sistema Obsoleto [2026-05-12]

#### Historia de Usuario (El QUÉ)

> "Como **Administrador ASEM**, necesito migrar los documentos validados del sistema legado al nuevo sistema antes de su apagado definitivo, de forma que los documentos históricamente aprobados y vigentes reemplacen automáticamente cualquier versión inferior existente, garantizando que el nuevo sistema quede como fuente de verdad sin duplicados activos ni registros contaminados."

#### Historia de Usuario Extendida (Flujos Específicos)

**HU-34.1 – Migración de documento aprobado y vigente (caso nominal)**
> "Como **Administrador ASEM**, cuando importo un documento del sistema viejo marcado como **Aprobado + Vigente**, el sistema debe archivarse la versión anterior (si existía) y dejar el doc importado como el registro activo en estado `Revisado`, con trazabilidad forense completa mediante el campo `reemplaza_a_id`."

**HU-34.2 – Protección del doc vigente frente a un doc de menor calidad**
> "Como **Administrador ASEM**, cuando importo un documento del sistema viejo que está **Rechazado o Vencido** y en el sistema actual ya existe un documento **Aprobado + Vigente** para la misma entidad y regla, el sistema debe **descartar el entrante** (archivarlo por trazabilidad) y dejar intacto el doc vigente en el sistema."

**HU-34.3 – Recuperación cuando no hay doc bueno en el sistema actual**
> "Como **Administrador ASEM**, cuando importo un documento que viene **Rechazado o Vencido** del sistema viejo pero en el sistema actual no existe ningún doc activo (o el existente también es rechazado/vencido), el doc importado debe **ganar** y quedar como el mejor disponible en estado `Revisado`."

**HU-34.4 – Trazabilidad completa del proceso**
> "Como **Administrador ASEM**, al finalizar la sincronización quiero ver un panel de resultados con **4 contadores** explícitos: documentos que quedaron Activos, documentos viejos Archivados, documentos entrantes Descartados y filas con Errores, para verificar la integridad de la migración antes del apagado del sistema obsoleto."

#### Ficha Técnica / Blueprint (El CÓMO)

##### Arquitectura del Módulo

| Componente | Archivo | Rol |
|---|---|---|
| Livewire Component | `app/Livewire/SincronizarDocumentos.php` | Controlador UI + orquestación |
| Import Class | `app/Imports/SincronizacionDocumentosImport.php` | Motor de decisión + persistencia |
| Template Export | `app/Exports/SincronizacionTemplateExport.php` | Generador de plantilla Excel |
| Template Sheet | `app/Exports/Sheets/SincronizacionTemplateSheet.php` | Hoja de datos + dropdowns |
| Listados Sheet | `app/Exports/Sheets/SincronizacionListadosSheet.php` | Hoja oculta de validación |
| Upload Controller | `app/Http/Controllers/SincronizacionFisicosController.php` | Endpoint Dropzone (PDFs) |
| Blade View | `resources/views/livewire/sincronizar-documentos.blade.php` | UI con 4 contadores |

##### Ruta y Acceso

*   **URL:** `/gestion/sincronizar/documentos`
*   **Nombre de ruta:** `gestion.sincronizar.documentos`
*   **Ruta física upload:** `POST /gestion/sincronizar/documentos/fisicos`
*   **Roles autorizados:** `ASEM_Admin | OVAL_Admin` (mismo guardia que demás importadores)
*   **Navegación:** Sidebar → CONFIGURACION → CARGAS MASIVAS → **SINCRONIZAR DOCS (LEGADO)**

##### Carpeta de Archivos Físicos

```
storage/app/public/importar_documentos_sincronizacion/
```
> ⚠ **Completamente separada** de la carpeta del importador masivo (`importar_documentos_fisicos/`). Esto evita que PDFs de distintos flujos se mezclen accidentalmente.

##### Plantilla Excel de Sincronización

| Columna | Clave interna | Obligatoria | Descripción |
|---|---|---|---|
| Mandante* | `mandante` | ✅ | Dropdown desde hoja Listados |
| Contratista* | `contratista` | ✅ | ID_REGISTRO o razón social |
| Tipo de Entidad* | `tipo_de_entidad` | ✅ | `App\Models\Trabajador`, etc. |
| ID/RUT/Patente Entidad* | `idrutpatente_entidad` | ✅ | Identificador único de la entidad |
| Regla Documental* | `regla_documental` | ✅ | Dropdown desde hoja Listados |
| Nombre Documento (Snapshot)* | `nombre_documento_snapshot` | ✅ | Nombre descriptivo del documento |
| Nombre Archivo Fisico* | `nombre_archivo_fisico` | ✅ | Nombre exacto del PDF en carpeta sync |
| Resultado Validacion Origen* | `resultado_validacion_origen` | ✅ | `Aprobado` o `Rechazado` (del sistema viejo) |
| Fecha Vencimiento* | `fecha_vencimiento` | ✅ | Afecta lógica de calidad |
| Unidad Organizacional | `unidad_organizacional` | — | Para resolución de `trabajador_vinculacion_id` |
| Fecha Emision | `fecha_emision` | — | Histórica, informativa |
| Periodo | `periodo` | — | Mes/año del documento (AAAAMM) |
| ID Validador ASEM | `id_validador_asem` | — | Defaults al usuario de migración (ID 54) |
| Observacion Validador | `observacion_validador` | — | Texto libre |

> 📌 **Sin fila de ejemplo**: La plantilla NO incluye fila de ejemplo para evitar que se importe accidentalmente. Los comentarios en los headers de Excel orientan al operador.

##### Motor de Decisión (Matriz de Calidad)

```
esDocumentoBueno(resultado, fecha_vencimiento):
  → Aprobado  AND  fecha_vencimiento >= HOY  →  BUENO
  → Rechazado  OR  fecha_vencimiento < HOY   →  MALO
  → Sin fecha_vencimiento + Aprobado          →  BUENO (vigencia indefinida)
```

| Entrante | Existente en sistema | Decisión |
|---|---|---|
| **BUENO** (Aprobado + Vigente) | Cualquier estado | ✅ Entrante GANA → existente pasa a `Archivado` |
| **MALO** (Rechazado o Vencido) | **BUENO** | ⏭ Existente GANA → entrante entra como `Archivado` |
| **MALO** | **MALO** o sin doc | ✅ Entrante GANA (lo mejor disponible) |
| **BUENO** | Sin doc previo | ✅ Entrante GANA (entra directo como `Revisado`) |

##### Persistencia y Trazabilidad

*   **Estado ganador:** `estado_validacion = 'Revisado'` + `resultado_validacion = [resultado_origen]`
*   **Estado perdedor (entrante descartado):** `estado_validacion = 'Archivado'`  
*   **Estado doc viejo archivado:** `estado_validacion = 'Archivado'`
*   **Campo forense:** `reemplaza_a_id` → apunta al ID del doc que fue archivado por el entrante
*   **`trabajador_vinculacion_id`:** Se resuelve igual que el importador masivo (vinculación activa por UO)
*   **`tipo_vencimiento_snapshot`:** Fijado como `'SINCRONIZACION'` para identificar el origen del registro
*   **`observacion_documento_snapshot`:** `'SINCRONIZACION DESDE SISTEMA OBSOLETO'`

##### Normalización de Claves Excel

El importador aplica limpieza de asteriscos residuales en claves generadas por Maatwebsite para garantizar robustez ante cambios menores en los headers:

```php
$cleanKey = str_replace('*', '', $k);
$cleanKey = preg_replace('/\s+/', '_', trim($cleanKey));
```

##### Panel de Resultados (UI)

```
┌──────────────────────┬──────────────────────┐
│  ✅ QUEDARON ACTIVOS │ 📦 VIEJOS ARCHIVADOS │
│        N             │         N            │
├──────────────────────┼──────────────────────┤
│ ⏭ ENTRANTES DESC.   │  ❌ ERRORES DE FILA  │
│        N             │         N            │
└──────────────────────┴──────────────────────┘
```

*   **Quedaron Activos:** Docs que ganaron la decisión y quedaron en `Revisado`.
*   **Viejos Archivados:** Docs del sistema actual que fueron superados por el entrante.
*   **Entrantes Descartados:** Docs del sistema viejo que perdieron ante un doc vigente (se guardan archivados para trazabilidad histórica).
*   **Errores de Fila:** Filas que no pudieron procesarse (entidad no encontrada, PDF faltante, etc.). Se muestran con detalle de fila y motivo.

##### Archivos Modificados/Creados

*   `routes/web.php` → 2 rutas nuevas bajo prefijo `gestion`
*   `resources/views/livewire/layout/navigation.blade.php` → Link en dropdown CARGAS MASIVAS + ruta en `$rutasDeCargasMasivas`

#### Notas de Operación

> ⚠ **Flujo de uso recomendado:**
> 1. Subir los PDFs al repositorio de sincronización mediante el Dropzone integrado.
> 2. Descargar la plantilla filtrada por Principal.
> 3. Completar el Excel con los metadatos del sistema obsoleto.
> 4. Subir el Excel y revisar los 4 contadores de resultado.
> 5. Vaciar el repositorio temporal una vez confirmada la migración.

> 🔒 **Decisión de diseño:** Este módulo es completamente independiente de `ImportarDocumentos`. No se modificó ningún archivo del importador masivo original para garantizar cero regresión en flujos productivos.

---

### Módulo 35: Acreditación IA Multimodal (Visual Grounding) [2026-05-13]

#### Historia de Usuario (El QUÉ)
"Como **Administrador/Auditor**, necesito que la Inteligencia Artificial no solo extraiga texto de los documentos, sino que tenga la capacidad de comparar visualmente la estructura y diseño del documento subido contra un 'Formato de Muestra' oficial (ej. formato de liquidación, diseño de credencial, etc.), y me responda si cumple o no con ese formato corporativo. Además, quiero poder elegir dinámicamente entre distintos modelos de IA (Gemini, Claude, Llama, Qwen) según las necesidades de rendimiento y costo."

#### Ficha Técnica / Blueprint (El CÓMO)

*   **Arquitectura Base:**
    *   Integración con la API de **OpenRouter**, permitiendo intercambiar el modelo fundacional en caliente sin tocar el backend.
    *   Soporte nativo para arreglos de imágenes (Multimodalidad).
*   **Modelo de Datos (`IaCampoConfiguracion`):**
    *   Inclusión de `formato_muestra_id` (migración `add_formato_muestra_id_to_ia_campos_configuracion_table`) como llave foránea hacia `formatos_documento_muestra`.
*   **Controlador UI (`RevisionIaAcreditacion`):**
    *   Nuevo selector de Modelos IA (Gemini 2.5 Flash, Claude 3 Haiku, Llama 3.2 Vision, Qwen 2.5 VL, etc.).
    *   Nuevo selector de "Formato de Muestra" en la configuración de cada criterio (Cajón de texto).
    *   Mejoras UI: Se eliminaron los cortes abruptos (`truncate`) en la "Vista Águila" usando `break-words` y se remarcaron fuertemente los bordes de la tabla (`border-gray-400`) para máxima legibilidad. Las tipografías de Contratista y Entidad fueron aumentadas.
*   **Servicio Core (`IaExtraccionService`):**
    *   El método `procesarDocumento` ahora detecta si el criterio tiene un formato asociado, va al disco (`Storage::disk('public')`), convierte la imagen de muestra en Base64, y la adjunta como un nodo secundario de tipo `file` en el payload JSON que se envía a OpenRouter.
    *   El prompt general incluye una directriz estricta ("Visual Grounding") advirtiendo a la IA que cualquier imagen secundaria adjunta es únicamente un patrón visual de referencia, blindando al modelo contra alucinaciones (evitando que lea los datos del trabajador de la plantilla de muestra).
*   **Conclusión Técnica:** La familia **Gemini 2.5 Flash** probó ser superior en atención cruzada multimodal en comparación con LLMs Open Source, logrando separar con éxito el contexto del documento a analizar.

---

### Módulo 36: Auditor IA Booleano y Contexto de Entidad [2026-05-13]

#### Historia de Usuario (El QUÉ)
"Como **Auditor de Terreno**, necesito que la Inteligencia Artificial deje de ser una caja negra. Quiero que la IA evalúe las reglas con un SÍ o NO definitivo, pero que me muestre explícitamente en una columna separada el fragmento de texto exacto que leyó para tomar esa decisión. Además, la IA debe saber exactamente de quién es el documento (Trabajador, Empresa o Vehículo) para que cuando yo le pida 'Compara el RUT', la IA sepa con qué RUT oficial debe cruzar la información sin que yo tenga que escribirlo manualmente en la configuración."

#### Ficha Técnica / Blueprint (El CÓMO)

*   **Arquitectura Dual de Criterios (Evidencia vs Veredicto):**
    *   Se refactorizó la estructura del JSON esperado en `IaExtraccionService`. Ahora, para todo campo catalogado como "Criterio", el motor genera dos claves dinámicas obligatorias:
        *   `{campo}_extraido`: Exige a la IA extraer la evidencia textual pura.
        *   `{campo}_cumple`: Exige a la IA evaluar la evidencia contra la regla de negocio y responder de manera estrictamente booleana (`SI` o `NO`).
    *   La UI (`revision-ia-acreditacion.blade.php`) fue adaptada para pintar tres columnas semánticas (Campo, Extraído, Cumple IA), filtrando inteligentemente respuestas ambiguas.
*   **Inyección Dinámica de Contexto de Entidad:**
    *   Se implementó un bloque de interpolación de variables en `IaExtraccionService::construirPrompt()`.
    *   Antes de evaluar el documento, el motor inyecta "en secreto" los **Datos de Referencia del Sistema** al prompt de la IA.
    *   Para **Trabajadores**, inyecta: `Nombres`, `RUT`, `Cargo` (resolviendo la vinculación activa), `Nacionalidad` y `Tipo de Permanencia`.
    *   Para **Vehículos**, inyecta: `Patente`, `Marca` y `Modelo`.
    *   Para **Empresas**, inyecta: `Razón Social` y `RUT`.
*   **Gestión del Flujo Humano-IA:**
    *   Se eliminó la dependencia matemática antigua (`Identidad Fuzzy`) para criterios, delegando la responsabilidad cognitiva a la IA, pero manteniendo el paso manual ("Aceptar Análisis IA") para garantizar la supervisión humana.
    *   El ciclo de vida del documento (`reemplaza_a_id` y archivado automático) sigue anclado estrictamente a la **confirmación manual humana**, evitando que una alucinación de la IA archive documentos válidos prematuramente.
*   **Conclusión de Diseño:** Este patrón resuelve el "Problema de la Caja Negra" inherente a los LLMs, obligando al modelo fundacional a justificar sus evaluaciones lógicas con extracciones explícitas, mientras la inyección de contexto le da a la IA "los ojos del sistema" para realizar verificaciones cruzadas sin configuraciones estáticas engorrosas.
