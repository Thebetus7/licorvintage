<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    is_propietario: {
        type: Boolean,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    vendedores: {
        type: Array,
        default: () => [],
    },
    kpis: {
        type: Object,
        required: true,
    },
    chart_tendencia: {
        type: Object,
        required: true,
    },
    chart_productos: {
        type: Array,
        required: true,
    },
    chart_vendedores: {
        type: Array,
        default: () => [],
    },
    chart_dias: {
        type: Object,
        required: true,
    },
    paginas_visitadas: {
        type: Array,
        default: () => [],
    },
    recursos_accedidos: {
        type: Array,
        default: () => [],
    },
    vendedores_stats: {
        type: Array,
        default: () => [],
    }
});

// --- PESTAÑAS (Submódulos) ---
const activeTab = ref('estadisticas'); // 'estadisticas' | 'trafico' | 'empleados'

// --- FILTROS REACTIVOS ---
const filterYear = ref(props.filters.year);
const filterMonth = ref(props.filters.month);
const filterVendedor = ref(props.filters.vendedor_id);

const months = [
    { value: 'all', label: 'Todo el Año' },
    { value: 1, label: 'Enero' },
    { value: 2, label: 'Febrero' },
    { value: 3, label: 'Marzo' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Mayo' },
    { value: 6, label: 'Junio' },
    { value: 7, label: 'Julio' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Septiembre' },
    { value: 10, label: 'Octubre' },
    { value: 11, label: 'Noviembre' },
    { value: 12, label: 'Diciembre' }
];

const years = [2026, 2025, 2024];

// --- ESTADOS LOCALES PARA ACTUALIZACIÓN INSTANTÁNEA (SIN RECARGAR PÁGINA) ---
const localKpis = ref(props.kpis);
const localChartTendencia = ref(props.chart_tendencia);
const localChartProductos = ref(props.chart_productos);
const localChartVendedores = ref(props.chart_vendedores);
const localChartDias = ref(props.chart_dias);
const localVendedoresStats = ref(props.vendedores_stats);

const loading = ref(false);
const chartKey = ref(0);

const applyFilters = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('dashboard'), {
            params: {
                year: filterYear.value,
                month: filterMonth.value,
                vendedor_id: filterVendedor.value,
                json: true
            }
        });
        
        localKpis.value = response.data.kpis;
        localChartTendencia.value = response.data.chart_tendencia;
        localChartProductos.value = response.data.chart_productos;
        localChartVendedores.value = response.data.chart_vendedores;
        localChartDias.value = response.data.chart_dias;
        localVendedoresStats.value = response.data.vendedores_stats;
        
        // Incrementar key para forzar el redibujado de los gráficos
        chartKey.value++;
    } catch (error) {
        console.error('Error al filtrar las estadísticas:', error);
    } finally {
        loading.value = false;
    }
};

// --- SOPORTE MULTI-TEMA DILIGENTE PARA LOS GRÁFICOS ---
const isLightMode = ref(false);

