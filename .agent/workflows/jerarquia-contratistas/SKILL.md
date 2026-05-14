---
name: jerarquia-contratistas
description: Visualización jerárquica de contratistas/subcontratistas con correlativos (1, 1.1, 1.1.1) y colores de fondo alternos por grupo. Patrón usado en el módulo "Informar Contratistas".
---

# Visualización Jerárquica de Contratistas

Permite mostrar contratistas y sus subcontratistas en una tabla con:
- **Correlativos**: `1`, `1.1`, `1.1.1` según nivel de jerarquía
- **Colores de fondo alternos** por grupo (Amarillo/Naranja para grupos con hijos, Blanco/Gris para contratistas simples)
- **Indentación visual** según nivel de profundidad

## Archivos de referencia

El patrón canónico está implementado en:
- **PHP (lógica)**: `app/Livewire/Mandante/InformarContratistas.php` → métodos `ordenarJerarquicamente()` y `getVinculacionesVerificables()`
- **Blade (vista)**: `resources/views/livewire/mandante/informar-contratistas.blade.php` → bloque `@forelse($vinculaciones ...)`

---

## Parte 1: Lógica PHP — `ordenarJerarquicamente()`

Copiar/adaptar este método en el componente Livewire destino:

```php
protected function ordenarJerarquicamente($collection)
{
    $itemsPorContratistaId = $collection->groupBy('contratista_id');

    foreach ($collection as $item) {
        $item->temporal_children = collect();
        $item->is_attached_to_parent = false;
    }

    foreach ($collection as $child) {
        if (empty($child->contratista_padre_id)) continue;

        $candidatos = $itemsPorContratistaId->get($child->contratista_padre_id);
        if (!$candidatos || $candidatos->isEmpty()) continue;

        $mejorPadre = null;
        $mejorPuntaje = -1;

        foreach ($candidatos as $padre) {
            $puntaje = 0;
            // Coincidencia de UO
            if ($padre->unidad_organizacional_mandante_id == $child->unidad_organizacional_mandante_id) $puntaje += 10;
            elseif ($padre->unidad_organizacional_mandante_id && $child->unidad_organizacional_mandante_id) $puntaje -= 50;
            // Coincidencia de Lugar
            if ($padre->dependencia_id == $child->dependencia_id) $puntaje += 10;
            elseif ($padre->dependencia_id && $child->dependencia_id) $puntaje -= 20;
            // Coincidencia de Contrato
            if ($child->numero_contrato && $padre->numero_contrato) {
                if ($child->numero_contrato == $padre->numero_contrato) $puntaje += 50;
                else $puntaje -= 100;
            }
            if ($puntaje > $mejorPuntaje) { $mejorPuntaje = $puntaje; $mejorPadre = $padre; }
        }

        if ($mejorPadre && $mejorPuntaje > -50) {
            $mejorPadre->temporal_children->push($child);
            $child->is_attached_to_parent = true;
        }
    }

    $resultado = collect();
    $aplanarArbol = function ($items, $prefijo = '') use (&$aplanarArbol, &$resultado) {
        $contador = 1;
        foreach ($items as $item) {
            $item->correlativo_jerarquico = $prefijo === '' ? (string)$contador : "$prefijo.$contador";
            $resultado->push($item);
            if ($item->temporal_children->isNotEmpty()) {
                $aplanarArbol($item->temporal_children, $item->correlativo_jerarquico);
            }
            $contador++;
        }
    };

    $raices = $collection->filter(fn($item) => !$item->is_attached_to_parent);
    $aplanarArbol($raices);
    return $resultado;
}
```

> **Requisito del query**: los registros deben traer los siguientes campos para que el algoritmo funcione:
> - `cuo.id as cuo_id` — clave primaria de la CUO
> - `cuo.id_registro` — identificador visible del registro (ej. N° legajo, ID interno)
> - `contratista_id`, `contratista_padre_id` — para construir la jerarquía
> - `unidad_organizacional_mandante_id`, `dependencia_id` — para emparejar padre-hijo
> - `numero_contrato` — score de coincidencia para el emparejamiento

