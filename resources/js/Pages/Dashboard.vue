<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    paginas_visitadas: {
        type: Array,
        required: true,
    },
    recursos_accedidos: {
        type: Array,
        required: true,
    }
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-[var(--text-primary)] leading-tight tracking-wide">
                        Resumen del Negocio
                    </h2>
                    <p class="text-sm text-[var(--text-secondary)]">Monitorea las estadísticas comerciales e interacciones en tiempo real.</p>
                </div>
                <div class="text-xs bg-white/5 border border-white/10 px-3 py-1.5 rounded-full text-[var(--text-secondary)] font-medium">
                    Actualizado: {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8 pb-12">
            <!-- Tarjetas de Estadísticas Principales -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Ventas de Hoy -->
                <div class="relative group rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md transition-all duration-300 hover:scale-[1.02] hover:shadow-indigo-500/10">
                    <div class="absolute -top-3 -right-3 h-12 w-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-xl text-indigo-400 group-hover:scale-110 transition-transform duration-300">
                        📈
                    </div>
                    <div class="text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider">Ventas de Hoy</div>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold text-[var(--text-primary)]">{{ (stats?.ventas_hoy ?? 0).toFixed(2) }}</span>
                        <span class="text-sm font-semibold text-indigo-400">Bs</span>
                    </div>
                    <div class="mt-2 text-xs text-emerald-400 flex items-center gap-1">
                        <span>● En vivo</span>
                    </div>
                </div>

                <!-- Monto en Caja -->
                <div class="relative group rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md transition-all duration-300 hover:scale-[1.02] hover:shadow-emerald-500/10">
                    <div class="absolute -top-3 -right-3 h-12 w-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-xl text-emerald-400 group-hover:scale-110 transition-transform duration-300">
                        💵
                    </div>
                    <div class="text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider">Monto en Caja</div>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold text-[var(--text-primary)]">{{ (stats?.monto_caja ?? 0).toFixed(2) }}</span>
                        <span class="text-sm font-semibold text-emerald-400">Bs</span>
                    </div>
                    <div class="mt-2 text-xs text-[var(--text-secondary)]">Saldo de caja activa</div>
                </div>

                <!-- Compras del Mes -->
                <div class="relative group rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md transition-all duration-300 hover:scale-[1.02] hover:shadow-amber-500/10">
                    <div class="absolute -top-3 -right-3 h-12 w-12 rounded-2xl bg-amber-500/10 border border-amber-500/25 flex items-center justify-center text-xl text-amber-400 group-hover:scale-110 transition-transform duration-300">
                        🛒
                    </div>
                    <div class="text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider">Compras del Mes</div>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold text-[var(--text-primary)]">{{ (stats?.compras_mes ?? 0).toFixed(2) }}</span>
                        <span class="text-sm font-semibold text-amber-400">Bs</span>
                    </div>
                    <div class="mt-2 text-xs text-[var(--text-secondary)]">Inversión acumulada</div>
                </div>

                <!-- Total Productos -->
                <div class="relative group rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md transition-all duration-300 hover:scale-[1.02] hover:shadow-rose-500/10">
                    <div class="absolute -top-3 -right-3 h-12 w-12 rounded-2xl bg-rose-500/10 border border-rose-500/25 flex items-center justify-center text-xl text-rose-400 group-hover:scale-110 transition-transform duration-300">
                        📦
                    </div>
                    <div class="text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider">Productos Registrados</div>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold text-[var(--text-primary)]">{{ stats?.total_productos ?? 0 }}</span>
                        <span class="text-sm font-semibold text-rose-400">ítems</span>
                    </div>
                    <div class="mt-2 text-xs text-[var(--text-secondary)]">En catálogo</div>
                </div>
            </div>

            <!-- Gráficos y Tablas de Bitácoras de Visitas y Accesos -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Visitas por Páginas (Punto 7) -->
                <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-[var(--text-primary)]">Tráfico por Página</h3>
                            <p class="text-xs text-[var(--text-secondary)]">Contador de vistas globales individuales.</p>
                        </div>
                        <span class="text-xs bg-indigo-500/10 border border-indigo-500/25 text-indigo-300 px-3 py-1 rounded-full font-medium">
                            Total Visitas
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

                <!-- Recursos Más Accedidos (Punto 8 - Bitácora / Matriz) -->
                <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
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