onMounted(() => {
    isLightMode.value = document.documentElement.classList.contains('theme-light');
    
    const observer = new MutationObserver(() => {
        isLightMode.value = document.documentElement.classList.contains('theme-light');
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    onUnmounted(() => {
        observer.disconnect();
    });
});

const textColor = computed(() => isLightMode.value ? '#374151' : '#a3a3a3'); // gris oscuro en claro, gris claro en oscuro
const gridBorderColor = computed(() => isLightMode.value ? 'rgba(0, 0, 0, 0.08)' : 'rgba(255, 255, 255, 0.05)');
const tooltipTheme = computed(() => isLightMode.value ? 'light' : 'dark');

// --- CONFIGURACIÓN DE APEXCHARTS (ESTILO POWER BI / ADAPTATIVO) ---

// 1. Gráfico de Tendencia de Ventas (Área)
const tendenciaChartOptions = computed(() => ({
    chart: {
        id: 'tendencia-ventas',
        type: 'area',
        background: 'transparent',
        foreColor: textColor.value,
        toolbar: { show: false },
        zoom: { enabled: false }
    },
    colors: ['#f59e0b'], // Ámbar / Dorado
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.05,
            stops: [0, 95]
        }
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    grid: {
        borderColor: gridBorderColor.value,
        strokeDashArray: 4,
        padding: { left: 15, right: 15 }
    },
    xaxis: {
        categories: localChartTendencia.value.labels,
        labels: {
            style: { fontSize: '10px', fontWeight: 500, colors: textColor.value }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: {
            style: { fontSize: '10px', fontWeight: 500, colors: textColor.value },
            formatter: (val) => `${val.toLocaleString('es-BO')} Bs`
        }
    },
    tooltip: {
        theme: tooltipTheme.value,
        x: { show: true },
        y: {
            formatter: (val) => `<strong>${val.toFixed(2)} Bs</strong>`
        }
    }
}));

const tendenciaChartSeries = computed(() => [{
    name: 'Ventas',
    data: localChartTendencia.value.values
}]);

// 2. Gráfico de Top 5 Productos (Barras Horizontales)
const productosChartOptions = computed(() => ({
    chart: {
        id: 'top-productos',
        type: 'bar',
        background: 'transparent',
        foreColor: textColor.value,
        toolbar: { show: false }
    },
    plotOptions: {
        bar: {
            horizontal: true,
            barHeight: '55%',
            borderRadius: 6,
            distributed: true
        }
    },
    colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'], // Paleta Power BI
    grid: {
        borderColor: gridBorderColor.value,
        strokeDashArray: 4
    },
    xaxis: {
        categories: localChartProductos.value.map(p => p.name),
        labels: {
            style: { fontSize: '10px', fontWeight: 500, colors: textColor.value }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: {
            style: { fontSize: '10px', fontWeight: 600, colors: textColor.value }
        }
    },
    legend: { show: false },
    tooltip: {
        theme: tooltipTheme.value,
        y: {
            formatter: (val) => `<strong>${val} unidades</strong>`
        }
    }
}));

const productosChartSeries = computed(() => [{
    name: 'Unidades Vendidas',
    data: localChartProductos.value.map(p => p.value)
}]);

// 3. Gráfico de Ventas por Vendedor (Dona - Solo Propietario)
const vendedoresChartOptions = computed(() => ({
    chart: {
        id: 'ventas-vendedor',
        type: 'donut',
        background: 'transparent',
        foreColor: textColor.value
    },
    labels: localChartVendedores.value.map(v => v.name),
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
    stroke: { show: false },
    legend: {
        position: 'bottom',
        fontSize: '11px',
        labels: { colors: textColor.value },
        markers: { radius: 12 }
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '11px', fontWeight: 'bold' },
        dropShadow: { enabled: false }
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    name: { show: true, fontSize: '12px', color: textColor.value },
                    value: {
                        show: true,
                        fontSize: '16px',
                        fontWeight: 'bold',
                        color: isLightMode.value ? '#111827' : '#ffffff',
                        formatter: (val) => `${parseFloat(val).toFixed(2)} Bs`
                    },
                    total: {
                        show: true,
                        label: 'Total Ventas',
                        color: textColor.value,
                        formatter: (w) => {
                            const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            return `${total.toFixed(2)} Bs`;
                        }
                    }
                }
            }
        }
    },
    tooltip: {
        theme: tooltipTheme.value,
        y: {
            formatter: (val) => `<strong>${val.toFixed(2)} Bs</strong>`
        }
    }
}));

const vendedoresChartSeries = computed(() => localChartVendedores.value.map(v => v.value));

