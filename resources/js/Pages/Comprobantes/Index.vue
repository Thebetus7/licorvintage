<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    ventas: Object,
    filters: Object,
});

const searchForm = useForm({
    from: props.filters.from,
    to: props.filters.to,
});

const selectedVenta = ref(null);
const showDetalleModal = ref(false);

function verDetalle(v) {
    selectedVenta.value = v;
    showDetalleModal.value = true;
}

function filtrar() {
    router.get(route('comprobantes.index'), {
        from: searchForm.from,
        to: searchForm.to,
    }, { preserveState: true, preserveScroll: true });
}

function formatTipoPago(tipo) {
    const map = { efectivo: 'Efectivo', qr: 'QR', tarjeta: 'Tarjeta', credito: 'Credito', compra_directa: 'Directo', mixto: 'Mixto' };
    return map[tipo] || tipo;
}

function formatDiff(diff) {
    const n = Number(diff || 0);
    return (n >= 0 ? '+' : '') + n.toFixed(2);
}
</script>

<template>
    <AppLayout title="Comprobantes">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-[var(--text-primary)]">Comprobantes</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Todas las ventas registradas.</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-12 space-y-4">
            <!-- Filtros -->
            <div class="flex items-end gap-4 flex-wrap rounded-2xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[var(--text-secondary)]">Desde</label>
                    <input v-model="searchForm.from" type="date" class="rounded-lg border-[var(--border-color)] bg-[var(--bg-secondary)] text-sm text-[var(--text-primary)] px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[var(--text-secondary)]">Hasta</label>
                    <input v-model="searchForm.to" type="date" class="rounded-lg border-[var(--border-color)] bg-[var(--bg-secondary)] text-sm text-[var(--text-primary)] px-3 py-2">
                </div>
                <PrimaryButton type="button" @click="filtrar">Filtrar</PrimaryButton>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto rounded-2xl border border-[var(--border-color)]">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[var(--bg-secondary)]/60 text-[var(--text-secondary)]">
                            <th class="text-left py-3 px-4 font-semibold">#</th>
                            <th class="text-left py-3 px-4 font-semibold">Fecha</th>
                            <th class="text-left py-3 px-4 font-semibold">Cliente</th>
                            <th class="text-left py-3 px-4 font-semibold">Vendedor</th>
                            <th class="text-right py-3 px-4 font-semibold">Total</th>
                            <th class="text-center py-3 px-4 font-semibold">Pago</th>
                            <th class="text-right py-3 px-4 font-semibold">Metodos</th>
                            <th class="text-center py-3 px-4 font-semibold">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="v in ventas.data" :key="v.id" class="border-t border-[var(--border-color)] hover:bg-[var(--bg-secondary)]/20">
                            <td class="py-3 px-4 font-mono text-[var(--text-primary)]">{{ v.id }}</td>
                            <td class="py-3 px-4 text-[var(--text-secondary)]">{{ new Date(v.created_at).toLocaleString() }}</td>
                            <td class="py-3 px-4 text-[var(--text-primary)]">{{ v.cliente?.name || 'Consumidor Final' }}</td>
                            <td class="py-3 px-4 text-[var(--text-primary)]">{{ v.user?.name || '—' }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-indigo-300">Bs {{ Number(v.monto_final).toFixed(2) }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="v.tipo_pago === 'credito' ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400'"
                                >
                                    {{ formatTipoPago(v.tipo_pago) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div v-if="v.metodo_pagos?.length" class="space-y-0.5">
                                    <div v-for="mp in v.metodo_pagos" :key="mp.id" class="text-xs text-[var(--text-secondary)]">
                                        {{ formatTipoPago(mp.tipo_pago) }}: Bs {{ Number(mp.monto || 0).toFixed(2) }}
                                    </div>
                                </div>
                                <span v-else class="text-xs text-[var(--text-secondary)]">—</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <button
                                    class="text-xs bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                                    @click="verDetalle(v)"
                                >
                                    Ver
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!ventas.data?.length">
                            <td colspan="8" class="py-12 text-center text-[var(--text-secondary)]">No hay ventas en este rango.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginacion -->
            <div class="flex justify-center gap-2">
                <template v-for="(link, i) in ventas.links" :key="i">
                    <button
                        v-if="link.url"
                        :disabled="link.active"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
                        :class="link.active ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-secondary)] hover:bg-white/5'"
                        @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                        v-html="link.label"
                    />
                    <span v-else class="px-3 py-1.5 text-sm text-[var(--text-secondary)]" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Modal Detalle -->
        <DialogModal :show="showDetalleModal" max-width="lg" @close="showDetalleModal = false">
            <template #title>
                <div class="flex items-center gap-2 text-[var(--text-primary)]">
                    <span>Factura #{{ selectedVenta?.id }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="selectedVenta?.tipo_pago === 'credito' ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400'"
                    >
                        {{ formatTipoPago(selectedVenta?.tipo_pago) }}
                    </span>
                </div>
            </template>
            <template #content>
                <div v-if="selectedVenta" class="space-y-4">
                    <!-- Info general -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-[var(--text-secondary)]">Fecha:</span>
                            <p class="font-medium text-[var(--text-primary)]">{{ new Date(selectedVenta.created_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--text-secondary)]">Vendedor:</span>
                            <p class="font-medium text-[var(--text-primary)]">{{ selectedVenta.user?.name || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--text-secondary)]">Cliente:</span>
                            <p class="font-medium text-[var(--text-primary)]">{{ selectedVenta.cliente?.name || 'Consumidor Final' }}</p>
                        </div>
                        <div v-if="selectedVenta.cliente?.ci">
                            <span class="text-[var(--text-secondary)]">CI:</span>
                            <p class="font-medium text-[var(--text-primary)]">{{ selectedVenta.cliente.ci }}</p>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase text-[var(--text-secondary)] mb-2">Productos</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--border-color)] text-[var(--text-secondary)]">
                                    <th class="text-left py-2 font-semibold">Producto</th>
                                    <th class="text-center py-2 font-semibold">Cant</th>
                                    <th class="text-right py-2 font-semibold">P.U.</th>
                                    <th class="text-right py-2 font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in selectedVenta.detalle_ventas" :key="d.id" class="border-b border-[var(--border-color)]/50">
                                    <td class="py-2 text-[var(--text-primary)]">{{ d.producto?.nombre || '—' }}</td>
                                    <td class="py-2 text-center text-[var(--text-secondary)]">{{ d.cantidad }}</td>
                                    <td class="py-2 text-right font-mono text-[var(--text-secondary)]">Bs {{ Number(d.precio_u_final).toFixed(2) }}</td>
                                    <td class="py-2 text-right font-mono text-[var(--text-primary)]">Bs {{ Number(d.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Montos -->
                    <div class="rounded-lg bg-[var(--bg-secondary)]/40 p-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-[var(--text-secondary)]">Subtotal</span>
                            <span class="font-mono text-[var(--text-primary)]">Bs {{ Number(selectedVenta.monto_original).toFixed(2) }}</span>
                        </div>
                        <div v-if="selectedVenta.cod_descuento" class="flex justify-between">
                            <span class="text-[var(--text-secondary)]">Descuento ({{ selectedVenta.cod_descuento }})</span>
                            <span class="font-mono text-emerald-400">−Bs {{ (Number(selectedVenta.monto_original) - Number(selectedVenta.monto_final)).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-[var(--border-color)] pt-1 text-base font-bold">
                            <span class="text-[var(--text-primary)]">Total</span>
                            <span class="font-mono text-indigo-300">Bs {{ Number(selectedVenta.monto_final).toFixed(2) }}</span>
                        </div>
                    </div>

                    <!-- Metodos de pago -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase text-[var(--text-secondary)] mb-2">Metodos de pago</h4>
                        <div class="space-y-1">
                            <div v-for="mp in selectedVenta.metodo_pagos" :key="mp.id" class="flex justify-between text-sm">
                                <span class="text-[var(--text-primary)]">{{ formatTipoPago(mp.tipo_pago) }}</span>
                                <span class="font-mono text-[var(--text-primary)]">Bs {{ Number(mp.monto || 0).toFixed(2) }}</span>
                            </div>
                            <div v-if="!selectedVenta.metodo_pagos?.length" class="flex justify-between text-sm">
                                <span class="text-[var(--text-primary)]">{{ formatTipoPago(selectedVenta.tipo_pago) }}</span>
                                <span class="font-mono text-[var(--text-primary)]">Bs {{ Number(selectedVenta.monto_final).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cuotas (credito) -->
                    <div v-if="selectedVenta.tipo_pago === 'credito' && selectedVenta.venta_cuotas?.length">
                        <h4 class="text-xs font-semibold uppercase text-[var(--text-secondary)] mb-2">Cuotas</h4>
                        <div class="space-y-1">
                            <div v-for="cu in selectedVenta.venta_cuotas" :key="cu.id" class="flex justify-between text-sm">
                                <span class="text-[var(--text-primary)]">Cuota #{{ cu.nro_cuota }}</span>
                                <span class="font-mono">Bs {{ Number(cu.sub_monto).toFixed(2) }}</span>
                                <span :class="cu.estado === 'pagado' ? 'text-emerald-400' : 'text-amber-400'">{{ cu.estado === 'pagado' ? 'Pagado' : 'Pendiente' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end">
                    <SecondaryButton @click="showDetalleModal = false">Cerrar</SecondaryButton>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>
