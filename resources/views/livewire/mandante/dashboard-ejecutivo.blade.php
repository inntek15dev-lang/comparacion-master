<div x-data="dashboardEjecutivo()" x-init="init()" class="min-h-screen bg-[#0a0f1e]">

    {{-- HEADER HERO --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-[#0a0f1e] via-[#0d1b3e] to-[#0a1628] border-b border-white/10 px-8 py-6">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(ellipse at 20% 50%, #1e40af 0%, transparent 50%), radial-gradient(ellipse at 80% 20%, #7c3aed 0%, transparent 50%)"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                    <span class="text-emerald-400 text-[10px] font-black uppercase tracking-[0.3em]">LIVE · DASHBOARD EJECUTIVO</span>
                </div>
                <h1 class="text-3xl font-black text-white tracking-tight">{{ $mandanteNombre }}</h1>
                <p class="text-white/40 text-sm mt-0.5">Centro de Inteligencia Operacional · OvalControl</p>
            </div>
            <div class="flex items-center gap-4">
                <div>
                    <label class="text-white/40 text-[9px] uppercase tracking-widest block mb-1">Año</label>
                    <select wire:model.live="anioFiltro" class="bg-white/10 border border-white/20 text-white text-xs font-bold rounded-lg px-3 py-2 focus:outline-none focus:border-blue-400">
                        @foreach($aniosDisponibles as $a)
                            <option value="{{ $a }}" class="bg-[#0d1b3e]">{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-right">
                    <div class="text-white/30 text-[9px] uppercase tracking-widest">Actualizado</div>
                    <div class="text-white/70 text-xs font-bold">{{ now()->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="px-6 py-6 space-y-6">

        {{-- ═══════════════════════════════════════ --}}
        {{-- FILA 1: KPI CARDS UNIVERSO CONTROLADO  --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            @php
            $kpiCards = [
                ['label'=>'Empresas Contratistas','value'=>number_format($kpisUniverso['empresas']),'icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4','color'=>'from-blue-600 to-blue-800','glow'=>'shadow-blue-900'],
                ['label'=>'Trabajadores Activos','value'=>number_format($kpisUniverso['trabajadores']),'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','color'=>'from-emerald-600 to-emerald-800','glow'=>'shadow-emerald-900'],
                ['label'=>'Vehículos','value'=>number_format($kpisUniverso['vehiculos']),'icon'=>'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h6l2-2zm0 0l2-2h3l2 2H13z','color'=>'from-violet-600 to-violet-800','glow'=>'shadow-violet-900'],
                ['label'=>'Maquinarias','value'=>number_format($kpisUniverso['maquinarias']),'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z','color'=>'from-amber-600 to-amber-800','glow'=>'shadow-amber-900'],
                ['label'=>'Embarcaciones','value'=>number_format($kpisUniverso['embarcaciones']),'icon'=>'M12 19l9 2-9-18-9 18 9-2zm0 0v-8','color'=>'from-cyan-600 to-cyan-800','glow'=>'shadow-cyan-900'],
            ];
            @endphp
            @foreach($kpiCards as $k)
            <div class="relative bg-gradient-to-br {{ $k['color'] }} rounded-2xl p-5 shadow-xl {{ $k['glow'] }}/30 border border-white/10 overflow-hidden group hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <svg class="w-8 h-8 text-white/30 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $k['icon'] }}"/></svg>
                <div class="text-3xl font-black text-white tabular-nums">{{ $k['value'] }}</div>
                <div class="text-white/60 text-[10px] font-bold uppercase tracking-wider mt-1">{{ $k['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- FILA 2: SALDO CONTINGENCIAS TOTAL + GRÁFICO POR ÍTEM  --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- BIG NUMBER: Saldo Total --}}
            <div class="relative bg-gradient-to-br from-red-900/80 to-red-950/90 rounded-2xl p-6 border border-red-500/30 shadow-2xl shadow-red-900/20 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-2 h-2 bg-red-400 rounded-full animate-pulse"></div>
                        <span class="text-red-300 text-[9px] font-black uppercase tracking-[0.2em]">CONTINGENCIAS TOTALES</span>
                    </div>
                    <div class="text-[10px] text-white/40 uppercase tracking-widest mb-1">Saldo Acumulado {{ $anioFiltro }}</div>
                    <div class="text-4xl font-black text-white leading-tight">
                        ${{ number_format($contingenciasResumen['total_saldo'], 0, ',', '.') }}
                    </div>
                    <div class="text-red-300/60 text-xs mt-1">Retenibles + No Retenibles</div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-2">
                    @php
                    $retenible = collect($contingenciasResumen['por_clasificacion'])->where('subtipo','retenible')->sum('total');
                    $noRetenible = collect($contingenciasResumen['por_clasificacion'])->where('subtipo','no_retenible')->sum('total');
                    @endphp
                    <div class="bg-white/5 rounded-xl p-3 border border-red-500/20">
                        <div class="text-[9px] text-red-300/60 uppercase tracking-widest mb-1">Retenible</div>
                        <div class="text-lg font-black text-red-300">${{ number_format($retenible, 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3 border border-amber-500/20">
                        <div class="text-[9px] text-amber-300/60 uppercase tracking-widest mb-1">No Retenible</div>
                        <div class="text-lg font-black text-amber-300">${{ number_format($noRetenible, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- GRÁFICO: Por Clasificación --}}
            <div class="lg:col-span-2 bg-white/5 backdrop-blur rounded-2xl p-5 border border-white/10">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-white/80 text-xs font-black uppercase tracking-widest">Desglose por Ítem de Contingencia</span>
                </div>
                <div class="relative h-52">
                    <canvas id="chartClasificacion"></canvas>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════ --}}
        {{-- FILA 3: EVOLUCIÓN ANUAL + MENSUAL  --}}
        {{-- ═══════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white/5 backdrop-blur rounded-2xl p-5 border border-white/10">
                <div class="text-white/80 text-xs font-black uppercase tracking-widest mb-4">📈 Evolución Histórica de Contingencias (por Año)</div>
                <div class="relative h-52">
                    <canvas id="chartEvolucion"></canvas>
                </div>
            </div>
            <div class="bg-white/5 backdrop-blur rounded-2xl p-5 border border-white/10">
                <div class="text-white/80 text-xs font-black uppercase tracking-widest mb-4">📅 Contingencias Mensuales · {{ $anioFiltro }}</div>
                <div class="relative h-52">
                    <canvas id="chartMensual"></canvas>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════ --}}
        {{-- FILA 4: RANKING CONTINGENCIA + CUMPLIMIENTO --}}
        {{-- ══════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- Ranking Contingencia --}}
            <div class="bg-white/5 backdrop-blur rounded-2xl border border-white/10 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-white/10 bg-red-900/20">
                    <span class="text-white/80 text-xs font-black uppercase tracking-widest">🏆 Ranking · Saldo Contingencias</span>
                    <button wire:click="toggleOrdenContingencia" class="text-[9px] font-black text-red-300 bg-red-900/40 border border-red-500/30 px-2 py-1 rounded hover:bg-red-800/50 transition-colors">
                        {{ $ordenRankingContingencia === 'desc' ? '↓ MAYOR' : '↑ MENOR' }}
                    </button>
                </div>
                <div class="overflow-auto max-h-72">
                    <table class="w-full text-[10px]">
                        <thead class="sticky top-0 bg-[#0d1b3e]">
                            <tr>
                                <th class="px-4 py-2 text-left text-white/30 font-black uppercase">#</th>
                                <th class="px-4 py-2 text-left text-white/30 font-black uppercase">Empresa</th>
                                <th class="px-4 py-2 text-right text-white/30 font-black uppercase">Saldo</th>
                                <th class="px-4 py-2 text-right text-white/30 font-black uppercase">Inc.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rankingContingencia as $i => $r)
                            @php
                            $maxSaldo = collect($rankingContingencia)->max('saldo') ?: 1;
                            $pct = $maxSaldo > 0 ? ($r['saldo'] / $maxSaldo) * 100 : 0;
                            @endphp
                            <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                <td class="px-4 py-2.5">
                                    <span class="{{ $i < 3 ? 'text-amber-400 font-black' : 'text-white/30' }}">{{ $i+1 }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="text-white/90 font-bold leading-tight">{{ Str::limit($r['razon_social'],30) }}</div>
                                    <div class="w-full bg-white/5 rounded-full h-1 mt-1">
                                        <div class="bg-red-500 h-1 rounded-full" style="width:{{ $pct }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-right font-black text-red-300">${{ number_format($r['saldo'],0,',','.') }}</td>
                                <td class="px-4 py-2.5 text-right text-white/40">{{ $r['num_incidencias'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-white/20 italic">Sin datos de contingencias</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Ranking Cumplimiento --}}
            <div class="bg-white/5 backdrop-blur rounded-2xl border border-white/10 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-white/10 bg-emerald-900/20">
                    <span class="text-white/80 text-xs font-black uppercase tracking-widest">✅ Ranking · Cumplimiento Documental</span>
                    <button wire:click="toggleOrdenCumplimiento" class="text-[9px] font-black text-emerald-300 bg-emerald-900/40 border border-emerald-500/30 px-2 py-1 rounded hover:bg-emerald-800/50 transition-colors">
                        {{ $ordenRankingCumplimiento === 'asc' ? '↑ MENOR' : '↓ MAYOR' }}
                    </button>
                </div>
                <div class="overflow-auto max-h-72">
                    <table class="w-full text-[10px]">
                        <thead class="sticky top-0 bg-[#0d1b3e]">
                            <tr>
                                <th class="px-4 py-2 text-left text-white/30 font-black uppercase">#</th>
                                <th class="px-4 py-2 text-left text-white/30 font-black uppercase">Empresa</th>
                                <th class="px-4 py-2 text-center text-white/30 font-black uppercase">%</th>
                                <th class="px-4 py-2 text-right text-white/30 font-black uppercase">Docs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rankingCumplimiento as $i => $r)
                            @php
                            $color = $r['pct'] >= 80 ? 'bg-emerald-500' : ($r['pct'] >= 50 ? 'bg-amber-500' : 'bg-red-500');
                            $textColor = $r['pct'] >= 80 ? 'text-emerald-300' : ($r['pct'] >= 50 ? 'text-amber-300' : 'text-red-300');
                            @endphp
                            <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                <td class="px-4 py-2.5 text-white/30">{{ $i+1 }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="text-white/90 font-bold leading-tight">{{ Str::limit($r['razon_social'],28) }}</div>
                                    <div class="w-full bg-white/5 rounded-full h-1.5 mt-1">
                                        <div class="{{ $color }} h-1.5 rounded-full transition-all" style="width:{{ $r['pct'] }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-center font-black {{ $textColor }}">{{ $r['pct'] }}%</td>
                                <td class="px-4 py-2.5 text-right text-white/40">{{ $r['total_docs'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-white/20 italic">Sin datos de cumplimiento</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════ --}}
        {{-- FILA 5: DOCS RECHAZADOS + DISTRIBUCIÓN TRAB.  --}}
        {{-- ══════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- Top Docs Rechazados --}}
            <div class="bg-white/5 backdrop-blur rounded-2xl p-5 border border-white/10">
                <div class="text-white/80 text-xs font-black uppercase tracking-widest mb-4">❌ Top Documentos Rechazados</div>
                <div class="space-y-2">
                    @forelse($topDocumentosRechazados as $i => $d)
                    @php $maxR = collect($topDocumentosRechazados)->max('total') ?: 1; $pctR = ($d['total']/$maxR)*100; @endphp
                    <div class="flex items-center gap-3 group">
                        <span class="text-[9px] font-black text-white/20 w-4">{{ $i+1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-0.5">
                                <span class="text-white/80 text-[10px] font-bold truncate">{{ $d['nombre'] }}</span>
                                <span class="text-red-300 text-[10px] font-black ml-2 shrink-0">{{ $d['total'] }}</span>
                            </div>
                            <div class="w-full bg-white/5 rounded-full h-1.5">
                                <div class="bg-gradient-to-r from-red-600 to-orange-500 h-1.5 rounded-full" style="width:{{ $pctR }}%"></div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-white/20 italic text-sm">Sin rechazos registrados</div>
                    @endforelse
                </div>
            </div>

            {{-- Distribución Trabajadores --}}
            <div class="bg-white/5 backdrop-blur rounded-2xl p-5 border border-white/10">
                <div class="text-white/80 text-xs font-black uppercase tracking-widest mb-4">👷 Distribución por Tipo de Contrato</div>
                <div class="relative h-52">
                    <canvas id="chartDistribucion"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- SCRIPTS CHART.JS --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    function dashboardEjecutivo() {
        return {
            charts: {},
            init() {
                this.$nextTick(() => {
                    this.buildCharts();
                });
                window.addEventListener('livewire:navigated', () => this.destroyCharts());
            },

            destroyCharts() {
                Object.values(this.charts).forEach(c => c?.destroy());
                this.charts = {};
            },

            buildCharts() {
                this.destroyCharts();

                const GRID = { color: 'rgba(255,255,255,0.05)' };
                const TICKS = { color: 'rgba(255,255,255,0.4)', font: { size: 9, weight: 'bold' } };
                const baseOpts = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                };

                // ── Clasificación (Horizontal Bar) ──
                const clasData = @json(collect($contingenciasResumen['por_clasificacion'])->take(8));
                const ctx1 = document.getElementById('chartClasificacion');
                if (ctx1 && clasData.length) {
                    this.charts.clas = new Chart(ctx1, {
                        type: 'bar',
                        data: {
                            labels: clasData.map(d => d.clasificacion.substring(0,20)),
                            datasets: [{
                                data: clasData.map(d => d.total),
                                backgroundColor: clasData.map((d,i) => d.subtipo === 'retenible' ? 'rgba(239,68,68,0.8)' : 'rgba(251,146,60,0.8)'),
                                borderRadius: 6,
                                borderSkipped: false,
                            }]
                        },
                        options: { ...baseOpts, indexAxis: 'y',
                            scales: {
                                x: { grid: GRID, ticks: { ...TICKS, callback: v => '$'+v.toLocaleString('es-CL') } },
                                y: { grid: { display: false }, ticks: TICKS }
                            }
                        }
                    });
                }

                // ── Evolución Anual (Line) ──
                const evoData = @json($evolucionContingencias);
                const ctx2 = document.getElementById('chartEvolucion');
                if (ctx2 && evoData.length) {
                    this.charts.evo = new Chart(ctx2, {
                        type: 'line',
                        data: {
                            labels: evoData.map(d => d.anio),
                            datasets: [{
                                data: evoData.map(d => d.total),
                                borderColor: 'rgba(239,68,68,0.9)',
                                backgroundColor: 'rgba(239,68,68,0.1)',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#ef4444',
                                pointRadius: 5,
                                pointHoverRadius: 8,
                                borderWidth: 2,
                            }]
                        },
                        options: { ...baseOpts,
                            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' $'+c.raw.toLocaleString('es-CL') } } },
                            scales: {
                                x: { grid: GRID, ticks: TICKS },
                                y: { grid: GRID, ticks: { ...TICKS, callback: v => '$'+v.toLocaleString('es-CL') } }
                            }
                        }
                    });
                }

                // ── Mensual (Bar) ──
                const menData = @json($contingenciasMensuales);
                const ctx3 = document.getElementById('chartMensual');
                if (ctx3 && menData.length) {
                    this.charts.men = new Chart(ctx3, {
                        type: 'bar',
                        data: {
                            labels: menData.map(d => d.mes),
                            datasets: [{
                                data: menData.map(d => d.total),
                                backgroundColor: menData.map(d => d.total > 0 ? 'rgba(99,102,241,0.8)' : 'rgba(99,102,241,0.15)'),
                                borderRadius: 6,
                                borderSkipped: false,
                            }]
                        },
                        options: { ...baseOpts,
                            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' $'+c.raw.toLocaleString('es-CL') } } },
                            scales: {
                                x: { grid: { display: false }, ticks: TICKS },
                                y: { grid: GRID, ticks: { ...TICKS, callback: v => '$'+v.toLocaleString('es-CL') } }
                            }
                        }
                    });
                }

                // ── Distribución Trabajadores (Doughnut) ──
                const distData = @json($distribucionTrabajadores);
                const ctx4 = document.getElementById('chartDistribucion');
                const COLORS = ['#6366f1','#22c55e','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#ec4899','#14b8a6'];
                if (ctx4 && distData.length) {
                    this.charts.dist = new Chart(ctx4, {
                        type: 'doughnut',
                        data: {
                            labels: distData.map(d => d.nombre),
                            datasets: [{
                                data: distData.map(d => d.cantidad),
                                backgroundColor: COLORS,
                                borderWidth: 2,
                                borderColor: '#0a0f1e',
                                hoverOffset: 8,
                            }]
                        },
                        options: { ...baseOpts,
                            cutout: '65%',
                            plugins: {
                                legend: { display: true, position: 'right', labels: { color: 'rgba(255,255,255,0.6)', font: { size: 9 }, boxWidth: 10, padding: 8 } }
                            }
                        }
                    });
                }
            }
        }
    }

    document.addEventListener('livewire:updated', () => {
        if (typeof dashboardEjecutivo === 'function') {
            // rebuild charts on Livewire re-render
            setTimeout(() => {
                ['chartClasificacion','chartEvolucion','chartMensual','chartDistribucion'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el && Chart.getChart(el)) Chart.getChart(el).destroy();
                });
                window.dispatchEvent(new Event('dashboard:rebuild'));
            }, 100);
        }
    });
    </script>
    @endpush

</div>
