<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import MatrizAcceso from './Partials/MatrizAcceso.vue';
import BitacoraActividad from './Partials/BitacoraActividad.vue';
import EstadisticasRecursos from './Partials/EstadisticasRecursos.vue';

const props = defineProps({
    menuItems: {
        type: Array,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    mostVisitedResources: {
        type: Array,
        required: true,
    },
    activityLogs: {
        type: Object,
        required: true,
    },
});

const activeTab = ref('matrix'); // matrix, log, stats
</script>

<template>
    <AppLayout title="Seguridad y Auditoría">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-[var(--text-primary)] tracking-tight">
                        Seguridad & Auditoría
                    </h1>
                    <p class="text-sm text-[var(--text-secondary)] mt-1">
                        Control de acceso de usuarios (Matriz RBAC), Bitácora de Inicios de Sesión y Estadísticas de visitas de recursos.
                    </p>
                </div>
                
                <!-- Menú de Pestañas Visuales -->
                <div class="inline-flex rounded-xl p-1 bg-stone-950/40 border border-[var(--border-color)]">
                    <button
                        @click="activeTab = 'matrix'"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer"
                        :class="activeTab === 'matrix' ? 'bg-[var(--accent)] text-white shadow' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                    >
                        Matriz Acceso
                    </button>
                    <button
                        @click="activeTab = 'log'"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer"
                        :class="activeTab === 'log' ? 'bg-[var(--accent)] text-white shadow' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                    >
                        Bitácora Actividad
                    </button>
                    <button
                        @click="activeTab = 'stats'"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer"
                        :class="activeTab === 'stats' ? 'bg-[var(--accent)] text-white shadow' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                    >
                        Estadísticas Recursos
                    </button>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2">
            <!-- Pestaña Activa con Transiciones Suaves -->
            <div class="transition-all duration-300">
                <MatrizAcceso 
                    v-if="activeTab === 'matrix'" 
                    :menuItems="menuItems" 
                    :roles="roles" 
                />
                
                <BitacoraActividad 
                    v-if="activeTab === 'log'" 
                    :activityLogs="activityLogs" 
                    :stats="stats" 
                />
                
                <EstadisticasRecursos 
                    v-if="activeTab === 'stats'" 
                    :mostVisitedResources="mostVisitedResources"
                    :activityLogs="activityLogs"
                />
            </div>
        </div>
    </AppLayout>
</template>
