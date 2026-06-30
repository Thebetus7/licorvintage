<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';

defineProps({
    compras: Object,
});

const emit = defineEmits(['edit']);

const selectedCompra = ref(null);
const showDetailsModal = ref(false);

const openDetails = (compra) => {
    selectedCompra.value = compra;
    showDetailsModal.value = true;
};

const destroy = (compra) => {
    if (confirm(`Eliminar compra #${compra.id}?`)) {
        router.delete(route('compras.destroy', compra.id), { preserveScroll: true });
    }
};
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm backdrop-blur-sm transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border-color)]">
                <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs font-semibold uppercase text-[var(--accent)] transition-colors duration-300">
                    <tr>
                        <th class="px-4 py-3">Compra</th>
                        <th class="px-4 py-3">Proveedor</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Productos</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)] transition-colors duration-300">
                    <tr v-for="compra in compras.data" :key="compra.id" class="hover:bg-white/5 transition-colors">
                        <td class="px-4 py-3 text-[var(--text-primary)] font-semibold">#{{ compra.id }}</td>
                        <td class="px-4 py-3">{{ compra.proveedor?.nombre || 'Sin proveedor' }}</td>
                        <td class="px-4 py-3 text-[var(--text-primary)]">Bs {{ Number(compra.costo).toFixed(2) }}</td>
                        <td class="px-4 py-3">
                            <button
                                @click="openDetails(compra)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--text-primary)] hover:bg-[var(--accent)]/10 hover:border-[var(--accent)] transition-all cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 text-[var(--accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Ver productos ({{ compra.detalle_compras?.length || 0 }})
                            </button>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right whitespace-nowrap">
                            <button @click="emit('edit', compra)" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--accent)] hover:bg-[var(--accent)]/10 hover:border-[var(--accent)] hover:text-[var(--text-primary)] transition-all duration-200 cursor-pointer">
                                Editar
                            </button>
                            <DangerButton class="!px-3 !py-1.5 text-xs" @click="destroy(compra)">Eliminar</DangerButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-2 border-t border-[var(--border-color)] bg-[var(--bg-secondary)]/50 px-4 py-3 transition-colors duration-300">
            <Link
                v-for="link in compras.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-md px-3 py-1.5 text-xs font-medium transition-all"
                :class="link.active 
                    ? 'bg-[var(--accent)] text-white shadow-md' 
                     : 'bg-[var(--bg-secondary)]/40 text-[var(--text-secondary)] hover:bg-[var(--bg-secondary)] hover:text-[var(--text-primary)] border border-[var(--border-color)]'"
                v-html="link.label"
            />
        </div>
    </div>

    <!-- Modal de Detalles de Compra -->
    <DialogModal :show="showDetailsModal" max-width="md" @close="showDetailsModal = false">
        <template #title>
            <span class="text-[var(--text-primary)] font-bold">Productos de la Compra #{{ selectedCompra?.id }}</span>
        </template>
        <template #content>
            <div class="space-y-4">
                <div class="flex flex-wrap justify-between items-center text-sm border-b border-[var(--border-color)] pb-2 text-[var(--text-secondary)]">
                    <span>Proveedor: <strong class="text-[var(--text-primary)]">{{ selectedCompra?.proveedor?.nombre || 'Sin proveedor' }}</strong></span>
                    <span>Total: <strong class="text-[var(--accent)]">Bs {{ Number(selectedCompra?.costo || 0).toFixed(2) }}</strong></span>
                </div>
                <div class="divide-y divide-[var(--border-color)] max-h-96 overflow-y-auto pr-1">
                    <div v-for="detalle in selectedCompra?.detalle_compras" :key="detalle.id" class="py-3 flex justify-between items-start gap-4">
                        <div class="min-w-0">
                            <div class="font-medium text-[var(--text-primary)] text-sm">{{ detalle.producto?.nombre }}</div>
                            <div class="text-xs text-[var(--text-secondary)] mt-0.5">
                                Lote: <span class="font-mono text-[var(--accent)]">{{ detalle.lote?.codigo_lote || 'N/A' }}</span>
                                <span v-if="detalle.lote?.fecha_expiracion" class="ms-2">
                                    Vence: {{ detalle.lote.fecha_expiracion }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-semibold text-[var(--text-primary)]">{{ detalle.cantidad }} u.</div>
                            <div class="text-xs text-[var(--text-secondary)]">Bs {{ (Number(detalle.sub_costo) / detalle.cantidad).toFixed(2) }} c/u</div>
                            <div class="text-xs font-bold text-[var(--text-primary)] mt-0.5">Bs {{ Number(detalle.sub_costo).toFixed(2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="showDetailsModal = false">Cerrar</SecondaryButton>
        </template>
    </DialogModal>
</template>
