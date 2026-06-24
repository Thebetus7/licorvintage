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
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Compra</th>
                        <th class="px-4 py-3">Proveedor</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Productos</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-sm">
                    <tr v-for="compra in compras.data" :key="compra.id">
                        <td class="px-4 py-3">#{{ compra.id }}</td>
                        <td class="px-4 py-3">{{ compra.proveedor?.nombre || 'Sin proveedor' }}</td>
                        <td class="px-4 py-3">Bs {{ Number(compra.costo).toFixed(2) }}</td>
                        <td class="px-4 py-3">
                            <span
                                v-for="detalle in compra.detalle_compras"
                                :key="detalle.id"
                                class="mr-2 inline-block rounded bg-amber-50 px-2 py-1 text-xs text-amber-800"
                            >
                                {{ detalle.producto?.nombre }} x{{ detalle.cantidad }}
                            </span>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <SecondaryButton @click="emit('edit', compra)">Editar</SecondaryButton>
                            <DangerButton @click="destroy(compra)">Eliminar</DangerButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-2 border-t bg-stone-50 px-4 py-3">
            <Link
                v-for="link in compras.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-md px-3 py-1 text-sm"
                :class="link.active ? 'bg-[#2b1115] text-white' : 'bg-white text-stone-700'"
                v-html="link.label"
            />
        </div>
    </div>
</template>
