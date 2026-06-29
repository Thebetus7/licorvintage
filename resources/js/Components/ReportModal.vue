<script setup>
import { ref } from 'vue';

const props = defineProps({
    module: {
        type: String,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const isOpen = ref(false);
const selectedFormat = ref('html'); // 'html' | 'pdf' | 'excel'

const getModuleTitle = () => {
    const titles = {
        productos: 'Reporte de Productos',
        compras: 'Reporte de Compras',
        cajas: 'Reporte de Historial de Cajas',
        creditos: 'Reporte de Créditos Vigentes',
        promociones: 'Reporte de Promociones',
        inventario_movimientos: 'Reporte de Movimientos',
        inventario_kardex: 'Kardex de Inventario',
        inventario_lotes: 'Reporte de Lotes',
        inventario_conteos: 'Reporte de Conteos',
        usuarios: 'Reporte de Usuarios',
        seguridad_bitacora: 'Bitácora de Auditoría',
        seguridad_recursos: 'Estadísticas de Recursos'
    };
    return titles[props.module] || 'Reporte del Sistema';
};

const submitReport = () => {
    // Construir parámetros de consulta a partir del prop filters y el formato
    const queryParams = {
        format: selectedFormat.value,
        ...props.filters
    };

    // Filtrar valores vacíos, nulos o indefinidos
    const cleanParams = {};
    Object.keys(queryParams).forEach(key => {
        if (queryParams[key] !== '' && queryParams[key] !== null && queryParams[key] !== undefined) {
            cleanParams[key] = queryParams[key];
        }
    });

    // Abrir reporte en una nueva pestaña
    const url = route('reports.export', { module: props.module }) + '?' + new URLSearchParams(cleanParams).toString();
    window.open(url, '_blank');
    isOpen.value = false;
};
</script>

<template>
    <div>
        <!-- Botón para abrir el Modal (Modelo Unificado de Botón Secundario) -->
        <button
            type="button"
            @click="isOpen = true"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-[var(--bg-secondary)]/50 border border-[var(--border-color)] hover:bg-[var(--bg-secondary)] hover:border-[var(--accent)] hover:text-[var(--accent)] rounded-xl font-semibold text-xs text-[var(--text-primary)] uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:ring-offset-2 focus:ring-offset-[var(--bg-primary)] transition ease-in-out duration-150 cursor-pointer"
        >
            <svg class="w-4 h-4 text-[var(--accent)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <span>Reporte</span>
        </button>

        <!-- Modal de Reportes Premium (Teleport a body para evitar problemas de stacking context) -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/65 backdrop-blur-sm"
            >
                <div
                    class="w-full max-w-sm overflow-hidden rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)] p-6 shadow-2xl backdrop-blur-md text-[var(--text-primary)]"
                >
                    <!-- Cabecera -->
                    <div class="flex items-center justify-between border-b border-[var(--border-color)] pb-4 mb-6">
                        <h3 class="text-sm font-bold text-[var(--text-primary)] flex items-center gap-2">
                            <svg class="w-4 h-4 text-[var(--accent)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801-12h5.801" />
                            </svg>
                            {{ getModuleTitle() }}
                        </h3>
                        <button
                            @click="isOpen = false"
                            class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition cursor-pointer text-xs"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Formato de Exportación (Sin iconos, totalmente integrados con el tema) -->
                    <div class="mb-6">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[var(--text-secondary)] mb-3">
                            Seleccionar Formato
                        </h4>
                        <div class="flex flex-col gap-2">
                            <!-- 1. HTML -->
                            <button
                                type="button"
                                @click="selectedFormat = 'html'"
                                class="flex items-center justify-center py-2.5 px-4 rounded-xl border font-bold text-xs transition-all duration-200 cursor-pointer text-center w-full"
                                :class="selectedFormat === 'html' ? 'border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)] shadow-md' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 text-[var(--text-secondary)] hover:bg-[var(--bg-primary)] hover:text-[var(--text-primary)]'"
                            >
                                <span>HTML IMPRIMIR</span>
                            </button>

                            <!-- 2. PDF -->
                            <button
                                type="button"
                                @click="selectedFormat = 'pdf'"
                                class="flex items-center justify-center py-2.5 px-4 rounded-xl border font-bold text-xs transition-all duration-200 cursor-pointer text-center w-full"
                                :class="selectedFormat === 'pdf' ? 'border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)] shadow-md' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 text-[var(--text-secondary)] hover:bg-[var(--bg-primary)] hover:text-[var(--text-primary)]'"
                            >
                                <span>DOCUMENTO PDF</span>
                            </button>

                            <!-- 3. EXCEL -->
                            <button
                                type="button"
                                @click="selectedFormat = 'excel'"
                                class="flex items-center justify-center py-2.5 px-4 rounded-xl border font-bold text-xs transition-all duration-200 cursor-pointer text-center w-full"
                                :class="selectedFormat === 'excel' ? 'border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)] shadow-md' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 text-[var(--text-secondary)] hover:bg-[var(--bg-primary)] hover:text-[var(--text-primary)]'"
                            >
                                <span>HOJA EXCEL</span>
                            </button>
                        </div>
                    </div>

                    <!-- Botones Accionadores (Modelo Unificado de Botón Primario y Secundario) -->
                    <div class="flex gap-2 mt-6 justify-end">
                        <button
                            type="button"
                            @click="isOpen = false"
                            class="inline-flex items-center justify-center px-4 py-2 bg-[var(--bg-secondary)]/50 border border-[var(--border-color)] hover:bg-[var(--bg-secondary)] hover:border-[var(--accent)] hover:text-[var(--accent)] rounded-xl font-semibold text-xs text-[var(--text-primary)] uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:ring-offset-2 focus:ring-offset-[var(--bg-primary)] transition ease-in-out duration-150 cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="submitReport"
                            class="inline-flex items-center justify-center px-5 py-2 bg-[var(--accent)] hover:bg-[var(--accent-hover)] border border-transparent rounded-xl font-semibold text-xs text-[var(--bg-primary)] uppercase tracking-widest active:opacity-80 focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:ring-offset-2 focus:ring-offset-[var(--bg-primary)] transition ease-in-out duration-150 shadow-md shadow-[var(--accent)]/10 cursor-pointer"
                        >
                            Generar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
