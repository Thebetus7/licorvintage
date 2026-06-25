<script setup>
import { Link, router } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    compras: Object,
});

const emit = defineEmits(['edit']);

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
                            <span
                                v-for="detalle in compra.detalle_compras"
                                :key="detalle.id"
                                class="mr-2 inline-block rounded bg-[var(--accent)]/10 text-[var(--accent)] border border-[var(--accent)]/15 px-2.5 py-1 text-xs transition-colors duration-300"
                            >
                                {{ detalle.producto?.nombre }} x{{ detalle.cantidad }}
                            </span>
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
</template>
