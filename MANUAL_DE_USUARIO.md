# MANUAL DE USUARIO PASO A PASO: OVALCONTROL

¡Bienvenido al sistema **Master Documentación Controlada**! 
Este manual está escrito de la forma más sencilla posible. Aquí te enseñaremos dónde hacer clic, para qué sirve cada botón y qué significan los colores que verás en tu pantalla.

Para hacerte la vida más fácil, hemos dividido este manual en tres partes, según el tipo de usuario que seas. **Solo lee la sección que corresponde a tu trabajo:**

1.  **SECCIÓN 1: Perfil OVAL ADMIN (ASEM)** - (Para los administradores del sistema y revisores de documentos).
2.  **SECCIÓN 2: Perfil PRINCIPAL (MANDANTE)** - (Para las empresas dueñas de los proyectos que necesitan supervisar).
3.  **SECCIÓN 3: Perfil CONTRATISTA (y Subcontratistas)** - (Para las empresas que prestan servicios y necesitan subir los documentos de sus trabajadores).

---

## CONCEPTOS BÁSICOS UNIVERSALES (Para todos)

Antes de empezar, debes conocer 3 palabras clave que usaremos mucho:
*   **Acreditación:** Es el permiso para entrar a trabajar. Si subes los documentos correctos de un trabajador, él estará "Habilitado".
*   **Verificación Laboral:** Es un trámite mensual. Cada mes debes subir las liquidaciones de sueldo y el pago de imposiciones.
*   **Criticidad (El Peligro):** Algunos documentos son "Críticos". Si un documento crítico vence o falta, el sistema le pondrá una Cruz Roja (Bloqueado) al trabajador automáticamente.

---
================================================================================
================================================================================

# SECCIÓN 1: PERFIL OVAL ADMIN (ASEM ADMIN)
*Esta sección es exclusiva para el equipo que administra la plataforma y revisa si los documentos están buenos o malos.*

## 1.1 INGRESO AL SISTEMA (LOGIN)
Para entrar, necesitas tu correo y tu clave secreta.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Pantalla de Login con los campos vacíos]`

1.  Escribe tu correo en el cajón de "Correo Electrónico".
2.  Escribe tu clave en "Contraseña".
3.  Haz clic en el botón azul "Iniciar Sesión".

## 1.2 EL TABLERO PRINCIPAL (DASHBOARD)
Es lo primero que ves al entrar. Te muestra un resumen rápido de cómo está la plataforma hoy.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Dashboard de ASEM]`

*   Aquí verás cuadros con números grandes indicando cuántos documentos hay pendientes por revisar.

## 1.3 GESTIÓN DE DOCUMENTOS (Tu lugar principal de trabajo)
Aquí es donde revisarás los papeles que suben los contratistas.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Tabla de Gestión de Documentos]`

### Los Filtros (Cómo buscar algo específico)
Arriba de la tabla, tienes cajones donde puedes buscar:
*   **Cajón "Contratista":** Escribe el nombre de la empresa que quieres revisar.
*   **Desplegable "Principal":** Elige el proyecto (Mandante).
*   **Desplegable "Estado":** Puedes pedirle al sistema que te muestre solo los documentos "Pendientes", "Aprobados" o "Rechazados".
*   *IMPORTANTE:* Siempre haz clic en el botón morado **"Buscar"** para que el sistema obedezca tus filtros. Si quieres borrar la búsqueda, presiona el botón rojo **"Resetear"**.

### Los Botones de Colores (Qué hacer con cada documento)
A la derecha de cada fila de la tabla, verás botones de colores:
*   **Botón Verde ("Validar"):** Haz clic aquí para abrir el documento, leerlo y decidir si lo "Apruebas" o lo "Rechazas".
*   **Botón Azul ("Auditar"):** Haz clic aquí si te equivocaste y necesitas cambiarle el estado a un documento que ya habías revisado.
*   **Botón Naranja ("Vencimiento"):** (Arriba de la tabla) Sirve para cambiarle la fecha de vencimiento a un documento si le quieres dar más plazo.

## 1.4 GESTIÓN DE SOLICITUDES (Dejando entrar nuevas empresas)
Cuando una empresa nueva quiere usar el sistema, primero te llegará una solicitud que debes revisar.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Tabla de Solicitudes de Vinculación]`

