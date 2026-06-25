<script setup>
import { computed } from 'vue';

const props = defineProps({
    mostVisitedResources: {
        type: Array,
        required: true,
    },
    activityLogs: {
        type: Object,
        required: true,
    },
});

// Encontrar el valor máximo de visitas para calcular los porcentajes de las barras
const maxVisits = computed(() => {
    if (props.mostVisitedResources.length === 0) return 1;
    return Math.max(...props.mostVisitedResources.map(r => r.total));
});

// Filtrar las últimas 5 visitas a recursos de la bitácora
const recentVisits = computed(() => {
    return props.activityLogs.data
        .filter(log => log.event_type === 'resource_access')
        .slice(0, 5);
});

const getPercentage = (total) => {
    return Math.round((total / maxVisits.value) * 100);
};

const formatDateShort = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) + ' - ' + d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
};
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Ranking de Módulos Más Visitados -->
        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/40 p-6 shadow-xl backdrop-blur-md transition-colors duration-300">
            <div class="mb-6">
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Ranking: Recursos Más Accedidos</h3>
                <p class="text-xs text-[var(--text-secondary)] mt-1">Estadística acumulada de visitas por módulo o sección de la aplicación.</p>
            </div>

            <div class="space-y-5" v-if="mostVisitedResources.length > 0">
                <div 
                    v-for="(item, index) in mostVisitedResources" 
                    :key="index"
                    class="space-y-2"
                >
                    <div class="flex items-center justify-between text-sm font-semibold">
                        <span class="text-[var(--text-primary)] flex items-center gap-2">
                            <span class="text-xs font-mono h-5 w-5 bg-stone-900 border border-[var(--border-color)] rounded flex items-center justify-center text-[var(--accent)]">
                                {{ index + 1 }}
                            </span>
                            {{ item.resource_name }}
                        </span>
                        <span class="text-[var(--accent)] font-mono">{{ item.total }} visitas</span>
                    </div>
                    
                    <!-- Barra de Progreso Grafica -->
                    <div class="h-3 w-full bg-stone-950/40 rounded-full overflow-hidden border border-[var(--border-color)]">
                        <div 
                            class="h-full bg-gradient-to-r from-[var(--accent)] to-[var(--accent-hover)] rounded-full transition-all duration-1000 ease-out"
                            :style="{ width: `${getPercentage(item.total)}%` }"
                        ></div>
                    </div>
                </div>
            </div>
            
            <div 
                v-else 
                class="h-60 flex flex-col items-center justify-center text-stone-500 text-sm"
            >
                📊 No hay datos de visitas registrados para mostrar.
            </div>
        </div>

        <!-- Flujo Reciente de Visitas -->
        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/40 p-6 shadow-xl backdrop-blur-md transition-colors duration-300">
            <div class="mb-6">
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Accesos Recientes en Tiempo Real</h3>
                <p class="text-xs text-[var(--text-secondary)] mt-1">Últimas visitas realizadas por los usuarios en el sistema.</p>
            </div>

            <div class="flow-root" v-if="recentVisits.length > 0">
                <ul class="-mb-8">
                    <li 
                        v-for="(log, logIdx) in recentVisits" 
                        :key="log.id"
                        class="relative pb-8"
                    >
                        <!-- Línea conectora temporal -->
                        <span 
                            v-if="logIdx !== recentVisits.length - 1" 
                            class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-[var(--border-color)]" 
                            aria-hidden="true"
                        ></span>
                        
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-lg bg-[var(--accent)]/10 border border-[var(--accent)]/30 flex items-center justify-center text-sm">
                                    📂
                                </span>
                            </div>
                            <div class="flex-1 min-w-0 pt-1.5 flex justify-between gap-4 text-xs">
                                <div>
                                    <p class="text-[var(--text-primary)] font-semibold">
                                        {{ log.user_identity }}
                                    </p>
                                    <p class="text-[var(--text-secondary)] mt-0.5">
                                        Accedió a <span class="text-[var(--accent)] font-semibold">{{ log.resource_name }}</span>
                                    </p>
                                    <p class="text-[10px] font-mono text-stone-500 mt-0.5">
                                        {{ log.visited_url }}
                                    </p>
                                </div>
                                <div class="text-right whitespace-nowrap text-stone-500 text-[10px] font-mono">
                                    {{ formatDateShort(log.created_at) }}
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            
            <div 
                v-else 
                class="h-60 flex flex-col items-center justify-center text-stone-500 text-sm"
            >
                ⏳ Esperando visitas para registrar en tiempo real...
            </div>
        </div>
    </div>
</template>