// 4. Gráfico de Ventas por Día de la Semana (Columnas)
const diasChartOptions = computed(() => ({
    chart: {
        id: 'ventas-dias',
        type: 'bar',
        background: 'transparent',
        foreColor: textColor.value,
        toolbar: { show: false }
    },
    plotOptions: {
        bar: {
            columnWidth: '40%',
            borderRadius: 6,
            dataLabels: { position: 'top' }
        }
    },
    colors: ['#6366f1'], // Indigo
    grid: {
        borderColor: gridBorderColor.value,
        strokeDashArray: 4
    },
    xaxis: {
        categories: localChartDias.value.labels,
        labels: {
            style: { fontSize: '10px', fontWeight: 500, colors: textColor.value }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: {
            style: { fontSize: '10px', fontWeight: 500, colors: textColor.value },
            formatter: (val) => `${val.toLocaleString('es-BO')} Bs`
        }
    },
    dataLabels: {
        enabled: true,
        formatter: (val) => val > 0 ? `${val.toFixed(0)}` : '',
        offsetY: -20,
        style: { fontSize: '10px', colors: [isLightMode.value ? '#374151' : '#ffffff'], fontWeight: 'bold' }
    },
    tooltip: {
        theme: tooltipTheme.value,
        y: {
            formatter: (val) => `<strong>${val.toFixed(2)} Bs</strong>`
        }
    }
}));

const diasChartSeries = computed(() => [{
    name: 'Ventas Totales',
    data: localChartDias.value.values
}]);
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-[var(--text-primary)] leading-tight tracking-wide">
                        {{ is_propietario ? 'Resumen Ejecutivo' : 'Mi Panel de Ventas' }}
                    </h2>
                    <p class="text-sm text-[var(--text-secondary)]">
                        {{ is_propietario ? 'Monitorea el rendimiento comercial del negocio en tiempo real.' : 'Visualiza tus estadísticas de ventas y rendimiento laboral.' }}
                    </p>
                </div>
                <div class="text-xs bg-white/5 border border-white/10 px-3 py-1.5 rounded-full text-[var(--text-secondary)] font-medium">
                    Actualizado: {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 pb-12">
            
            <!-- Barra de Pestañas / Submódulos (Solo visible para Propietario) -->
            <div v-if="is_propietario" class="flex border-b border-[var(--border-color)] gap-6">
                <button
                    type="button"
                    @click="activeTab = 'estadisticas'"
                    class="pb-4 text-sm font-semibold transition-all duration-200 relative cursor-pointer"
                    :class="activeTab === 'estadisticas' ? 'text-[var(--accent)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                >
                    Estadísticas Comerciales
                    <span v-if="activeTab === 'estadisticas'" class="absolute bottom-0 left-0 w-full h-0.5 bg-[var(--accent)] rounded-full" />
                </button>
                <button
                    type="button"
                    @click="activeTab = 'empleados'"
                    class="pb-4 text-sm font-semibold transition-all duration-200 relative cursor-pointer"
                    :class="activeTab === 'empleados' ? 'text-[var(--accent)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                >
                    Rendimiento de Empleados
                    <span v-if="activeTab === 'empleados'" class="absolute bottom-0 left-0 w-full h-0.5 bg-[var(--accent)] rounded-full" />
                </button>
                <button
                    type="button"
                    @click="activeTab = 'trafico'"
                    class="pb-4 text-sm font-semibold transition-all duration-200 relative cursor-pointer"
                    :class="activeTab === 'trafico' ? 'text-[var(--accent)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                >
                    Tráfico y Auditoría
                    <span v-if="activeTab === 'trafico'" class="absolute bottom-0 left-0 w-full h-0.5 bg-[var(--accent)] rounded-full" />
                </button>
            </div>

            <!-- SECCIÓN 1: ESTADÍSTICAS COMERCIALES (Tab 1) -->
            <div v-if="activeTab === 'estadisticas'" class="space-y-6">
                
                <!-- Faja de Filtros Estilo Power BI -->
                <div class="flex flex-wrap items-center gap-4 bg-[var(--bg-secondary)]/30 border border-white/5 rounded-2xl p-4 backdrop-blur-md relative">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--text-secondary)]">Filtros:</span>
                    </div>

                    <!-- Selector de Año -->
                    <div class="flex flex-col gap-1">
                        <select
                            v-model="filterYear"
                            @change="applyFilters"
                            :disabled="loading"
                            class="bg-[var(--bg-tertiary)] border border-[var(--border-color)] text-xs rounded-lg text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)] block w-28 p-2 disabled:opacity-50"
                        >
                            <option v-for="y in years" :key="y" :value="y" class="bg-[var(--bg-tertiary)] text-[var(--text-primary)]">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Selector de Mes -->
                    <div class="flex flex-col gap-1">
                        <select
                            v-model="filterMonth"
                            @change="applyFilters"
                            :disabled="loading"
                            class="bg-[var(--bg-tertiary)] border border-[var(--border-color)] text-xs rounded-lg text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)] block w-36 p-2 disabled:opacity-50"
                        >
                            <option v-for="m in months" :key="m.value" :value="m.value" class="bg-[var(--bg-tertiary)] text-[var(--text-primary)]">{{ m.label }}</option>
                        </select>
                    </div>

                    <!-- Selector de Vendedor (Solo visible para Propietario) -->
                    <div v-if="is_propietario" class="flex flex-col gap-1">
                        <select
                            v-model="filterVendedor"
                            @change="applyFilters"
                            :disabled="loading"
                            class="bg-[var(--bg-tertiary)] border border-[var(--border-color)] text-xs rounded-lg text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)] block w-44 p-2 disabled:opacity-50"
                        >
                            <option :value="null" class="bg-[var(--bg-tertiary)] text-[var(--text-primary)]">Todos los Vendedores</option>
                            <option v-for="v in vendedores" :key="v.id" :value="v.id" class="bg-[var(--bg-tertiary)] text-[var(--text-primary)]">{{ v.name }}</option>
                        </select>
                    </div>

                    <!-- Indicador de Carga -->
                    <div v-if="loading" class="absolute right-4 flex items-center gap-2 text-xs text-[var(--accent)] font-semibold animate-pulse">
                        <svg class="animate-spin h-4 w-4 text-[var(--accent)]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Actualizando...
                    </div>
                </div>

                <!-- Tarjetas de KPIs Comerciales -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Tarjeta 1: Ventas Totales -->
                    <div class="relative group rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-5 shadow-lg backdrop-blur-md">
                        <div class="absolute top-4 right-4 h-9 w-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.214.139a4.865 4.865 0 005.572 0 4.865 4.865 0 000-6.79a4.865 4.865 0 00-5.572 0l-.214.138A4.865 4.865 0 009 13.5H9z" />
                            </svg>
                        </div>
                        <div class="text-[10px] font-bold text-[var(--text-secondary)] uppercase tracking-wider">
                            {{ is_propietario ? 'Ventas Totales' : 'Mis Ventas Totales' }}
                        </div>
                        <div class="mt-3 flex items-baseline gap-1">
                            <span class="text-2xl font-extrabold text-[var(--text-primary)]">{{ localKpis.ventas_totales.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                            <span class="text-xs font-semibold text-amber-400">Bs</span>
                        </div>
                        <div class="mt-1.5 text-[10px] text-[var(--text-secondary)]">En el período seleccionado</div>
                    </div>

                    <!-- Tarjeta 2: Compras Totales (O Mi Rol si es Vendedor) -->
                    <div class="relative group rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-5 shadow-lg backdrop-blur-md">
                        <div v-if="is_propietario">
                            <div class="absolute top-4 right-4 h-9 w-9 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400">
                                <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                </svg>
                            </div>
                            <div class="text-[10px] font-bold text-[var(--text-secondary)] uppercase tracking-wider">Inversión (Compras)</div>
                            <div class="mt-3 flex items-baseline gap-1">
                                <span class="text-2xl font-extrabold text-[var(--text-primary)]">{{ localKpis.compras_totales.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                <span class="text-xs font-semibold text-red-400">Bs</span>
                            </div>
                            <div class="mt-1.5 text-[10px] text-[var(--text-secondary)]">Abastecimiento acumulado</div>
                        </div>
                        <div v-else>
                            <div class="absolute top-4 right-4 h-9 w-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                                <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </div>
                            <div class="text-[10px] font-bold text-[var(--text-secondary)] uppercase tracking-wider">Estado Laboral</div>
                            <div class="mt-3 flex items-baseline">
                                <span class="text-xl font-extrabold text-indigo-300">Vendedor Activo</span>
                            </div>
                            <div class="mt-2.5 text-[10px] text-[var(--text-secondary)]">Ventas personales registradas</div>
                        </div>
                    </div>

                    <!-- Tarjeta 3: Cantidad de Ventas -->
                    <div class="relative group rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-5 shadow-lg backdrop-blur-md">
                        <div class="absolute top-4 right-4 h-9 w-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801-12h5.801M3 6.75h.008v.008H3V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3 12h.008v.008H3V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.008v.008H3v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                        </div>
                        <div class="text-[10px] font-bold text-[var(--text-secondary)] uppercase tracking-wider">
                            {{ is_propietario ? 'Transacciones' : 'Mis Transacciones' }}
                        </div>
                        <div class="mt-3 flex items-baseline gap-1">
                            <span class="text-2xl font-extrabold text-[var(--text-primary)]">{{ localKpis.transacciones }}</span>
                            <span class="text-xs font-semibold text-emerald-400">ventas</span>
                        </div>
                        <div class="mt-1.5 text-[10px] text-[var(--text-secondary)]">Facturas emitidas</div>
                    </div>

                    <!-- Tarjeta 4: Ticket Promedio -->
                    <div class="relative group rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-5 shadow-lg backdrop-blur-md">
                        <div class="absolute top-4 right-4 h-9 w-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                            </svg>
                        </div>
                        <div class="text-[10px] font-bold text-[var(--text-secondary)] uppercase tracking-wider">Ticket Promedio</div>
                        <div class="mt-3 flex items-baseline gap-1">
                            <span class="text-2xl font-extrabold text-[var(--text-primary)]">{{ localKpis.ticket_promedio.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                            <span class="text-xs font-semibold text-indigo-400">Bs</span>
                        </div>
                        <div class="mt-1.5 text-[10px] text-[var(--text-secondary)]">Monto promedio por venta</div>
                    </div>
                </div>

                <!-- Gráficos Principales -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Gráfico 1: Tendencia de Ventas (Línea / Área) -->
                    <div class="rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                        <div class="mb-4">
                            <h3 class="text-base font-bold text-[var(--text-primary)]">Tendencia de Ventas</h3>
                            <p class="text-xs text-[var(--text-secondary)]">
                                {{ month === 'all' ? 'Ingresos mensuales a lo largo del año.' : 'Evolución diaria de ventas en el mes seleccionado.' }}
                            </p>
                        </div>
                        <div class="h-80">
                            <apexchart
                                :key="`tendencia-${chartKey}-${isLightMode}`"
                                type="area"
                                height="100%"
                                :options="tendenciaChartOptions"
                                :series="tendenciaChartSeries"
                            />
                        </div>
                    </div>
 
                    <!-- Gráfico 2: Top 5 Productos Más Vendidos -->
                    <div class="rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                        <div class="mb-4">
                            <h3 class="text-base font-bold text-[var(--text-primary)]">Top 5 Licores Más Vendidos</h3>
                            <p class="text-xs text-[var(--text-secondary)]">Productos preferidos por volumen de venta.</p>
                        </div>
                        <div v-if="localChartProductos.length === 0" class="h-80 flex items-center justify-center text-xs text-[var(--text-secondary)]">
                            Sin datos de ventas en este período.
                        </div>
                        <div v-else class="h-80">
                            <apexchart
                                :key="`productos-${chartKey}-${isLightMode}`"
                                type="bar"
                                height="100%"
                                :options="productosChartOptions"
                                :series="productosChartSeries"
                            />
                        </div>
                    </div>
 
                    <!-- Gráfico 3: Ventas por Vendedor (Solo visible para Propietario) -->
                    <div v-if="is_propietario" class="rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                        <div class="mb-4">
                            <h3 class="text-base font-bold text-[var(--text-primary)]">Ventas por Trabajador</h3>
                            <p class="text-xs text-[var(--text-secondary)]">Distribución de facturación generada por vendedor.</p>
                        </div>
                        <div v-if="localChartVendedores.length === 0" class="h-80 flex items-center justify-center text-xs text-[var(--text-secondary)]">
                            Sin datos de vendedores.
                        </div>
                        <div v-else class="h-80 flex flex-col justify-center">
                            <apexchart
                                :key="`vendedores-${chartKey}-${isLightMode}`"
                                type="donut"
                                height="270"
                                :options="vendedoresChartOptions"
                                :series="vendedoresChartSeries"
                            />
                        </div>
                    </div>
 
                    <!-- Gráfico 4: Ventas por Día de la Semana -->
                    <div class="rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md" :class="{ 'md:col-span-2': !is_propietario }">
                        <div class="mb-4">
                            <h3 class="text-base font-bold text-[var(--text-primary)]">Rendimiento por Día de la Semana</h3>
                            <p class="text-xs text-[var(--text-secondary)]">Identifica qué días de la semana son los de mayor afluencia comercial.</p>
                        </div>
                        <div class="h-80">
                            <apexchart
                                :key="`dias-${chartKey}-${isLightMode}`"
                                type="bar"
                                height="100%"
                                :options="diasChartOptions"
                                :series="diasChartSeries"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: RENDIMIENTO DE EMPLEADOS (Tab 3 - Solo Propietario) -->
            <div v-if="activeTab === 'empleados' && is_propietario" class="space-y-6">
                <div class="rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-[var(--text-primary)]">Rendimiento de Empleados / Vendedores</h3>
                        <p class="text-xs text-[var(--text-secondary)]">Métricas clave de desempeño de ventas por cada miembro del equipo en el período seleccionado.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-[var(--text-primary)]">
                            <thead>
                                <tr class="border-b border-[var(--border-color)] text-[var(--text-secondary)] text-left">
                                    <th class="py-3 font-semibold">Vendedor</th>
                                    <th class="py-3 font-semibold">Contacto</th>
                                    <th class="py-3 font-semibold text-right">Ventas Totales</th>
                                    <th class="py-3 font-semibold text-center">Transacciones</th>
                                    <th class="py-3 font-semibold text-right">Ticket Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="v in localVendedoresStats"
                                    :key="v.id"
                                    class="border-b border-[var(--border-color)]/50 hover:bg-white/5 transition-colors duration-150"
                                >
                                    <td class="py-3 font-medium flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-[var(--accent)]/10 text-[var(--accent)] flex items-center justify-center font-bold text-xs uppercase">
                                            {{ v.name.substring(0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold">{{ v.name }}</p>
                                            <p class="text-[10px] text-[var(--text-secondary)]">ID: #{{ v.id }}</p>
                                        </div>
                                    </td>
                                    <td class="py-3 text-[var(--text-secondary)] text-xs">
                                        <p>{{ v.email }}</p>
                                        <p>Telf: {{ v.phone }}</p>
                                    </td>
                                    <td class="py-3 text-right font-semibold font-mono text-[var(--accent)]">
                                        Bs {{ v.total_ventas.toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                                    </td>
                                    <td class="py-3 text-center font-medium">
                                        <span class="bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full text-xs">
                                            {{ v.transacciones }} ventas
                                        </span>
                                    </td>
                                    <td class="py-3 text-right font-mono text-[var(--text-secondary)]">
                                        Bs {{ v.ticket_promedio.toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                                    </td>
                                </tr>
                                <tr v-if="localVendedoresStats.length === 0">
                                    <td colspan="5" class="py-8 text-center text-[var(--text-secondary)]">
                                        No hay ventas registradas para ningún vendedor en este período.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: TRÁFICO Y AUDITORÍA (Tab 2 - Solo Propietario) -->
            <div v-if="activeTab === 'trafico' && is_propietario" class="grid gap-6 md:grid-cols-2">
                <!-- Visitas por Páginas -->
                <div class="rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-[var(--text-primary)]">Tráfico por Página</h3>
                            <p class="text-xs text-[var(--text-secondary)]">Contador de vistas globales individuales.</p>
                        </div>
                        <span class="text-xs bg-indigo-500/10 border border-indigo-500/25 text-indigo-300 px-3 py-1 rounded-full font-medium">
                            Vistas
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div v-if="paginas_visitadas.length === 0" class="text-center py-8 text-[var(--text-secondary)] text-sm">
                            No hay datos de visitas registrados.
                        </div>
                        <div
                            v-for="(page, index) in paginas_visitadas"
                            :key="page.id"
                            class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5 hover:bg-white/10 transition-colors duration-200"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex-shrink-0 h-6 w-6 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-xs font-bold text-indigo-400">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-mono text-xs text-[var(--text-primary)] truncate" :title="page.url_path">
                                    {{ page.url_path }}
                                </span>
                            </div>
                            <span class="font-bold text-sm bg-white/10 border border-white/15 px-3 py-1 rounded-full text-[var(--text-primary)] flex-shrink-0">
                                {{ page.views_count }} vistas
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recursos Más Accedidos -->
                <div class="rounded-2xl border border-white/5 bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-[var(--text-primary)]">Recursos Más Accedidos</h3>
                            <p class="text-xs text-[var(--text-secondary)]">Accesos validados por bitácora de auditoría.</p>
                        </div>
                        <span class="text-xs bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 px-3 py-1 rounded-full font-medium">
                            Bitácora
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div v-if="recursos_accedidos.length === 0" class="text-center py-8 text-[var(--text-secondary)] text-sm">
                            Sin registros de accesos a recursos.
                        </div>
                        <div
                            v-for="(resource, index) in recursos_accedidos"
                            :key="resource.resource_name"
                            class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5 hover:bg-white/10 transition-colors duration-200"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex-shrink-0 h-6 w-6 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-xs font-bold text-emerald-400">
                                    {{ index + 1 }}
                                </span>
                                <span class="text-xs text-[var(--text-primary)] font-semibold truncate">
                                    {{ resource.resource_name }}
                                </span>
                            </div>
                            <span class="font-bold text-xs bg-emerald-500/10 border border-emerald-500/25 px-3 py-1 rounded-full text-emerald-300 flex-shrink-0">
                                {{ resource.total }} entradas
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