*   **Botón Verde "Aprobar":** Le das permiso a la empresa para que empiece a subir sus cosas.
*   **Botón Rojo "Rechazar":** Le dices que no, porque sus datos están malos.

## 1.5 LISTADOS UNIVERSALES (Creando las Bases del Sistema)
Antes de pedir documentos, el sistema necesita saber qué cargos existen, qué tipos de vehículos hay y cuáles son las empresas vigentes. Todo esto se crea aquí.

Ve al menú izquierdo, abre **"CONFIGURACIÓN"** y luego **"LISTADOS UNIVERSALES"**. Encontrarás 3 opciones principales:

### A. HUB Principal (El corazón de los listados)
Aquí creas las "etiquetas" que usarán todos los demás. Por ejemplo, si un contratista quiere agregar un trabajador que es "Soldador", la palabra "Soldador" tiene que estar creada aquí primero.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Pantalla del HUB Principal con sus pestañas]`

*   **Pestañas Superiores:** Verás opciones como "Cargos", "Tipos de Vehículos", "Categorías de Documentos", etc.
*   **Botón "Nuevo" (Azul):** Haces clic aquí, escribes el nombre (ej. "Soldador") y lo guardas. Inmediatamente estará disponible para todos.

### B. Unidades Operativas (Áreas de Trabajo)
Aquí creas las grandes áreas de la empresa principal. Por ejemplo: "Planta Norte", "Oficina Central", "Mina Sur".

### C. Dependencias / Lugares Físicos
Es un nivel más específico. Si la Unidad Operativa es "Planta Norte", las dependencias pueden ser "Comedor", "Bodega 1" o "Área de Calderas".

## 1.6 CONFIGURACIÓN DE REGLAS DOCUMENTALES (El Cerebro del Sistema)
Esta es la parte más poderosa y detallada del sistema. Aquí le dices exactamente **QUÉ PAPELES** le debe exigir a **QUIÉN**, en qué lugares, bajo qué condiciones y cómo se deben revisar. 

Ve al menú izquierdo: **"CONFIGURACIÓN"** -> **"REGLAS DOCUMENTALES"**.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Listado Principal de Reglas Documentales]`

En la primera pantalla verás una tabla con todas las reglas que ya existen. Puedes usar los pequeños botones para editar (lápiz azul), ver el historial (reloj celeste), o borrar la regla (basurero rojo).

Para crear una nueva exigencia, haz clic en el botón azul **"Nueva Regla"**. Aparecerá un formulario largo. Para que sea muy fácil, lo dividiremos en 5 grandes pasos:

### PASO 1: ¿Qué papel y a Quién se lo pido? (Datos Base)
`[AQUÍ VA LA CAPTURA DE PANTALLA: Sección del formulario con Documento, Principal y Entidades]`
*   **DOCUMENTO:** Eliges el papel a pedir (Ej. "Contrato de Trabajo"). *Recuerda que primero debiste crearlo en el Hub Principal.*
*   **PRINCIPAL:** Seleccionas la empresa Mandante dueña del proyecto.
*   **Entidad Controlada:** ¿A quién le exigirás esto? ¿A una **Persona** (Trabajador), a un **Vehículo**, a una **Maquinaria**, a una **Embarcación**, o a la **Empresa** como un todo?
*   **RUT Específicos / RUT Excluidos:** Si quieres que esta regla aplique *solo* a ciertas personas o máquinas exactas (pones su RUT o Patente separadas por `;`), o que le aplique a todos *menos* a esos específicos (RUT Excluidos).
*   **Filtros Específicos (Nacionalidad, Cargos, etc.):** Dependiendo de la entidad saltarán opciones para afinar la puntería:
    *   *Si es Persona:* Puedes elegir que el papel solo se le pida a los cargos "Soldador" o "Eléctrico", o a la nacionalidad dictada.
    *   *Si es Vehículo (o máquina/embarcación):* Puedes elegir que solo se le pida a las "Camionetas" o solo a los vehículos en estado "Propio" o "Arrendado" (Tenencias).

