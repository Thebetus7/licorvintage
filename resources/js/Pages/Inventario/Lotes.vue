<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import ReportModal from '@/Components/ReportModal.vue';

const props = defineProps({
    lotes: Object,
    productos: Array,
    filters: Object,
    totalProximos: Number,
    totalVencidos: Number,
});

const filterForm = ref({
    producto_id: props.filters.producto_id || '',
    estado: props.filters.estado || '',
});

const search = () => {
    router.get(route('inventario.lotes'), {
        producto_id: filterForm.value.producto_id,
        estado: filterForm.value.estado,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(() => filterForm.value.producto_id, search);
watch(() => filterForm.value.estado, search);

const getDaysRemaining = (expiryDate) => {
    if (!expiryDate) return null;
    const diffTime = new Date(expiryDate) - new Date();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

const getStatusBadge = (lote) => {
    if (lote.estado === 'agotado') {
        return { text: 'Agotado', class: 'bg-stone-500/15 text-stone-400 border-stone-500/30' };
    }
    const days = getDaysRemaining(lote.fecha_expiracion);
    if (days === null) {
        return { text: 'Activo (Sin Vencimiento)', class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' };
    }
    if (days <= 0) {
        return { text: 'Vencido', class: 'bg-rose-500/15 text-rose-400 border-rose-500/30' };
    }
    if (days <= 30) {
        return { text: `Vence en ${days} días`, class: 'bg-amber-500/15 text-amber-400 border-amber-500/30' };
    }
    return { text: 'Activo', class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' };
};

const getStockProgressColor = (lote) => {
    const pct = (lote.cantidad_actual / lote.cantidad_inicial) * 100;
    if (pct <= 20) return 'bg-rose-500';
    if (pct <= 50) return 'bg-amber-500';
    return 'bg-emerald-500';
};
</script>

<template>
    <AppLayout title="Control de Lotes">
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Control de Lotes</h1>
                <p class="text-sm text-[var(--text-secondary)]">Seguimiento de compras por lotes, stock disponible y vencimientos.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <!-- Resumen de Alertas -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-5 shadow-sm text-[var(--text-primary)] transition-colors duration-300">
                    <div class="text-sm text-[var(--text-secondary)]">Lotes Vencidos</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold" :class="totalVencidos > 0 ? 'text-rose-500' : 'text-[var(--text-primary)]'">
                            {{ totalVencidos }}
                        </span>
                        <span v-if="totalVencidos > 0" class="text-xs font-semibold px-2 py-0.5 rounded bg-rose-500/15 text-rose-400">Atención requerida</span>
                    </div>
                </div>
                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-5 shadow-sm text-[var(--text-primary)] transition-colors duration-300">
                    <div class="text-sm text-[var(--text-secondary)]">Próximos a Vencer (30 días)</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold" :class="totalProximos > 0 ? 'text-amber-500' : 'text-[var(--text-primary)]'">
                            {{ totalProximos }}
                        </span>
                        <span v-if="totalProximos > 0" class="text-xs font-semibold px-2 py-0.5 rounded bg-amber-500/15 text-amber-400">Advertencia</span>
                    </div>
                </div>
                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-5 shadow-sm text-[var(--text-primary)] transition-colors duration-300 col-span-1 sm:col-span-2 lg:col-span-1">
                    <div class="text-sm text-[var(--text-secondary)]">Total Lotes Registrados</div>
                    <div class="mt-1 text-2xl font-bold text-[var(--text-primary)]">{{ lotes.total }}</div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="mb-6 rounded-xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/50 p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-[var(--text-secondary)] uppercase mb-1">Filtrar por Producto</label>
                        <select v-model="filterForm.producto_id" class="w-full rounded-xl border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--text-primary)] text-sm focus:border-[var(--accent)] focus:ring-[var(--accent)]">
                            <option value="" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Todos los productos</option>
                            <option v-for="prod in productos" :key="prod.id" :value="prod.id" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">{{ prod.nombre }}</option>
                        </select>
                    </div>
                    <div class="w-48">
                        <label class="block text-xs font-medium text-[var(--text-secondary)] uppercase mb-1">Filtrar por Estado</label>
                        <select v-model="filterForm.estado" class="w-full rounded-xl border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--text-primary)] text-sm focus:border-[var(--accent)] focus:ring-[var(--accent)]">
                            <option value="" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Todos los estados</option>
                            <option value="activo" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Activo</option>
                            <option value="agotado" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Agotado</option>
                        </select>
                    </div>
                    <div>
                        <ReportModal module="inventario_lotes" :filters="filterForm" />
                    </div>
                </div>
            </div>

            <!-- Tabla de Lotes -->
            <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm transition-colors duration-300">
                <table class="min-w-full divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                    <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs uppercase text-[var(--accent)] font-semibold">
                        <tr>
                            <th class="px-6 py-3">Código</th>
                            <th class="px-6 py-3">Producto</th>
                            <th class="px-6 py-3">Fecha Ingreso</th>
                            <th class="px-6 py-3">Vencimiento</th>
                            <th class="px-6 py-3">Uso / Consumo</th>
                            <th class="px-6 py-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        <tr v-if="lotes.data.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-[var(--text-secondary)]">
                                No se encontraron lotes registrados.
                            </td>
                        </tr>
                        <tr v-for="lote in lotes.data" :key="lote.id" class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-[var(--text-primary)]">
                                {{ lote.codigo_lote }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-[var(--text-primary)]">{{ lote.producto?.nombre }}</div>
                                <div class="text-xs text-[var(--text-secondary)]">ID: {{ lote.producto_id }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                {{ new Date(lote.fecha_ingreso).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold">
                                <span v-if="lote.fecha_expiracion" :class="getDaysRemaining(lote.fecha_expiracion) <= 0 ? 'text-rose-400 font-bold' : (getDaysRemaining(lote.fecha_expiracion) <= 30 ? 'text-amber-400' : 'text-[var(--text-primary)]')">
                                    {{ new Date(lote.fecha_expiracion).toLocaleDateString() }}
                                </span>
                                <span v-else class="text-stone-500 font-normal">Sin vencimiento</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-semibold text-[var(--text-primary)]">{{ lote.cantidad_actual }} / {{ lote.cantidad_inicial }} u</span>
                                    <span class="text-[var(--text-secondary)]">{{ Math.round((lote.cantidad_actual / lote.cantidad_inicial) * 100) }}%</span>
                                </div>
                                <div class="w-full bg-stone-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full transition-all duration-500" 
                                         :class="getStockProgressColor(lote)"
                                         :style="{ width: `${(lote.cantidad_actual / lote.cantidad_inicial) * 100}%` }">
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full border" :class="getStatusBadge(lote).class">
                                    {{ getStatusBadge(lote).text }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="text-sm text-[var(--text-secondary)]">
                    Mostrando {{ lotes.from || 0 }} al {{ lotes.to || 0 }} de {{ lotes.total }} lotes
                </div>
                <div class="flex flex-wrap items-center justify-center gap-1">
                    <Link v-for="link in lotes.links" 
                          :key="link.label"
                          :href="link.url || '#'" 
                          class="px-3 py-1.5 text-xs rounded border transition duration-150"
                          :class="[
                              link.active ? 'bg-[var(--accent)] text-white border-[var(--accent)]' : 'bg-[var(--bg-secondary)] text-[var(--text-primary)] border-[var(--border-color)] hover:bg-white/5',
                              !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                          ]"
                          v-html="link.label">
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
