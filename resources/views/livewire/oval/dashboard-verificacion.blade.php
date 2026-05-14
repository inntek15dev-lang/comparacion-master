<div>
    <div class="px-6 py-6 bg-slate-50 dark:bg-gray-900 min-h-screen">
        <!-- Header & Filters -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight uppercase">Dashboard Ejecutivo</h1>
                <p class="text-xs text-slate-500 dark:text-gray-400 font-semibold uppercase tracking-widest mt-1">Servicio de Verificación Laboral</p>
            </div>
            
            <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-sm border border-slate-200 dark:border-gray-700">
                <div class="flex items-center gap-2 px-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <select wire:model.live="principalId" class="border-none bg-transparent text-sm font-bold text-slate-700 dark:text-gray-200 focus:ring-0 py-1 cursor-pointer">
                        <option value="">Seleccione Principal...</option>
                        @foreach($mandantes as $m)
                            <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="h-6 border-r border-slate-200 dark:border-gray-700"></div>
                
                <div class="flex items-center gap-2 px-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <select wire:model.live="anioSeleccionado" class="border-none bg-transparent text-sm font-bold text-slate-700 dark:text-gray-200 focus:ring-0 py-1 cursor-pointer">
                        @for($y = date('Y'); $y >= 2021; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        @if($principalId)
            <!-- Tabs -->
            <div class="flex gap-4 mb-6 border-b border-slate-200 dark:border-gray-700">
                <button wire:click="setTab('empresas')" class="pb-3 text-sm font-bold uppercase tracking-widest transition-colors {{ $tabSeleccionado === 'empresas' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400 dark:border-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-gray-400' }}">
                    Empresas
                </button>
                <button wire:click="setTab('trabajadores')" class="pb-3 text-sm font-bold uppercase tracking-widest transition-colors {{ $tabSeleccionado === 'trabajadores' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400 dark:border-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-gray-400' }}">
                    Trabajadores
                </button>
                <button wire:click="setTab('contingencias')" class="pb-3 text-sm font-bold uppercase tracking-widest transition-colors {{ $tabSeleccionado === 'contingencias' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400 dark:border-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-gray-400' }}">
                    Contingencias
                </button>
            </div>

            <!-- Tab Content: EMPRESAS -->
            <div class="{{ $tabSeleccionado === 'empresas' ? 'block' : 'hidden' }}">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- KPI Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 flex flex-col justify-center items-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-slate-500 dark:text-gray-400 text-xs font-bold uppercase tracking-widest text-center">Promedio Mensual ({{ $anioSeleccionado }})</h3>
                        <p class="text-5xl font-black text-slate-800 dark:text-white mt-2">{{ $datos['empresas']['promedio_anual'] ?? 0 }}</p>
                        <p class="text-xs text-slate-400 mt-2 text-center">Empresas Contratistas activas en promedio por mes</p>
                    </div>
                    
                    <!-- Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 lg:col-span-2">
                        <h3 class="text-slate-800 dark:text-white text-sm font-bold uppercase tracking-widest mb-4">Evolución de Contratistas ({{ $anioSeleccionado }})</h3>
                        <div class="relative h-64" wire:ignore>
                            <canvas id="chartEmpresas"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: TRABAJADORES -->
            <div class="{{ $tabSeleccionado === 'trabajadores' ? 'block' : 'hidden' }}">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- KPI Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 flex flex-col justify-center items-center">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-slate-500 dark:text-gray-400 text-xs font-bold uppercase tracking-widest text-center">Dotación Promedio ({{ $anioSeleccionado }})</h3>
                        <p class="text-5xl font-black text-slate-800 dark:text-white mt-2">{{ number_format($datos['trabajadores']['promedio_anual'] ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs text-slate-400 mt-2 text-center">Trabajadores evaluados en promedio por mes</p>
                    </div>
                    
                    <!-- Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 lg:col-span-2">
                        <h3 class="text-slate-800 dark:text-white text-sm font-bold uppercase tracking-widest mb-4">Evolución de Dotación ({{ $anioSeleccionado }})</h3>
                        <div class="relative h-64" wire:ignore>
                            <canvas id="chartTrabajadores"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: CONTINGENCIAS -->
            <div class="{{ $tabSeleccionado === 'contingencias' ? 'block' : 'hidden' }}">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                        <h3 class="text-slate-800 dark:text-white text-sm font-bold uppercase tracking-widest mb-4">Evolución de Contingencias</h3>
                        <div class="relative h-64 flex items-center justify-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                            <span class="text-gray-400 text-sm font-bold">Gráfico de Contingencias en construcción...</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                        <h3 class="text-slate-800 dark:text-white text-sm font-bold uppercase tracking-widest mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Top Riesgo Pareto
                        </h3>
                        <div class="relative h-64 flex items-center justify-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                            <span class="text-gray-400 text-sm font-bold">Matriz de Riesgo en construcción...</span>
                        </div>
                    </div>
                </div>
            </div>

        @else
            <div class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700">
                <svg class="w-16 h-16 text-slate-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <h2 class="text-xl font-bold text-slate-700 dark:text-gray-300">Seleccione una Empresa Principal</h2>
                <p class="text-sm text-slate-500 dark:text-gray-400 mt-2 text-center max-w-md">Para visualizar el Dashboard de Verificación, es necesario seleccionar primero la empresa Principal que desea analizar.</p>
            </div>
        @endif
    </div>

    <!-- Chart.js Injection -->
    @if($principalId)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            let chartE, chartT;

            const initCharts = () => {
                const data = @this.get('datos');
                if(!data) return;

                // Chart Empresas
                const ctxE = document.getElementById('chartEmpresas');
                if (ctxE) {
                    if (chartE) chartE.destroy();
                    chartE = new Chart(ctxE, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Empresas Activas',
                                data: data.empresas.historico,
                                backgroundColor: '#3b82f6', // blue-600
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { borderDash: [2,4], color: '#e2e8f0' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }

                // Chart Trabajadores
                const ctxT = document.getElementById('chartTrabajadores');
                if (ctxT) {
                    if (chartT) chartT.destroy();
                    chartT = new Chart(ctxT, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Trabajadores Evaluados',
                                data: data.trabajadores.historico,
                                borderColor: '#10b981', // emerald-500
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#10b981',
                                pointBorderWidth: 2,
                                pointRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { borderDash: [2,4], color: '#e2e8f0' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }
            };

            initCharts();

            @this.on('filtros-actualizados', () => {
                setTimeout(initCharts, 100);
            });
            @this.on('tab-cambiada', () => {
                setTimeout(initCharts, 100);
            });
        });
    </script>
    @endif
</div>