### PASO 2: ¿En qué lugar? (Unidades Operativas a las que aplica)
`[AQUÍ VA LA CAPTURA DE PANTALLA: Sección de Unidades Operativas (Niveles 1 al 4)]`
Aquí le dices al sistema en qué lugares geográficos/físicos es obligatorio este documento.
1.  Haz clic en el botón verde **"Añadir U.O."**.
2.  Usa los desplegables para elegir Nivel 1 (Proyecto), Nivel 2 (Área), Nivel 3 y Nivel 4 (Lugar súper exacto). 
3.  Puedes presionar "Añadir U.O." varias veces para crear una lista de lugares donde este papel es necesario. ¡Si la persona no va a ese lugar, el sistema no le pedirá el documento!

### PASO 3: Ayudas para el Revisor (Analista)
`[AQUÍ VA LA CAPTURA DE PANTALLA: Sección de Ayudas (Observación, Formato y Documento Relacionado)]`
Cuando un experto de tu equipo o de OVAL Admin revise este papel subido por el contratista, el sistema le mostrará estas ayudas para hacerle el trabajo más fácil:
*   **Observación Documento:** Un texto de consejo que creaste (Ej: "Revisar que tenga timbre notarial").
*   **Formato Documento:** Permite elegir un archivo de ejemplo (como una foto de un finiquito perfecto) que el analista puede ver para comparar si el que le subieron es falso o incorrecto.
*   **Documento Relacionado (Ojo aquí):** Sirve de apoyo. Por ejemplo, al revisar un "Certificado Médico", podrías obligar al sistema a que también le ponga en pantalla el "Contrato" de esa persona al revisor, para que confirme rápido algunos datos cruzados.

### PASO 4: Vencimientos y Control de Tiempos (El Reloj)
`[AQUÍ VA LA CAPTURA DE PANTALLA: Sección de Tipos de Vencimiento y Fechas]`
Aquí configuras cuánto dura el papel antes de marcarse rojo como VENCIDO.
*   **TIPO DE VENCIMIENTO:** Las opciones son:
    *   *Desde Carga:* El papel durará los días que tú quieras, contando desde la fecha que el contratista lo subió.
    *   *Desde Emisión:* Cuenta desde la fecha de firma o emisión del documento mismo.
    *   *Por Período:* Para papeles (ej. liquidaciones) que deben renovarse el mes exacto del período declarado con cierta holgura días "gracia".
*   **Valida Emisión / Valida Vencimiento:** Si las marcas, obligarás al Analista humano a mirar el documento y tipear manualmente la "Fecha de Emisión" y la "Fecha en que se Vence".
*   **Días Aviso Vencimiento:** Número de días antes de que el papel venza en los que el sistema le mandará correos automáticos al contratista (la famosa alerta amarilla) diciendo "Oye, este papel está por vencerse!".

### PASO 5: Criterios de Evaluación y Rechazo Automático
`[AQUÍ VA LA CAPTURA DE PANTALLA: Sección de Criterios (ASEM y Principal)]`
Esta es **"La Pauta de Corrección"**. Le dice al analista qué validar paso a paso. Si el documento falla en alguno de estos puntos, el sistema genera la explicación automática del rechazo.
1.  Haz clic en **"Agregar Criterio"**.
2.  **Criterio:** Tema grueso a revisar (Ej: "Revisión de Firmas").
3.  **Sub Criterio:** Exactamente qué mirar (Ej: "La firma debe estar legalizada ante notario").
4.  **Aclaración Criterio:** Una explicación de ayuda interna para tu analista.
5.  **Texto Rechazo (¡Súper Importante!):** Es el texto que el sistema le mandará por correo al contratista si le rechazan el papel por fallar este punto. (Ej: *"Rechazado porque la firma no tiene timbre de notaría o está ilegible"*).

> [!IMPORTANT]
> **Criterios de PRINCIPAL (Doble Revisión)**
> Encontrarás una casilla que dice *"Requiere Validación de Principal"*. Si la marcas, significa que el documento es TAN importante que tiene que pasar el filtro del ASEM y, si pasa, va a una segunda fila de espera para que la empresa Mandante lo re-apruebe con otros criterios exclusivos morados. 

