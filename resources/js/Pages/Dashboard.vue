<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const bitacoraData = computed(() => page.props.bitacora_data || null);

const activeTab = ref('general'); // 'general', 'matrix', 'logins', 'accesos'

// Filter logs from bitacoraData
const loginLogs = computed(() => {
    if (!bitacoraData.value) return [];
    return bitacoraData.value.recent_logs.filter(log => log.tipo === 'Login');
});

const accessLogs = computed(() => {
    if (!bitacoraData.value) return [];
    return bitacoraData.value.recent_logs.filter(log => log.tipo === 'Acceso');
});

const formatDateTime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleString('es-ES', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-2xl text-[#0f172a] dark:text-white leading-tight">
                        Panel de Control
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Gestión operativa y auditoría de seguridad del sistema.</p>
                </div>
                
                <!-- Tab Selector (Only for Admin / Propietario) -->
                <div v-if="bitacoraData" class="flex bg-slate-200 dark:bg-slate-900 p-1 rounded-lg text-xs font-bold shadow-inner">
                    <button 
                        @click="activeTab = 'general'"
                        class="px-3 py-1.5 rounded-md transition"
                        :class="activeTab === 'general' ? 'bg-amber-500 text-[#0f172a]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white'"
                    >
                        General
                    </button>
                    <button 
                        @click="activeTab = 'matrix'"
                        class="px-3 py-1.5 rounded-md transition"
                        :class="activeTab === 'matrix' ? 'bg-amber-500 text-[#0f172a]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white'"
                    >
                        Matriz de Acceso
                    </button>
                    <button 
                        @click="activeTab = 'logins'"
                        class="px-3 py-1.5 rounded-md transition"
                        :class="activeTab === 'logins' ? 'bg-amber-500 text-[#0f172a]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white'"
                    >
                        Auditoría Logins
                    </button>
                    <button 
                        @click="activeTab = 'accesos'"
                        class="px-3 py-1.5 rounded-md transition"
                        :class="activeTab === 'accesos' ? 'bg-amber-500 text-[#0f172a]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white'"
                    >
                        Auditoría Accesos
                    </button>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- 
                =========================================
                TAB: GENERAL VISTA
                =========================================
            -->
            <div v-if="activeTab === 'general'" class="space-y-6 animate-fade-in">
                <!-- Administrative cards -->
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] p-5 shadow-sm flex flex-col justify-between group hover:border-amber-500/50 transition">
                        <div>
                            <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Inventario</div>
                            <div class="mt-2 text-xl font-bold text-[#0f172a] dark:text-white">Productos y Stock</div>
                            <p class="text-xs text-slate-400 mt-2">Control total de stock físico, lotes y valorización en almacén.</p>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs font-semibold text-amber-600 dark:text-amber-400 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <span>Ver inventario</span>
                            <span>➔</span>
                        </div>
                    </div>
                    
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] p-5 shadow-sm flex flex-col justify-between group hover:border-amber-500/50 transition">
                        <div>
                            <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Compras</div>
                            <div class="mt-2 text-xl font-bold text-[#0f172a] dark:text-white">Ingreso de Mercadería</div>
                            <p class="text-xs text-slate-400 mt-2">Abastecimiento por proveedores, cálculo de costos y lotes de expiración.</p>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs font-semibold text-amber-600 dark:text-amber-400 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <span>Ver compras</span>
                            <span>➔</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] p-5 shadow-sm flex flex-col justify-between group hover:border-amber-500/50 transition">
                        <div>
                            <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Caja y Finanzas</div>
                            <div class="mt-2 text-xl font-bold text-[#0f172a] dark:text-white">Ventas y Arqueo</div>
                            <p class="text-xs text-slate-400 mt-2">Apertura, cierre de caja, registro de ventas al contado y planes de cuotas.</p>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs font-semibold text-amber-600 dark:text-amber-400 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <span>Ver arqueo de caja</span>
                            <span>➔</span>
                        </div>
                    </div>
                </div>

                <!-- Admin Metrics Summary (Bitacora summary) -->
                <div v-if="bitacoraData" class="grid gap-5 md:grid-cols-2">
                    <!-- Quick Bitacora Stats -->
                    <div class="bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm space-y-4">
                        <h3 class="font-bold text-sm text-[#0f172a] dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                            Auditoría de Inicios de Sesión (Logins)
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40 p-4 rounded-xl">
                                <span class="block text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                    {{ bitacoraData.stats.logins_exitosos }}
                                </span>
                                <span class="text-[10px] uppercase font-bold text-slate-500">Exitosos</span>
                            </div>
                            <div class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/40 p-4 rounded-xl">
                                <span class="block text-2xl font-black text-red-600 dark:text-red-400">
                                    {{ bitacoraData.stats.logins_fallidos }}
                                </span>
                                <span class="text-[10px] uppercase font-bold text-slate-500">Fallidos</span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Accessed Resources -->
                    <div class="bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm space-y-4">
                        <h3 class="font-bold text-sm text-[#0f172a] dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                            Recursos Más Accedidos (Top 5)
                        </h3>
                        <div class="space-y-2.5">
                            <div 
                                v-for="(rec, index) in bitacoraData.stats.top_recursos" 
                                :key="index"
                                class="flex items-center justify-between text-xs p-3 bg-slate-50 dark:bg-slate-900/40 rounded-r-xl border-l-4 border-l-[var(--accent)] border-t border-r border-b border-slate-200 dark:border-slate-800/60 shadow-sm transition-all duration-200 hover:translate-x-1 hover:bg-slate-100 dark:hover:bg-slate-900/80"
                            >
                                <span class="font-bold truncate text-slate-700 dark:text-slate-200 pr-3">
                                    {{ index + 1 }}. {{ rec.descripcion }}
                                </span>
                                <span class="bg-[var(--accent)] text-[var(--accent-text)] px-3 py-1 rounded-full font-black text-[10px] shadow-sm shrink-0 whitespace-nowrap">
                                    {{ rec.total }} accesos
                                </span>
                            </div>
                            <p v-if="!bitacoraData.stats.top_recursos.length" class="text-xs text-slate-400 text-center py-4">No hay registros de accesos aún.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 
                =========================================
                TAB: MATRIZ DE ACCESO
                =========================================
            -->
            <div v-else-if="activeTab === 'matrix' && bitacoraData" class="bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden animate-fade-in">
                <div class="p-5 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-base text-[#0f172a] dark:text-white">Matriz de Control de Acceso (Seguridad)</h3>
                    <p class="text-xs text-slate-400 mt-1">Representación gráfica de las políticas de seguridad y autorizaciones asignadas por rol.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase font-black text-slate-400">
                                <th class="p-4">Módulo del Sistema</th>
                                <th class="p-4 text-center">Rol: Propietario (Admin)</th>
                                <th class="p-4 text-center">Rol: Vendedor (Cajero)</th>
                                <th class="p-4 text-center">Rol: Cliente (Usuario)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 dark:divide-slate-800 text-sm">
                            <tr v-for="(row, index) in bitacoraData.matrix" :key="index" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition">
                                <td class="p-4 font-bold text-slate-700 dark:text-slate-300">{{ row.modulo }}</td>
                                <td class="p-4 text-center">
                                    <span v-if="row.propietario" class="text-emerald-500 font-black text-lg" title="Acceso Habilitado">✔</span>
                                    <span v-else class="text-red-500 font-black text-lg" title="Acceso Restringido">✘</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span v-if="row.vendedor" class="text-emerald-500 font-black text-lg" title="Acceso Habilitado">✔</span>
                                    <span v-else class="text-red-500 font-black text-lg" title="Acceso Restringido">✘</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span v-if="row.cliente" class="text-emerald-500 font-black text-lg" title="Acceso Habilitado">✔</span>
                                    <span v-else class="text-red-500 font-black text-lg" title="Acceso Restringido">✘</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 
                =========================================
                TAB: AUDITORÍA LOGINS (LOGS)
                =========================================
            -->
            <div v-else-if="activeTab === 'logins' && bitacoraData" class="bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden animate-fade-in">
                <div class="p-5 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-base text-[#0f172a] dark:text-white">Bitácora de Seguridad - Auditoría de Logins</h3>
                    <p class="text-xs text-slate-400 mt-1">Registro cronológico de todos los inicios de sesión exitosos e intentos fallidos.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase font-black text-slate-400">
                                <th class="p-4">Fecha y Hora</th>
                                <th class="p-4">Descripción del Suceso</th>
                                <th class="p-4">Dirección IP</th>
                                <th class="p-4">Dispositivo / Navegador</th>
                                <th class="p-4 text-center">Resultado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 dark:divide-slate-800 text-xs">
                            <tr v-for="log in loginLogs" :key="log.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition">
                                <td class="p-4 font-mono whitespace-nowrap text-slate-500">{{ formatDateTime(log.created_at) }}</td>
                                <td class="p-4 font-bold text-slate-700 dark:text-slate-300">{{ log.descripcion }}</td>
                                <td class="p-4 font-mono text-slate-600 dark:text-slate-450">{{ log.ip_address }}</td>
                                <td class="p-4 max-w-[200px] truncate text-slate-500" :title="log.user_agent">{{ log.user_agent }}</td>
                                <td class="p-4 text-center">
                                    <span 
                                        class="px-2 py-0.5 rounded-full font-bold text-[10px]"
                                        :class="log.estado === 'Exitoso' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'"
                                    >
                                        {{ log.estado }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!loginLogs.length">
                                <td colspan="5" class="p-8 text-center text-slate-400">No hay registros de logins guardados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 
                =========================================
                TAB: AUDITORÍA ACCESOS (LOGS)
                =========================================
            -->
            <div v-else-if="activeTab === 'accesos' && bitacoraData" class="bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden animate-fade-in">
                <div class="p-5 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-base text-[#0f172a] dark:text-white">Bitácora de Actividad - Auditoría de Accesos a Recursos</h3>
                    <p class="text-xs text-slate-400 mt-1">Trazabilidad en tiempo real sobre accesos a páginas y ejecuciones en el panel administrativo.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase font-black text-slate-400">
                                <th class="p-4">Fecha y Hora</th>
                                <th class="p-4">Usuario</th>
                                <th class="p-4">Acción Realizada</th>
                                <th class="p-4">Dirección IP</th>
                                <th class="p-4 text-center">Autorización</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 dark:divide-slate-800 text-xs">
                            <tr v-for="log in accessLogs" :key="log.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition">
                                <td class="p-4 font-mono whitespace-nowrap text-slate-500">{{ formatDateTime(log.created_at) }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-700 dark:text-slate-300">{{ log.user?.nombre || 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ log.user?.email }}</div>
                                </td>
                                <td class="p-4 font-semibold text-slate-700 dark:text-slate-300">
                                    <div class="flex items-center gap-2">
                                        <span class="bg-slate-100 dark:bg-slate-850 border border-slate-200 dark:border-slate-750 font-black px-1.5 py-0.5 rounded text-[9px] uppercase">
                                            {{ log.metadata?.metodo }}
                                        </span>
                                        <span>{{ log.descripcion }}</span>
                                    </div>
                                </td>
                                <td class="p-4 font-mono text-slate-600 dark:text-slate-450">{{ log.ip_address }}</td>
                                <td class="p-4 text-center">
                                    <span 
                                        class="px-2 py-0.5 rounded-full font-bold text-[10px]"
                                        :class="log.estado === 'Permitido' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'"
                                    >
                                        {{ log.estado }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!accessLogs.length">
                                <td colspan="5" class="p-8 text-center text-slate-400">No hay registros de accesos guardados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Subtle fade in transition for tabs */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.25s ease-out forwards;
}
</style>
