<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';

defineProps({
    productos: Array,
    valorTotal: Number,
});
</script>

<template>
    <AppLayout title="Valorizacion de inventario">
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold text-[#2b1115]">Valorizacion de inventario</h1>
                <p class="text-sm text-stone-500">Stock actual x costo promedio ponderado.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <div class="mb-4 rounded-lg border bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                Valor total del inventario: Bs {{ Number(valorTotal).toFixed(2) }}
            </div>

            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                        <tr>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Codigo</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Costo promedio</th>
                            <th class="px-4 py-3">Valor total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <tr v-for="producto in productos" :key="producto.id">
                            <td class="px-4 py-3 font-medium">{{ producto.nombre }}</td>
                            <td class="px-4 py-3">{{ producto.codigo_barra }}</td>
                            <td class="px-4 py-3">{{ producto.stock }}</td>
                            <td class="px-4 py-3">Bs {{ Number(producto.costo_promedio).toFixed(2) }}</td>
                            <td class="px-4 py-3 font-semibold">Bs {{ Number(producto.valor_total).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