### PASO 6: Histórico y Casillas Finales
`[AQUÍ VA LA CAPTURA DE PANTALLA: Casillas finales de la Regla]`
*   **Mostrar Histórico (Histórico Documental):** Si marcas esto, significa que cuando un papel venza y suban el nuevo sobre él, el viejo NO se destruye. Se guarda en un archivador oculto para que siempre puedas ver la "Historia" del trabajador.
*   **Configuraciones Opcionales de Identidad:** En esta parte, si la entidad es Persona, podrás permitir o bloquear ciertos botones durante el alta relacionada a esta regla. (Ej: Modificar Nacionalidad, Modificar Fechas).

¡Cuando llenes toda la oración, presiona **"Guardar Regla"**!

### HISTORIAL DE LOS CAMBIOS (El Ojo que todo lo ve)
Si otra persona de tu equipo mueve algo en la regla documentar (Por ej. Cambia el tiempo de vencimiento de 1 año a 2 años). Podrás saber **quién fue**. En la tabla resumen de reglas, a mano derecha, haz clic en el botón con ícono de un reloj. Se abrirá la **"Auditoría: Historial de Cambios"**, indicando qué usuario hizo el cambio, qué fecha y exactamente qué palabra modificó, mostrándote el texto antiguo tachado en rojo y el texto nuevo en verde.

## 1.7 CONFIGURACIÓN DE CRITICIDAD Y ACCESO (¿Qué pasa si falta el papel?)
Las reglas dicen "Pídele este papel", pero la **Criticidad** es donde le dices al sistema **qué castigo aplicar** si la empresa no cumple. 

Ve al menú izquierdo: **"CONFIGURACIÓN"** -> **"CRITICIDAD"** (o Gestión de % y Acceso).

`[AQUÍ VA LA CAPTURA DE PANTALLA: Tabla de Gestión de Criticidad y Acceso]`

En la parte de arriba verás 3 filtros clave:
1.  **Principal:** Elige la empresa dueña del proyecto.
2.  **Filtrar por U.O.:** Si quieres ver solo los papeles que se piden en un lugar físico específico.
3.  **Filtrar por Entidad:** Para revisar solo papeles de Personas, o solo de Vehículos.

Luego verás una tabla gigante agrupada por Entidades, y al lado del nombre de cada documento, hay 3 interruptores muy importantes que debes marcar o desmarcar según la orden del Mandante:

### A. "Afecta % Cumplimiento" (La Nota de Comportamiento)
*   **Si lo marcas:** Significa que este papel es parte de la "nota" de la empresa. Si un trabajador no lo tiene, o lo tiene vencido, el porcentaje de cumplimiento de la empresa contratista bajará (ej. del 100% al 98%).
*   **Si NO lo marcas:** El papel se pedirá igual, la gente lo subirá, pero si no lo suben, la empresa contratista seguirá teniendo 100% de cumplimiento. Es un papel "informativo".

### B. "Restringe Acceso" (La Puerta del Proyecto)
*   **Si lo marcas (¡MUY PELIGROSO!):** Esta es literal la "Cruz Roja". Significa que este papel es de **VIDA O MUERTE** para entrar a trabajar. Si falta, o si está vencido, o si ASEM lo rechazó, el sistema bloqueará en rojo al trabajador instantáneamente y el guardia no lo dejará pasar.
*   **Si NO lo marcas:** Aunque el papel falte, el trabajador podrá cruzar la puerta de la mina (su estado de acceso seguirá verde), aunque a la empresa le baje el porcentaje (si marcaste la opción A).

### C. "Es Perseguidor" (El Fantasma)
*   **Si lo marcas:** Hace que la deuda de este papel persiga a la empresa a lo largo del tiempo. Si la empresa se va del proyecto debiendo este papel (ej. finiquitos que debían al final de la faena), la próxima vez que quieran volver a trabajar meses después, el sistema les cobrará este documento antes de dejarlos hacer nada.

## 1.8 VERIFICACIÓN LABORAL (Revisión Mensual)
En el menú lateral, verás una sección llamada "VERIFICACIÓN". Esto tiene 3 pasos y 3 personas distintas lo hacen:

