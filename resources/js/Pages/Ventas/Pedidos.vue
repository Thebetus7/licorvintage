<script setup>
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    pedidos: Object,
});

const selectedPedido = ref(null);
const showDetalleModal = ref(false);

function verDetalle(p) {
    selectedPedido.value = p;
    showDetalleModal.value = true;
}

function marcarEnviado(ventaId) {
    router.put(route('ventas.pedidos.estado', ventaId), { estado: 'enviado' }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function formatEstado(estado) {
    const map = { pagado: 'Pagado', enviado: 'Enviado', completado: 'Completado' };
    return map[estado] || estado;
}

function formatTipoPago(tipo) {
    const map = { efectivo: 'Efectivo', qr: 'QR', tarjeta: 'Tarjeta', credito: 'Credito', compra_directa: 'Directo', mixto: 'Mixto' };
    return map[tipo] || tipo;
}
</script>

<template>
    <AppLayout title="Pedidos">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-[var(--text-primary)]">Pedidos del día</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Pedidos realizados hoy por los clientes.</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-12 space-y-4">
            <div class="overflow-x-auto rounded-2xl border border-[var(--border-color)]">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[var(--bg-secondary)]/60 text-[var(--text-secondary)]">
                            <th class="text-left py-3 px-4 font-semibold">#</th>
                            <th class="text-left py-3 px-4 font-semibold">Cliente</th>
                            <th class="text-right py-3 px-4 font-semibold">Total</th>
                            <th class="text-center py-3 px-4 font-semibold">Pago</th>
                            <th class="text-center py-3 px-4 font-semibold">Estado</th>
                            <th class="text-center py-3 px-4 font-semibold">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in pedidos.data" :key="p.id" class="border-t border-[var(--border-color)] hover:bg-[var(--bg-secondary)]/20">
                            <td class="py-3 px-4 font-mono text-[var(--text-primary)]">{{ p.id }}</td>
                            <td class="py-3 px-4 text-[var(--text-primary)]">{{ p.cliente?.name || '—' }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-indigo-300">Bs {{ Number(p.monto_final).toFixed(2) }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="p.tipo_pago === 'credito' ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400'"
                                >
                                    {{ formatTipoPago(p.tipo_pago) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-400': p.estado_pedido === 'pagado',
                                        'bg-sky-500/10 text-sky-400': p.estado_pedido === 'enviado',
                                        'bg-gray-500/10 text-gray-400': p.estado_pedido === 'completado',
                                    }"
                                >
                                    {{ formatEstado(p.estado_pedido) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        class="text-xs bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                                        @click="verDetalle(p)"
                                    >
                                        Ver
                                    </button>
                                    <button
                                        v-if="p.estado_pedido === 'pagado'"
                                        class="text-xs bg-sky-500 hover:bg-sky-600 text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                                        @click="marcarEnviado(p.id)"
                                    >
                                        Enviar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!pedidos.data?.length">
                            <td colspan="6" class="py-12 text-center text-[var(--text-secondary)]">No hay pedidos hoy.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-center gap-2">
                <template v-for="(link, i) in pedidos.links" :key="i">
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
                    <span>Pedido #{{ selectedPedido?.id }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="{
                            'bg-emerald-500/10 text-emerald-400': selectedPedido?.estado_pedido === 'pagado',
                            'bg-sky-500/10 text-sky-400': selectedPedido?.estado_pedido === 'enviado',
                            'bg-gray-500/10 text-gray-400': selectedPedido?.estado_pedido === 'completado',
                        }"
                    >
                        {{ formatEstado(selectedPedido?.estado_pedido) }}
                    </span>
                </div>
            </template>
            <template #content>
                <div v-if="selectedPedido" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-[var(--text-secondary)]">Cliente:</span>
                            <p class="font-medium text-[var(--text-primary)]">{{ selectedPedido.cliente?.name || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-[var(--text-secondary)]">Fecha:</span>
                            <p class="font-medium text-[var(--text-primary)]">{{ new Date(selectedPedido.created_at).toLocaleString() }}</p>
                        </div>
                    </div>

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
                                <tr v-for="d in selectedPedido.detalle_ventas" :key="d.id" class="border-b border-[var(--border-color)]/50">
                                    <td class="py-2 text-[var(--text-primary)]">{{ d.producto?.nombre || '—' }}</td>
                                    <td class="py-2 text-center text-[var(--text-secondary)]">{{ d.cantidad }}</td>
                                    <td class="py-2 text-right font-mono text-[var(--text-secondary)]">Bs {{ Number(d.precio_u_final).toFixed(2) }}</td>
                                    <td class="py-2 text-right font-mono text-[var(--text-primary)]">Bs {{ Number(d.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-lg bg-[var(--bg-secondary)]/40 p-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-[var(--text-secondary)]">Total</span>
                            <span class="font-mono text-indigo-300 font-bold">Bs {{ Number(selectedPedido.monto_final).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="showDetalleModal = false">Cerrar</SecondaryButton>
                    <PrimaryButton
                        v-if="selectedPedido?.estado_pedido === 'pagado'"
                        @click="marcarEnviado(selectedPedido.id); showDetalleModal = false"
                    >
                        Marcar como Enviado
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>
