# Explicación Técnica: Motor de Reglas Documentales

## 1. Concepto General
El **Motor de Reglas** es el mecanismo central que determina qué documentos debe presentar un recurso (Trabajador, Vehículo, Maquinaria, etc.) en un contexto específico (Mandante y Unidad Organizacional). 

No es una relación estática 1 a 1; es una evaluación dinámica que ocurre en tiempo de ejecución (`Runtime Evaluation`) basada en:
1.  **Ámbito (Scope):** Dónde aplica la regla (Mandante, Unidad Organizacional).
2.  **Sujeto (Subject):** A quién aplica (Tipo de Entidad, Cargo, Nacionalidad, etc.).
3.  **Condiciones (Predicates):** Filtros lógicos (RUT específico, Contratista, etc.).

## 2. Modelo de Datos (`ReglaDocumental`)
La persistencia de la regla se maneja en el modelo `App\Models\ReglaDocumental`.

### Estructura Base
*   **Definición del Requerimiento:**
    *   `mandante_id`: El dueño de la regla.
    *   `nombre_documento_id`: El documento exigido (ej. "Contrato de Trabajo").
    *   `tipo_entidad_controlada_id`: El tipo de recurso (Trabajador, Vehículo, etc.).
*   **Alcance Organizacional:**
    *   Relación `unidadesOrganizacionales`: Define en qué nodos del árbol organizacional aplica. Soporta **herencia** (reglas en nodos padres aplican a hijos).
*   **Filtros de Aplicación (Discriminadores):**
    *   `cargosAplica`: Lista de cargos específicos (solo para Trabajadores).
    *   `nacionalidadesAplica`: Lista de nacionalidades.
    *   `tiposVehiculoAplica` / `tiposMaquinariaAplica`: Para activos.
    *   `aplica_empresa_condicion_id`: Filtro por atributos del Contratista.
    *   `rut_especificos` / `rut_excluidos`: Listas blancas/negras por identificador.
*   **Configuración de Validación:**
    *   `tipo_vencimiento_id`: Define si es "Por Periodo" (mensual) o "Por Vencimiento" (fecha fija).
    *   `dias_validez_documento`: Vigencia calculada.
    *   `criterios`: Relación `HasMany` con `ReglaDocumentalCriterio` para validaciones granulares (ej. "¿Tiene firma?", "¿Fecha legible?").

## 3. Implementación del Motor (`DocumentoRequeridoService`)
La lógica de negocio reside en `App\Services\DocumentoRequeridoService`.

### Método Principal: `obtenerEstadoDocumentosParaEntidad`
Este es el "corazón" del motor. Recibe una entidad (ej. un `Trabajador`) y un contexto (`UnidadOrganizacional`), y devuelve la "Matriz de Cumplimiento".

#### Fase 1: Resolución de Candidatos (Scope Resolution)
El método `getReglasParaEntidadEnUO` recupera todas las reglas potenciales.
*   **Lógica de Herencia:** Construye un array de IDs de UO (`$idsUoAplicables`) recorriendo el árbol hacia arriba (`parent_id`).
*   **Query:** Selecciona reglas que coincidan con el `Mandante`, `TipoEntidad` y que estén asignadas a alguna de las UOs en la rama jerárquica.

#### Fase 2: Evaluación de Predicados (Filtering)
Itera sobre cada regla candidata y decide si aplica a la instancia específica (`$entidad`).
*   **Check de Identidad:** Verifica `rut_excluidos` y `rut_especificos`.
*   **Check de Relaciones:**
    *   Si es **Trabajador**: Verifica `Cargo` (vs `TrabajadorVinculacion`), `Nacionalidad`, y `CondicionPersonal`.
    *   Si es **Vehículo/Maquinaria**: Verifica `Tipo` y `Tenencia`.
    *   Si es **Empresa**: Verifica `CondicionEmpresa`.
*   *Si alguna condición falla, la regla se descarta (`continue`).*

#### Fase 3: Determinación de Estado (State Resolution)
Si la regla aplica, el motor busca si existe un `DocumentoCargado` que la satisfaga.
*   **Cruce:** Busca en `documentos_cargados` donde `regla_documental_id_origen` coincida.
*   **Lógica Temporal:**
    *   **Periódicos:** Busca el último periodo cargado. Calcula el `siguiente_periodo_requerido`.
    *   **Vigencia:** Compara la fecha de vencimiento o emisión con la fecha actual (`Carbon::today()`).
*   **Estados Resultantes:** `Aprobado`, `Rechazado`, `Vencido`, `No Cargado`, `Pendiente Validación`.

## 4. Flujo Completo (Lifecycle)

1.  **Diseño (Design Time):**
    *   Administrador configura una `ReglaDocumental` en `GestionReglasDocumentales`.
    *   Define: "Para la UO 'Faena Norte', todos los 'Soldadores' necesitan 'Certificado de Altura'".

2.  **Disparo (Trigger):**
    *   Un usuario consulta el perfil de un trabajador en `ModalGestionDocumentosRecurso`.

3.  **Ejecución (Runtime):**
    *   Se instancia `DocumentoRequeridoService`.
    *   Se llama a `obtenerEstadoDocumentosParaEntidad($trabajador, $faenaNorteId)`.
    *   El motor detecta que el trabajador es "Soldador".
    *   La regla hace "match".

4.  **Resultado:**
    *   El sistema retorna un objeto estructurado indicando que el documento es **Requerido** y su estado actual (ej. "Vencido").

## 5. Resumen de Código
*   **Ubicación:** `app/Services/DocumentoRequeridoService.php`
*   **Complejidad:** O(N*M) donde N son las reglas candidatas y M las condiciones. Optimizado con Eager Loading (`with(['cargosAplica', ...])`).
*   **Extensibilidad:** Nuevos tipos de entidades o condiciones requieren modificar el `match` en el servicio y agregar las relaciones en el modelo `ReglaDocumental`.