1.  **Asignación (Lo hace el Supervisor):** Elige qué carpetas mensuales revisará cada trabajador (Analista).
2.  **Revisión (Lo hace el Analista):** Abre la carpeta del mes, revisa las imposiciones y dice si está todo OK.
3.  **Auditoría (Lo hace el Auditor):** Es el jefe que revisa lo que hizo el analista y emite el "Certificado Final".

---
================================================================================
================================================================================

# SECCIÓN 2: PERFIL PRINCIPAL (MANDANTE)
*Esta sección es para la empresa dueña del proyecto. Aquí vienes a mirar, supervisar y a sacar reportes, no a cargar documentos.*

## 2.1 PANEL DE SUPERVISIÓN (La Vista de Águila)
Aquí puedes ver de un solo vistazo cómo se están portando todas las empresas contratistas que trabajan para ti.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Panel de Supervisión (Hawk View) mostrando porcentajes]`

*   Verás una lista con todas las empresas.
*   Al lado derecho de cada empresa hay un **Porcentaje (%)**. 
    *   Si dice 100%, significa que esa empresa tiene todos sus papeles al día.
    *   Si está en rojo (ej. 45%), significa que les faltan muchos documentos y sus trabajadores podrían tener bloqueada la entrada.

## 2.2 VER EL DETALLE DE UNA EMPRESA
Si ves que una empresa tiene un porcentaje muy bajo, puedes hacer clic en el botón azul **"Gestión Entidad"** que está al final de su fila.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Listado de Trabajadores de un Contratista visto por Mandante]`

*   Al hacer esto, el sistema te mostrará la lista exacta de todos los trabajadores de esa empresa.
*   En la columna **"Acceso"**, verás:
    *   Un texto verde que dice **"HABILITADO"** (Puede entrar a trabajar).
    *   O una gran cruz roja **"✕"** (No puede entrar porque le faltan papeles).

## 2.3 CONTROL MANUAL DE ACCESO (Tarjeta Roja y Verde)
A veces, necesitas dejar entrar a un trabajador urgente aunque le falte un documento, o necesitas bloquear a alguien por mala conducta. Eso se hace en "Gestión de Excepciones".

`[AQUÍ VA LA CAPTURA DE PANTALLA: Panel de Gestión de Excepciones]`

*   Busca a la persona en la lista.
*   **Botón Verde ("Habilitar"):** Forza al sistema a decirle a la puerta que ESTA PERSONA SÍ ENTRA. El sistema te pedirá que escribas *por qué* le das este permiso especial y hasta *qué fecha* durará el favor.
*   **Botón Rojo ("Restringir"):** Forza al sistema a decirle a la puerta que ESA PERSONA NO ENTRA, sin importar que tenga sus papeles al día.

---
================================================================================
================================================================================

# SECCIÓN 3: PERFIL CONTRATISTA (Y SUBCONTRATISTA)
*Esta sección es para ti, la empresa que presta el servicio. Aquí aprenderás a subir a tus trabajadores y sus documentos para que los dejen entrar a trabajar.*

## 3.1 CREAR TRABAJADORES (Gestión de Entidades)
Lo primero es decirle al sistema quiénes son las personas que irán a trabajar. Ve al menú izquierdo y haz clic en **"Gestión de Entidades"**.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Botón Azul de Agregar Nuevo Trabajador y la tabla vacía]`

1.  Haz clic en el botón azul que dice **"Agregar Nuevo Trabajador"**.
2.  Aparecerá un formulario pequeño. Escribe su RUT, Nombres, Apellidos y elige su Cargo.
3.  Presiona **"Guardar"**. Tu trabajador aparecerá inmediatamente en la tabla.

## 3.2 CÓMO SUBIR UN DOCUMENTO (Para que lo dejen entrar)
Si miras a tu trabajador recién creado en la tabla, verás que en la columna "Acceso", tiene una gran cruz roja **"✕"**. Esto es porque no tiene papeles.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Fila de un trabajador destacando el botón del ojito/documento negro]`

