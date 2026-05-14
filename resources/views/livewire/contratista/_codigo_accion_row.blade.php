{{--
    PARTIAL: _codigo_accion_row.blade.php
    Fila de acción para cada código en los acordeones del Contratista.

    Variables esperadas en $det:
      - bloqueado_permanente  : bool → Código con TOTAL en SC cerrada. Nunca más.
      - bloqueado_activo      : bool → Código en SC activa (ENVIADO/EN_REVISION).
      - subsanado             : bool → Compatibilidad legacy
      - enviado               : bool → Compatibilidad legacy
      - en_solicitud          : bool → Compatibilidad legacy (= bloqueado_activo)
      - historial_label       : string|null → Descripción del estado anterior (PARCIAL/RECHAZADO)
      - monto                 : numeric → Monto a incluir en nueva SC (saldo)
      - monto_original        : numeric → Monto original del certificado
      - monto_solucionado     : numeric → Monto ya resuelto en SC anterior
      - id                    : int
--}}

@php
    $bloqueadoPerm  = $det['bloqueado_permanente'] ?? false;
    $bloqueadoActiv = $det['bloqueado_activo'] ?? false;
    $tieneHistorial = !empty($det['historial_label']);
    $montoSaldo     = $det['monto'] ?? 0;
    $montoOrig      = $det['monto_original'] ?? $montoSaldo;
    $montoResuelto  = $det['monto_solucionado'] ?? 0;
@endphp

@if($bloqueadoPerm)
    {{-- 🔒 BLOQUEADO PERMANENTE: ya resuelto con TOTAL --}}
    <tr class="bg-emerald-50 border-t-2 border-emerald-300">
        <td colspan="4" class="py-1.5 px-2">
            <div class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-emerald-700 font-black text-[11px] uppercase tracking-wide">✅ RESUELTO — Solución Total Aceptada</span>
            </div>
        </td>
    </tr>

@elseif($bloqueadoActiv)
    {{-- 🟦 BLOQUEADO TEMPORALMENTE: está en SC activa (enviada/en revisión) --}}
    <tr class="bg-blue-50 border-t-2 border-blue-300">
        <td colspan="4" class="py-1.5 px-2">
            <div class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span class="text-blue-700 font-black text-[11px] uppercase">En revisión — Complementario activo</span>
            </div>
        </td>
    </tr>

@elseif($tieneHistorial)
    {{-- 🟡 DISPONIBLE CON HISTORIAL: PARCIAL o RECHAZADO en SC cerrada → puede ir a nueva SC --}}
    <tr class="bg-amber-50 border-t-2 border-amber-300">
        <td colspan="4" class="py-1.5 px-2">
            <div class="flex flex-col gap-1.5">
                {{-- Info del historial --}}
                <div class="text-[10px] text-amber-800 font-black tracking-wide text-center uppercase mb-1">
                    ⚠️ {{ $det['historial_label'] }}
                </div>
                @if($montoResuelto > 0)
                    {{-- Barra de progreso de cobertura --}}
                    <div class="w-full bg-amber-200/50 rounded-full h-1.5 mx-auto max-w-[250px] mb-1">
                        <div class="bg-emerald-500 h-1.5 rounded-full"
                             style="width: {{ $montoOrig > 0 ? round(($montoResuelto/$montoOrig)*100) : 0 }}%"></div>
                    </div>
                    <div class="text-[10px] text-center text-gray-700 font-bold uppercase tracking-tight">
                        SOLUCIONADO: ${{ number_format($montoResuelto,0,',','.') }} &nbsp;&nbsp;&mdash;&nbsp;&nbsp; POR SOLUCIONAR: <span class="text-rose-600 font-black">${{ number_format($montoSaldo,0,',','.') }}</span>
                    </div>
                @endif
                {{-- Checkbox para incluir en nueva SC --}}
                <label class="cursor-pointer bg-amber-600 hover:bg-amber-700 text-white font-black text-[11px] flex items-center justify-center gap-2 py-1 rounded transition-all">
                    <input type="checkbox" wire:model="incidencias_seleccionadas" value="{{ $det['id'] }}" class="w-3.5 h-3.5 rounded-sm cursor-pointer">
                    Incluir en nueva solicitud complementaria
                </label>
            </div>
        </td>
    </tr>

@else
    {{-- ✅ DISPONIBLE LIBRE: código nunca en SC o primera vez --}}
    <tr class="bg-green-600 border-t-2 border-white">
        <td colspan="4" class="py-1">
            <label class="cursor-pointer text-white font-bold flex items-center justify-center gap-2 text-[12px] w-full">
                <input type="checkbox" wire:model="incidencias_seleccionadas" value="{{ $det['id'] }}" class="w-4 h-4 rounded-sm cursor-pointer">
                Seleccionar para corregir
            </label>
        </td>
    </tr>
@endif
