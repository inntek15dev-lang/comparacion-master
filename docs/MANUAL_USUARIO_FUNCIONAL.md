# Manual Funcional y Reglas de Negocio - Master Documentación Controlada

## 1. Introducción
El sistema **Master Documentación Controlada** es una plataforma diseñada para asegurar que todas las empresas contratistas, sus trabajadores y equipos (vehículos, maquinaria) cumplan con los estándares documentales exigidos por la empresa principal (**Mandante**).

El objetivo es minimizar riesgos operacionales y legales, garantizando que "quien entra a faena, está habilitado para hacerlo".

## 2. Actores del Sistema

### 🏢 Mandante (Empresa Principal)
Es el dueño de la obra o faena.
- **Rol**: Define las "Reglas del Juego". Decide qué documentos son obligatorios para cada tipo de trabajo.
- **Responsabilidad**: Puede actuar como validador final de documentos críticos.

### 👷 Contratista (Empresa Externa)
Es la empresa que presta servicios al Mandante.
- **Rol**: Debe mantener al día la documentación de su empresa, sus trabajadores y sus equipos.
- **Responsabilidad**: Cargar documentos a tiempo y subsanar rechazos.

### 🛡️ ASEM (Administrador/Auditor)
Es el equipo que opera la plataforma y realiza la primera línea de validación.
- **Rol**: Revisa que los documentos cargados sean legibles, correctos y coincidan con lo solicitado.

## 3. Conceptos Clave

### 📄 Regla Documental (El Requisito)
Es la instrucción que dice: *"Para entrar a la faena X, el rol Y necesita el documento Z"*.
Ejemplo: *"Todo Conductor (Rol) necesita Licencia de Conducir (Documento) vigente"*.

### 📂 Documento Cargado (La Evidencia)
Es el archivo digital (PDF, Foto) que sube el contratista para demostrar que cumple la regla.

### 🚦 Semáforo de Estado
El sistema clasifica cada documento en estados visuales:
- **🟢 Vigente**: Aprobado y dentro de fecha.
- **🟡 Por Vencer**: Aprobado, pero próximo a expirar (según configuración de días de aviso).
- **🔴 Vencido**: La fecha de vigencia expiró.
- **🔵 Pendiente**: Subido, esperando revisión de ASEM o Mandante.
- **⚫ Rechazado**: Revisado y no aprobado (debe volver a subirse).

## 4. Flujos de Trabajo (Reglas de Negocio)

### 4.1. Ciclo de Vida de un Documento
1. **Solicitud**: El sistema detecta automáticamente qué documentos le faltan a un trabajador/vehículo basándose en su cargo o tipo.
2. **Carga**: El contratista sube el archivo digital.
3. **Validación ASEM**: Un auditor de ASEM revisa el documento.
    - *Aprueba*: Pasa a la siguiente etapa.
    - *Rechaza*: Vuelve al contratista con una observación.
4. **Validación Mandante (Opcional)**: Si el documento es crítico, requiere una segunda aprobación del Mandante.
5. **Vigencia**: Una vez aprobado totalmente, el documento queda "Vigente" hasta su fecha de vencimiento.

### 4.2. Reglas de Vencimiento
- **Fecha Fija**: Documentos que vencen en una fecha específica (ej. Revisión Técnica).
- **Por Periodo**: Documentos mensuales (ej. Pago de Cotizaciones). Se consideran vigentes hasta el mes siguiente + días de gracia.
- **Indefinidos**: Documentos que no caducan (ej. Título Profesional).

### 4.3. Bloqueo por Incumplimiento
El sistema está diseñado para detectar incumplimientos. Si un documento crítico ("Bloqueante") falta o está vencido, la entidad (Trabajador/Vehículo) puede quedar inhabilitada para ingresar a faena (dependiendo de la integración con control de acceso).

## 6. Gestión de Entidades (Panel de Operación)

Esta es la vista principal donde se realiza la gestión documental diaria. Dependiendo de su perfil, verá diferentes opciones de filtrado.

### 6.1. Perfil Mandante
Como Mandante, usted ya está posicionado en su empresa.
1.  **Seleccione Contratista**: Elija la empresa contratista con la que desea operar. Solo verá aquellas con vinculación APROBADA.
2.  **Filtre por Lugar de Trabajo**: Seleccione la faena o instalación específica.
3.  **Filtre por U.O. (Opcional)**: Refine la búsqueda por Unidad Organizacional.

### 6.2. Perfil Contratista
Como Contratista, usted opera sobre su propia empresa.
1.  **Filtre por Principal**: Seleccione el Mandante para el cual está trabajando.
2.  **Filtre por Lugar de Trabajo**: Seleccione dónde están asignados sus recursos.
3.  **Gestión**: Una vez seleccionados los filtros, aparecerán las pestañas (Empresa, Trabajadores, Vehículos, etc.) para cargar documentos.

### 6.3. Perfil ASEM (Admin)
Como Administrador con visión global:
1.  **Filtre por Principal**: Primero seleccione el Mandante.
2.  **Seleccione Contratista**: El sistema cargará los contratistas asociados a ese Mandante.
3.  **Operación**: Continúe con la selección de Lugar y U.O. como en los otros perfiles.

## 7. Preguntas Frecuentes

**¿Por qué mi documento sigue "Pendiente"?**
Probablemente está en la cola de revisión. Algunos documentos requieren doble validación (ASEM + Mandante), lo que puede tomar más tiempo.

**¿Qué hago si me rechazan un documento?**
Revise la "Observación de Rechazo" en el sistema. Corrija el error (ej. foto borrosa, fecha incorrecta) y vuelva a subir el archivo.

**¿Cómo sé qué documentos debo subir?**
Vaya a su "Panel de Control" o "Mi Ficha". El sistema le mostrará una lista de "Pendientes" personalizada para su contrato.