1.  En la fila de tu trabajador, al final a la derecha, busca un **botón cuadrado de color gris oscuro o azul que tiene el dibujo de una hoja y una lupa (Ojo)**. Haz clic ahí.
2.  Se abrirá una gran pantalla que dice "Documentación de [Nombre de tu trabajador]".
3.  Verás una lista de papeles que te están pidiendo (Ej: Contrato de Trabajo, Cédula de Identidad).
4.  Junto a cada nombre de documento que falta, hay un botón con una "Nube". Haz clic ahí para buscar el archivo PDF o Foto en tu computador y subirlo.

### Los Colores de los Documentos:
*   **Gris (Faltante):** No has subido nada.
*   **Amarillo (Pendiente):** Ya lo subiste, pero el jefe (OVAL Admin) aún no lo revisa. ESPERA.
*   **Verde (Aprobado):** ¡Todo perfecto! El documento está bueno.
*   **Rojo (Rechazado):** El documento estaba malo, ilegible o vencido. Te enviarán un correo explicando por qué. Debes subirlo de nuevo.

## 3.3 VERIFICACIÓN LABORAL (Tus papeles de fin de mes)
Todos los meses debes demostrar que le pagaste el sueldo y las cotizaciones a tu gente. Ve al menú izquierdo y haz clic en **"VERIFICACIÓN"**.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Pantalla de Verificación Laboral mostrando los semáforos]`

### El Semáforo de Plazos
A la izquierda verás una lista con los meses (Ej: Enero, Febrero). Fíjate en el color:
*   **Etiqueta Verde (DENTRO DE PLAZO):** Estás a tiempo. ¡Excelente!
*   **Etiqueta Amarilla (FUERA DE PLAZO):** Se te pasó la fecha ideal, pero el sistema aún te deja subir los papeles. Apúrate.
*   **Etiqueta Roja (VENCIDO):** El plazo se cerró definitivamente.

### Cómo enviar el mes:
1.  Haz clic sobre el mes (ej: Enero).
2.  Sube los archivos que te piden (Liquidaciones, Planillas de AFP, etc.) apretando la nubecita.
3.  **PASO MÁS IMPORTANTE:** Cuando sumas TODO, debes hacer clic en el botón grande arriba a la derecha que dice **"ENVIAR PERIODO"**. Si no lo aprietas, el sistema pensará que sigues trabajando y nadie te revisará.

## 3.4 MIS SUBCONTRATISTAS (Si tú contratas a otra empresa)
Si trajiste a otra empresa para que te ayude con el trabajo, ellos también deben subir sus papeles, pero bajo tu responsabilidad.

`[AQUÍ VA LA CAPTURA DE PANTALLA: Pantalla de Mis Subcontratistas]`

1.  Ve a **"Mis Sub-Contratistas"** en el menú.
2.  Haz clic en **"Solicitar Sub-Contratista"** y pon el RUT de la empresa amiga.
3.  Ellos tendrán que entrar con sus propias claves, pero tú podrás ver en una pantalla si ellos están cumpliendo o no (verás su porcentaje de cumplimiento).

---

## DUDAS RÁPIDAS (Las preguntas de siempre)

**1. Ya subí el contrato, pero a mi trabajador le sigue saliendo la '✕' roja de No Habilitado. ¿Por qué?**
R: Porque subirlo no es suficiente. Debes esperar a que un revisor humano (ASEM) lo lea y aprete "Aprobar" para que se ponga verde. Solo cuando los papeles obligatorios estén verdes, el trabajador tendrá la palabra "HABILITADO".

**2. ¿Puedo borrar un documento que subí mal?**
R: El sistema no borra cosas, las reemplaza. Simplemente vuelve a subir el certificado correcto apretando el botón de la nube. El archivo viejo desaparecerá y el nuevo se pondrá amarillo (Pendiente de revisión).

**3. ¿Cómo le doy claves a mi secretaria para que me ayude a subir cosas?**
R: Ve al menú "Gestión de Usuarios". Haz clic en "Agregar Usuario", pon el nombre y correo de tu secretaria. A ella le llegará un correo con su clave para entrar a ayudarte.

---
**FIN DEL MANUAL.**
*(Para el administrador del sistema: Inserte las capturas de pantalla reemplazando los textos entre corchetes rectos `[ ]`).*
