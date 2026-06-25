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
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Valorización de inventario</h1>
                <p class="text-sm text-[var(--text-secondary)]">Stock actual x costo promedio ponderado.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <div class="mb-4 rounded-lg border border-[var(--accent)]/30 bg-[var(--accent)]/10 px-4 py-3 text-sm font-semibold text-[var(--accent)] transition-colors duration-300">
                Valor total del inventario: Bs {{ Number(valorTotal).toFixed(2) }}
            </div>

            <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm transition-colors duration-300">
                <table class="min-w-full divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                    <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs uppercase text-[var(--accent)]">
                        <tr>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Costo promedio</th>
                            <th class="px-4 py-3">Valor total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        <tr v-for="producto in productos" :key="producto.id" class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">{{ producto.nombre }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ producto.codigo_barra }}</td>
                            <td class="px-4 py-3 text-[var(--text-primary)]">{{ producto.stock }}</td>
                            <td class="px-4 py-3">Bs {{ Number(producto.costo_promedio).toFixed(2) }}</td>
                            <td class="px-4 py-3 font-bold text-[var(--text-primary)]">Bs {{ Number(producto.valor_total).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