---

## Parte 2: Colores en la Blade

Pegar este bloque `@php` al inicio del `@forelse`:

```blade
@forelse($vinculaciones as $index => $v)
    @php
        $correlativoArray = explode('.', $v->correlativo_jerarquico);
        $numeroBase = (int) $correlativoArray[0];

        // ¿Este grupo tiene hijos?
        $tieneSubcontratistas = $vinculaciones->filter(
            fn($item) => str_starts_with($item->correlativo_jerarquico, $numeroBase . '.')
        )->count() > 0;

        if ($tieneSubcontratistas) {
            // Grupos con hijos: alternan Amarillo / Naranja
            static $grupoCounter = 0;
            static $lastBaseGroup = null;
            if ($lastBaseGroup !== $numeroBase) { $grupoCounter++; $lastBaseGroup = $numeroBase; }
            $fondoClase = ($grupoCounter % 2 == 1)
                ? 'bg-yellow-100/50 dark:bg-yellow-900/30'
                : 'bg-orange-100/50 dark:bg-orange-900/30';
        } else {
            // Simples: alternan Blanco / Gris
            static $simpleCounter = 0;
            static $lastBaseSimple = null;
            if ($lastBaseSimple !== $numeroBase) { $simpleCounter++; $lastBaseSimple = $numeroBase; }
            $fondoClase = ($simpleCounter % 2 == 1)
                ? 'bg-white dark:bg-gray-800'
                : 'bg-gray-300 dark:bg-gray-600';
        }

        // Indentación según nivel de profundidad
        $nivel = count($correlativoArray) - 1;
        $indentClass = $nivel > 0 ? 'pl-' . ($nivel * 3) : '';
    @endphp

    <tr class="{{ $fondoClase }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors">
        {{-- Columna de correlativo --}}
        <td class="px-3 py-2 text-sm text-gray-500">{{ $v->correlativo_jerarquico }}</td>

        {{-- Columna de Razón Social con indentación --}}
        <td class="px-3 py-2 text-sm {{ $indentClass }}">
            @if($nivel > 0) └ @endif {{ $v->razon_social }}
        </td>
        {{-- ... resto de columnas --}}
    </tr>
@empty
    <tr><td colspan="X" class="text-center py-4">Sin resultados.</td></tr>
@endforelse
```

---

## Parte 3: Llamada en render()

```php
public function render()
{
    $vinculaciones = $this->ordenarJerarquicamente(
        collect($this->getVinculacionesVerificables())
    );

    return view('livewire.mi-modulo.mi-vista', [
        'vinculaciones' => $vinculaciones,
    ])->layout('layouts.app');
}
```

---

## Checklist de implementación

- [ ] Incluir en el SELECT del query:
  - `cuo.id as cuo_id`
  - `cuo.id_registro` ⚠️ **obligatorio** para identificar el registro en la blade
  - `contratista_id`, `contratista_padre_id`
  - `unidad_organizacional_mandante_id`, `dependencia_id`, `numero_contrato`
- [ ] Agregar método `ordenarJerarquicamente()` al componente PHP
- [ ] Llamar al método en `render()` antes de pasar a la vista
- [ ] Pegar el bloque `@php` de colores al inicio del `@forelse` en la blade
- [ ] Usar `{{ $v->correlativo_jerarquico }}` como primera columna
- [ ] Mostrar `{{ $v->id_registro }}` en la columna de ID visible
- [ ] Aplicar `$fondoClase` como clase dinámica en el `<tr>`
- [ ] Usar `$indentClass` en la columna de nombre para indentación visual

---

## Esquema de colores resultante

| Tipo de fila | Color |
|---|---|
| Contratista simple (impar) | Blanco |
| Contratista simple (par) | Gris claro |
| Grupo con hijos (impar) | Amarillo suave |
| Grupo con hijos (par) | Naranja suave |
| Subcontratista (hereda grupo) | Mismo fondo que su padre |
