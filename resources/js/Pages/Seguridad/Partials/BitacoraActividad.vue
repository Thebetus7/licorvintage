<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    activityLogs: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
});

const getEventBadgeClass = (type) => {
    if (type.endsWith('_success') || type.endsWith('_created') || type === 'caja_opened' || type === 'sale_created') {
        return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
    }
    if (type.endsWith('_failed') || type === 'sale_failed' || type === 'caja_open_failed') {
        return 'bg-rose-500/10 text-rose-400 border border-rose-500/20';
    }
    if (type.endsWith('_updated') || type === 'caja_movement') {
        return 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
    }
    if (type.endsWith('_deleted') || type === 'caja_closed') {
        return 'bg-stone-500/20 text-stone-400 border border-stone-500/25';
    }
    if (type === 'resource_access') {
        return 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
    }
    return 'bg-stone-500/10 text-stone-400 border border-stone-500/20';
};

const getEventLabel = (type) => {
    switch (type) {
        case 'login_success':
            return 'Sesión Iniciada';
        case 'login_failed':
            return 'Intento Fallido';
        case 'resource_access':
            return 'Acceso a Recurso';
        case 'product_created':
            return 'Producto Creado';
        case 'product_updated':
            return 'Producto Editado';
        case 'product_deleted':
            return 'Producto Eliminado';
        case 'purchase_created':
            return 'Compra Registrada';
        case 'purchase_updated':
            return 'Compra Editada';
        case 'purchase_deleted':
            return 'Compra Revertida';
        case 'caja_opened':
            return 'Caja Abierta';
        case 'caja_closed':
            return 'Caja Cerrada';
        case 'caja_open_failed':
            return 'Fallo Apertura';
        case 'caja_movement':
            return 'Movimiento Caja';
        case 'sale_created':
            return 'Venta Registrada';
        case 'sale_failed':
            return 'Venta Fallida';
        default:
            return type.replace('_', ' ').toUpperCase();
    }
};

const formatDate = (dateString) => {
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const cleanAgent = (userAgent) => {
    if (!userAgent) return 'Desconocido';
    if (userAgent.includes('Chrome')) return 'Google Chrome';
    if (userAgent.includes('Firefox')) return 'Mozilla Firefox';
    if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Apple Safari';
    if (userAgent.includes('Edge')) return 'Microsoft Edge';
    return userAgent.substring(0, 30) + '...';
};
</script>

<template>
    <div class="space-y-6 select-none">
        <!-- Tarjetas Estadísticas Superiores -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/40 p-5 shadow-xl backdrop-blur-md transition-colors duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)]">Logins Exitosos</p>
                        <h4 class="text-3xl font-extrabold text-emerald-400 mt-2">{{ stats.total_logins }}</h4>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/40 p-5 shadow-xl backdrop-blur-md transition-colors duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)]">Logins Fallidos</p>
                        <h4 class="text-3xl font-extrabold text-rose-400 mt-2">{{ stats.failed_logins }}</h4>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/40 p-5 shadow-xl backdrop-blur-md transition-colors duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)]">Direcciones IP Únicas</p>
                        <h4 class="text-3xl font-extrabold text-amber-400 mt-2">{{ stats.unique_ips }}</h4>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de logs -->
        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/40 p-6 shadow-xl backdrop-blur-md transition-colors duration-300">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Registro de Auditoría Integral (Bitácora)</h3>
                <p class="text-xs text-[var(--text-secondary)]">Monitorea en tiempo real todos los eventos de acceso, transacciones exitosas, errores del sistema e intentos fallidos.</p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[var(--border-color)]">
                <table class="w-full text-left border-collapse bg-stone-950/20">
                    <thead>
                        <tr class="border-b border-[var(--border-color)] bg-[var(--bg-secondary)]/50 text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)]">
                            <th class="px-6 py-4">Fecha y Hora</th>
                            <th class="px-6 py-4">Tipo de Evento</th>
                            <th class="px-6 py-4">Usuario / Identidad</th>
                            <th class="px-6 py-4">Dirección IP</th>
                            <th class="px-6 py-4">Descripción de la Actividad</th>
                            <th class="px-6 py-4">Recurso / URL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                        <tr 
                            v-for="log in activityLogs.data" 
                            :key="log.id" 
                            class="hover:bg-white/5 transition-colors duration-150"
                        >
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-mono">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold', getEventBadgeClass(log.event_type)]">
                                    {{ getEventLabel(log.event_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-[var(--text-primary)]">
                                {{ log.user_identity }}
                                <span v-if="log.user" class="text-xs block text-[var(--accent)]">
                                    (ID: {{ log.user_id }})
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs">
                                {{ log.ip_address || '127.0.0.1' }}
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-[var(--text-primary)] max-w-sm break-words leading-relaxed">
                                {{ log.description || 'Sin descripción disponible.' }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono max-w-xs truncate" :title="log.visited_url">
                                <span v-if="log.resource_name" class="font-semibold text-[var(--text-primary)] block">
                                    {{ log.resource_name }}
                                </span>
                                <span v-if="log.visited_url" class="text-[var(--text-secondary)] text-[10px]">
                                    {{ log.visited_url }}
                                </span>
                                <span v-else class="text-stone-600">-</span>
                            </td>
                        </tr>
                        <tr v-if="activityLogs.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-stone-500">
                                No hay registros en la bitácora aún.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="activityLogs.links && activityLogs.links.length > 3" class="mt-6 flex items-center justify-between border-t border-[var(--border-color)] pt-4">
                <div class="text-xs text-[var(--text-secondary)]">
                    Mostrando {{ activityLogs.from || 0 }} a {{ activityLogs.to || 0 }} de {{ activityLogs.total || 0 }} registros.
                </div>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, key) in activityLogs.links"
                        :key="key"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 rounded-lg border text-xs font-semibold transition cursor-pointer',
                            link.active 
                                ? 'bg-[var(--accent)] border-[var(--accent)] text-white' 
                                : 'border-[var(--border-color)] bg-stone-900 text-[var(--text-secondary)] hover:bg-stone-850 hover:text-[var(--text-primary)]',
                            !link.url ? 'opacity-40 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
